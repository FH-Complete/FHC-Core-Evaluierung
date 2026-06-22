<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

use Box\Spout\Common\Type;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Writer\Style\StyleBuilder; // note: also a different namespace in 2.x vs 3.x
use Box\Spout\Common\Entity\Style\Color;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as PhpSpreadsheetWriter;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class Export extends FHCAPI_Controller
{
	public function __construct()
	{
		parent::__construct(array(
				'getExportRowCount' => 'extension/lvevaluierung_export:rw',
				'exportAllToExcelCursor' => 'extension/lvevaluierung_export:rw'
			)
		);
		
		$this->load->model('extensions/FHC-Core-Evaluierung/Lvevaluierung_model', 'LvevaluierungModel');
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungLehrveranstaltung_model', 'LvevaluierungLehrveranstaltungModel');
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungCode_model', 'LvevaluierungCodeModel');
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungPrestudent_model', 'LvevaluierungPrestudentModel');
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungZeitfenster_model', 'LvevaluierungZeitfensterModel');

		// Load language phrases
		$this->loadPhrases([
			'ui',
			'global'
		]);

		// Loads LogLib with different debug trace levels to get data of the job that extends this class
		// It also specify parameters to set database fields
		$this->load->library('LogLib', array(
			'classIndex' => 5,
			'functionIndex' => 5,
			'lineIndex' => 4,
			'dbLogType' => 'API', // required
			'dbExecuteUser' => 'RESTful API',
			'requestId' => 'API',
			'requestDataFormatter' => function ($data) {
				return json_encode($data);
			}
		), 'logLib');

		$this->_uid = getAuthUid();
	}

	private function exportViaPhpSpreadsheet($tempPath, array $rows, array $headers, array $columnWidths)
	{
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setTitle('Evaluierungsdaten');

		// Write Headers
		$headerValues = array_values($headers);
		$sheet->fromArray($headerValues, null, 'A1');
		$sheet->getRowDimension(1)->setRowHeight(30);

		// Write Data Rows
		$rowData = [];
		foreach ($rows as $row) {
			$rowData[] = array_values((array) $row);
		}
		if (!empty($rowData)) {
			$sheet->fromArray($rowData, null, 'A2');
		}

		$highestRow = $sheet->getHighestRow();
		$highestColumnIndex = count($headers);
		$highestColumnLetter = Coordinate::stringFromColumnIndex($highestColumnIndex);

		// Dynamic row heights for data rows
		for ($r = 2; $r <= $highestRow; $r++) {
			$sheet->getRowDimension($r)->setRowHeight(45);
		}

		// Set deliberate Column Widths
		foreach ($columnWidths as $index => $width) {
			$colLetter = Coordinate::stringFromColumnIndex($index + 1);
			$sheet->getColumnDimension($colLetter)->setWidth($width);
		}

		// Stylize the Sheet Layout
		$headerRange = "A1:{$highestColumnLetter}1";
		$fullRange   = "A1:{$highestColumnLetter}{$highestRow}";

		// Header Specific Layout (Steel Blue background, White Bold text)
		$sheet->getStyle($headerRange)->applyFromArray([
			'font' => [
				'bold' => true,
				'color' => ['argb' => 'FFFFFFFF'],
				'size' => 11,
			],
			'fill' => [
				'fillType' => Fill::FILL_SOLID,
				'startColor' => ['argb' => 'FF365F91'],
			],
		]);

		// Global Alignment and Grid Styles
		$sheet->getStyle($fullRange)->applyFromArray([
			'alignment' => [
				'vertical' => Alignment::VERTICAL_CENTER,
				'wrapText' => true,
			],
			'borders' => [
				'allBorders' => [
					'borderStyle' => Border::BORDER_THIN,
					'color' => ['argb' => 'FFD3D3D3'], // subtle light gray gridline
				],
			],
		]);

		// Freeze header pane so scrolling through data is intuitive
		$sheet->freezePane('A2');

		// Save File
		$writer = new PhpSpreadsheetWriter($spreadsheet);
		$writer->save($tempPath);
	}

	private function buildColumnWidths(array $headerKeys, array $struktur)
	{
		$fixedWidths = [
			'lehrveranstaltung_id'              => 8,
			'lv_titel'                          => 30,
			'lv_titel_english'                  => 30,
			'lv_semester'                       => 10,
			'lv_orgform'                        => 8,
			'studiengang'                       => 28,
			'studiengang_typ'                   => 6,
			'zur_eval_ausgewaehlt'              => 16,
			'gruppen_info'                      => 16,
			'lv_leitung_hat_datum_eingetragen'  => 12,
			'startzeit'                         => 20,
			'endezeit'                          => 20,
			'linkversand_datum'                 => 20,
			'linkversand_von'                   => 14,
			'code_verwendet'                    => 15,
			'lv_eval_abgeschlossen'             => 15,
			'durchfuehrungsdatum'               => 20,
			'code_startzeit'                    => 20,
			'code_endzeit'                      => 20,
			'optionale_bereiche_angeklickt'     => 14,
		];

		$frageTypById = [];
		foreach ($struktur as $f) {
			$frageTypById[$f->lvevaluierung_frage_id] = $f->frage_typ;
		}

		$widths = [];
		foreach ($headerKeys as $key) {
			if (isset($fixedWidths[$key])) {
				$widths[] = $fixedWidths[$key];
				continue;
			}
			if (preg_match('/^frage_(\d+)$/', $key, $m)) {
				// score columns are narrow ('99'/values), free-text answers need room to read
				$widths[] = ($frageTypById[(int) $m[1]] ?? null) === 'text' ? 60 : 20;
				continue;
			}
			$widths[] = 20; // fallback for anything unforeseen
		}

		return $widths;
	}

	private function getFixedHeaders()
	{
		return [
			'lehrveranstaltung_id'              => 'LV-ID',
			'lv_titel'                          => 'LV-Titel',
			'lv_titel_english'                  => 'LV-Titel (EN)',
			'lv_semester'                       => 'Semester',
			'lv_orgform'                        => 'Orgform',
			'studiengang'                       => 'Studiengang',
			'studiengang_typ'                   => 'Typ',
			'zur_eval_ausgewaehlt'              => 'Zur Evaluierung ausgewählt',
			'gruppen_info'                      => 'Gruppe',
			'lv_leitung_hat_datum_eingetragen'  => 'Datum eingetragen',
			'startzeit'                         => 'Startzeit',
			'endezeit'                          => 'Endezeit',
			'linkversand_datum'                 => 'Linkversand Datum',
			'linkversand_von'                   => 'Linkversand von',
			'code_verwendet'                    => 'Code verwendet',
			'lv_eval_abgeschlossen'             => 'Abgeschlossen',
			'durchfuehrungsdatum'               => 'Durchführungsdatum',
			'code_startzeit'                    => 'Code Startzeit',
			'code_endzeit'                      => 'Code Endzeit',
		];
	}

	private function pivotExportData(array $baseRows, array $struktur, array $antworten)
	{
		$byCode = [];
		foreach ($antworten as $a) {
			$byCode[$a->lvevaluierung_code_id][$a->lvevaluierung_frage_id] = $a;
		}

		foreach ($baseRows as $row) {
			$hatOptionaleBereicheBeantwortet = false;

			foreach ($struktur as $frageRow) {
				$col = 'frage_' . $frageRow->lvevaluierung_frage_id;
				$antwortRow = $byCode[$row->lvevaluierung_code_id][$frageRow->lvevaluierung_frage_id] ?? null;
				$row->$col = $this->resolveValue($frageRow, $antwortRow);

				// Generic replacement for the old hardcoded optionale_bereiche_angeklickt flag:
				// true if any answer exists under any gruppe of typ 'group'
				if ($frageRow->gruppe_typ === 'group' && $antwortRow !== null) {
					$hatOptionaleBereicheBeantwortet = true;
				}
			}

			$row->optionale_bereiche_angeklickt = $hatOptionaleBereicheBeantwortet ? 'ja' : 'nein';

			unset($row->fragebogen_id, $row->lvevaluierung_code_id);
		}

		return $baseRows;
	}

	private function resolveValue($frageRow, $antwortRow)
	{
		if ($frageRow->frage_typ === 'text') {
			$val = $antwortRow->antwort ?? null;
			return ($val !== null && $val !== '') ? $val : 'nein';
		}
		return $antwortRow->wert ?? '99'; // singleresponse
	}

	private function buildFragebogenHeaders(array $struktur)
	{
		$headers = [];
		foreach ($struktur as $frageRow) {
			$label = $frageRow->frage_bezeichnung;
			$headers['frage_' . $frageRow->lvevaluierung_frage_id] =
				is_array($label) ? ($label[0] ?? '') : $label;
		}
		$headers['optionale_bereiche_angeklickt'] = 'Optionale Bereiche angeklickt';
		return $headers;
	}
	

	// =================== EXPORT WITH CURSOR ===================

	public function exportAllToExcelCursor()
	{
		$startTime = microtime(true);
		set_time_limit(1500); // 25min export
		ini_set('max_execution_time', 1500);
//		ini_set('memory_limit', '768M'); // 10k in phpspreadsheet working
		
		$this->load->model('extensions/FHC-Core-Evaluierung/Lvevaluierung_model', 'LvevaluierungModel');

		$studiensemester = $this->input->get('studiensemester');
		$von = $this->input->get('von');
		$bis = $this->input->get('bis');

		$this->logLib->logInfoDB('exportAllToExcel start: sem=' . $studiensemester . ' von=' . $von . ' bis=' . $bis);

		$countResult = $this->LvevaluierungModel->getExportRowCount($studiensemester, $von, $bis);
		if (isError($countResult)) {
			$this->logLib->logInfoDB('getExportRowCount failed: ' . $countResult->msg);
			return $this->terminateWithError($countResult->msg);
		}
		$rowCount = (int) getData($countResult)[0]->cnt;
		$this->logLib->logInfoDB('row count: ' . $rowCount . ' | memory: ' . memory_get_usage(true));

		// TODO: filename?
		$tempPath = sys_get_temp_dir() . '/lvevaluierung_export_' . uniqid() . '.xlsx';

		if ($rowCount <= 2000) {

			$result = $this->getExportData($studiensemester, $von, $bis);

			if (isError($result)) {
				return $this->terminateWithError($result);
			}

			$data = getData($result);
			$rows = $data['rows'];
			$headers = $data['headers'];
			$columnWidths = $data['columnWidths'];
			
			$this->exportViaPhpSpreadsheet($tempPath, $rows, $headers, $columnWidths);
		} else {
			$writer = WriterFactory::create(Type::XLSX);
			$writer->openToFile($tempPath);
			$wrapStyle = (new StyleBuilder())->setShouldWrapText(true)->build();
			$columnWidths = null;
			$rowsWritten = 0;

			$result = $this->streamExportRows(
				function ($headers, $widths) use ($writer, $wrapStyle, &$columnWidths) {
					$writer->addRow(array_values($headers), $wrapStyle);
					$columnWidths = $widths;
					$this->logLib->logInfoDB('header written, ' . count($headers) . ' columns');
				},
				function ($row) use ($writer, $wrapStyle, &$rowsWritten) {
					$writer->addRow(array_values((array) $row), $wrapStyle);
					$rowsWritten++;
					if ($rowsWritten % 15000 === 0) {
						$this->logLib->logInfoDB('rows written: ' . $rowsWritten . ' | memory: ' . memory_get_usage(true));
					}
				},
				$studiensemester, $von, $bis, 2000
			);

			$writer->close();
			$this->logLib->logInfoDB('stream finished, total rows: ' . $rowsWritten . ' | peak memory: ' . memory_get_peak_usage(true));

			if (isError($result)) {
				$this->logLib->logInfoDB('streamExportRows failed: ' . $result->msg);
				return $this->terminateWithError($result->msg);
			}
			if ($columnWidths) $this->applyXlsxColumnFormattingStreamed($tempPath, $columnWidths, 45);
		}

		if (!file_exists($tempPath) || filesize($tempPath) === 0) {
			$this->logLib->logInfoDB('export produced empty file at ' . $tempPath);
			return $this->terminateWithError('Export-Datei konnte nicht erstellt werden.');
		}

		$elapsed = round(microtime(true) - $startTime, 1);
		$this->logLib->logInfoDB('stream finished, total rows: ' . $rowsWritten . ' | elapsed: ' . $elapsed . 's | sem=' . $studiensemester . ' von=' . $von . ' bis=' . $bis);
		$this->terminateWithFileOutput('application/octet-stream', file_get_contents($tempPath), basename($tempPath));
	}

	public function getExportData($studiensemester = null, $von = null, $bis = null)
	{
		$baseResult = $this->LvevaluierungModel->getExportBaseRows($studiensemester, $von, $bis);
		if (!isSuccess($baseResult)) return $baseResult;
		$baseRows = getData($baseResult);

		if (empty($baseRows))
			return success(['rows' => [], 'headers' => $this->getFixedHeaders(), 'columnWidths' => []]);

		$fragebogenIds = array_values(array_unique(array_filter(array_column($baseRows, 'fragebogen_id'))));
		$codeIds = array_values(array_unique(array_filter(array_column($baseRows, 'lvevaluierung_code_id'))));

		$strukturResult = $this->LvevaluierungModel->getFragebogenStruktur($fragebogenIds);
		if (!isSuccess($strukturResult)) return $strukturResult;
		$struktur = getData($strukturResult);

		$antwortenResult = $this->LvevaluierungModel->getAntwortenByCodeIds($codeIds);
		if (!isSuccess($antwortenResult)) return $antwortenResult;
		$antwortenData = getData($antwortenResult);
		$antworten = $antwortenData ?? [];

		$rows = $this->pivotExportData($baseRows, $struktur, $antworten);
		$headers = array_merge($this->getFixedHeaders(), $this->buildFragebogenHeaders($struktur));
		$columnWidths = $this->buildColumnWidths(array_keys($headers), $struktur);

		return success(['rows' => $rows, 'headers' => $headers,'columnWidths' => $columnWidths,]);
	}
	
	public function streamExportRows(callable $onHeader, callable $onRow, $studiensemester, $von, $bis, $chunkSize = 2000)
	{
		$fragebogenResult = $this->LvevaluierungModel->getDistinctFragebogenIds($studiensemester, $von, $bis);
		if (!isSuccess($fragebogenResult)) return $fragebogenResult;
		$fragebogenIds = array_values(array_filter(array_column(getData($fragebogenResult), 'fragebogen_id')));

		$strukturResult = $this->LvevaluierungModel->getFragebogenStruktur($fragebogenIds);
		if (!isSuccess($strukturResult)) return $strukturResult;
		$struktur = getData($strukturResult);

		$headers = array_merge($this->getFixedHeaders(), $this->buildFragebogenHeaders($struktur));
		$columnWidths = $this->buildColumnWidths(array_keys($headers), $struktur);
		$onHeader($headers, $columnWidths);

		list($whereClause, $params) = $this->LvevaluierungModel->buildBaseWhereAndParams($studiensemester, $von, $bis);
		$baseSql = $this->LvevaluierungModel->buildBaseSql($whereClause);

		if (!isSuccess($this->LvevaluierungModel->execReadOnlyQuery('BEGIN')))
			return error('Could not start transaction', EXIT_DATABASE);

		$declareResult = $this->LvevaluierungModel->execReadOnlyQuery("DECLARE export_cursor NO SCROLL CURSOR FOR $baseSql", $params);
		if (!isSuccess($declareResult)) {
			$this->LvevaluierungModel->execReadOnlyQuery('ROLLBACK');
			return $declareResult;
		}

		while (true) {
			$chunkResult = $this->LvevaluierungModel->execReadOnlyQuery("FETCH FORWARD $chunkSize FROM export_cursor");
			if (!isSuccess($chunkResult)) {
				$this->LvevaluierungModel->execReadOnlyQuery('CLOSE export_cursor');
				$this->LvevaluierungModel->execReadOnlyQuery('ROLLBACK');
				return $chunkResult;
			}

			$chunkRows = getData($chunkResult);
			if (empty($chunkRows)) break;

			$codeIds = array_values(array_unique(array_filter(array_column($chunkRows, 'lvevaluierung_code_id'))));

			$antwortenResult = $this->LvevaluierungModel->getAntwortenByCodeIds($codeIds);
			if (!isSuccess($antwortenResult)) {
				$this->LvevaluierungModel->execReadOnlyQuery('CLOSE export_cursor');
				$this->LvevaluierungModel->execReadOnlyQuery('ROLLBACK');
				return $antwortenResult;
			}
			$antwortenData = getData($antwortenResult);
			$antworten = $antwortenData ?? [];
			
			$pivoted = $this->pivotExportData($chunkRows, $struktur, $antworten);
			foreach ($pivoted as $row) {
				$onRow($row);
			}
		}

		$this->LvevaluierungModel->execReadOnlyQuery('CLOSE export_cursor');
		$this->LvevaluierungModel->execReadOnlyQuery('COMMIT');

		return success(true);
	}


	/**
	 * Box/Spout 2.7 has no API for column width/row height (proposed in PR #715, never merged), 
	 * so this patches the generated .xlsx afterward by editing the OOXML xl/worksheets/sheet1.xml part directly 
	 * inside the zip. Two failures shaped the implementation: loading the full worksheet XML into one string blew
	 * memory at 330k+ rows (~600MB uncompressed), so the rewrite streams through a small chunked buffer instead.
	 * 
	 * This builds the result as a fully separate archive and only rename()s it over the original after
	 * confirming the rebuild succeeded and is non-empty. Failures anywhere in here are non-fatal by 
	 * design — formatting is cosmetic and should never break the download, and the original file is never opened
	 * in write mode, so it can't be corrupted by this function. Goes away entirely if we ever move off Spout 2.7
	 * to something with native width/height support.
	 */
	private function applyXlsxColumnFormattingStreamed($filePath, array $columnWidths, $dataRowHeight)
	{
		$sourceZip = new \ZipArchive();
		if ($sourceZip->open($filePath) !== true) return; // read-only open; original is never at risk

		$sheetPath = 'xl/worksheets/sheet1.xml';
		$stream = $sourceZip->getStream($sheetPath);
		if ($stream === false) { $sourceZip->close(); return; }

		$colsXml = '<cols>';
		foreach ($columnWidths as $index => $width) {
			$colNum = $index + 1;
			$colsXml .= sprintf('<col min="%d" max="%d" width="%s" customWidth="1"/>', $colNum, $colNum, $width);
		}
		$colsXml .= '</cols>';

		$tempXmlPath = $filePath . '.sheet1.tmp.xml';
		$out = fopen($tempXmlPath, 'wb');

		$chunkSize = 65536;
		$carry = '';
		$injected = false;

		while (!feof($stream)) {
			$chunk = fread($stream, $chunkSize);
			if ($chunk === false) break;
			$buffer = $carry . $chunk;

			if (!$injected) {
				if (preg_match('/<sheetFormatPr[^>]*\/>/', $buffer, $m, PREG_OFFSET_CAPTURE)) {
					$tag = $m[0][0];
					$pos = $m[0][1];
					$replacement = '<sheetFormatPr defaultRowHeight="' . $dataRowHeight . '" customHeight="1"/>' . $colsXml;
					$buffer = substr($buffer, 0, $pos) . $replacement . substr($buffer, $pos + strlen($tag));
					$injected = true;
				} elseif (preg_match('/<sheetData\b/', $buffer, $m, PREG_OFFSET_CAPTURE)) {
					$pos = $m[0][1];
					$buffer = substr($buffer, 0, $pos) . $colsXml . substr($buffer, $pos);
					$injected = true;
				}
			}

			if (!$injected) {
				$carry    = substr($buffer, -2048);
				$writable = substr($buffer, 0, -2048);
				if ($writable !== '') fwrite($out, $writable);
			} else {
				fwrite($out, $buffer);
				$carry = '';
			}
		}
		if ($carry !== '') fwrite($out, $carry);
		fclose($stream);
		fclose($out);

		if (!$injected) {
			unlink($tempXmlPath);
			$sourceZip->close();
			return; // unexpected file shape -- original untouched
		}

		$tempZipPath = $filePath . '.rebuild.tmp.xlsx';
		$newZip = new \ZipArchive();
		if ($newZip->open($tempZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
			$this->logLib->logInfoDB('zip rebuild open failed for ' . $tempZipPath);
			unlink($tempXmlPath);
			$sourceZip->close();
			return;
		}

		for ($i = 0; $i < $sourceZip->numFiles; $i++) {
			$name = $sourceZip->getNameIndex($i);
			if ($name === $sheetPath) {
				$newZip->addFile($tempXmlPath, $sheetPath);
			} else {
				$newZip->addFromString($name, $sourceZip->getFromName($name));
			}
		}

		$sourceZip->close();
		$closeOk = $newZip->close();
		unlink($tempXmlPath);

		if (!$closeOk || !file_exists($tempZipPath) || filesize($tempZipPath) === 0) {
			$this->logLib->logInfoDB('zip rebuild close failed or produced empty file for ' . $tempZipPath);
			if (file_exists($tempZipPath)) unlink($tempZipPath);
			return; // original at $filePath is still intact
		}

		rename($tempZipPath, $filePath); // same filesystem, effectively atomic
	}
	
	public function getExportRowCount()
	{
		$studiensemester = $this->input->get('studiensemester');
		$von = $this->input->get('von');
		$bis = $this->input->get('bis');


		$countResult = $this->LvevaluierungModel->getExportRowCount($studiensemester, $von, $bis);
		if (isError($countResult)) {
			$this->logLib->logInfoDB('getExportRowCount failed: ' . $countResult->msg);
			return $this->terminateWithError($countResult->msg);
		}
		$rowCount = (int)getData($countResult)[0]->cnt;

		$this->terminateWithSuccess($rowCount);
	}
	
	
}
