<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

use Box\Spout\Common\Type;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Common\Entity\Style\Color;

class Export extends FHCAPI_Controller
{
	public function __construct()
	{
		parent::__construct(array(
				'exportAllToExcel' => 'extension/lvevaluierung_export:rw',
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

		$this->_uid = getAuthUid();
	}

	public function exportAllToExcel() {
		$this->load->helper('download');

		$studiensemester = $this->input->post('studiensemester');
		$von = $this->input->post('von');
		$bis = $this->input->post('bis');

		$filenameParts = ['LV_Evaluierung_Rohdaten'];
		if (!empty($studiensemester)) $filenameParts[] = $studiensemester;
		if (!empty($von))             $filenameParts[] = 'von_' . $von;
		if (!empty($bis))             $filenameParts[] = 'bis_' . $bis;
		if (count($filenameParts) === 1) $filenameParts[] = 'gesamt';
		$filename = implode('_', $filenameParts) . '.xlsx';

		$writer = WriterFactory::create(Type::XLSX);
		$tempPath = "/var/fhcomplete/" . $filename;
		$writer->openToFile($tempPath);

		$headerStyle = (new StyleBuilder())
			->setFontBold()
			->setFontSize(11)
			->setFontColor('FFFFFF')
			->setBackgroundColor('34495E')
			->build();

		// Column order must match SELECT order in getExportData() exactly
		$headers = [
			'LV-Nummer',                                // lehrveranstaltung_id
			'LV-Name',                                  // lv_titel
			'LV-Name (Englisch)',                       // lv_titel_english
			'LV-Semester',                              // lv_semester
			'LV-Organisationsform',                     // lv_orgform
			'Studiengang',                              // studiengang
			'Studiengang-Typ',                          // studiengang_typ
			'zur Eval. ausgewählt',                     // zur_eval_ausgewaehlt
			'Gruppe / Gesamt',                          // gruppen_info
			// -- Evaluation metadata
			'Evaldatum eingetragen',                    // lv_leitung_hat_datum_eingetragen
			'Evaluierungszeitraum Start',               // startzeit
			'Evaluierungszeitraum Ende',                // endezeit
			'Linkversand Datum',                        // linkversand_datum
			'Linkversand durch',                        // linkversand_von
			// -- Per-code (per-student)
			'Code verwendet',                           // code_verwendet
			'LV-Evaluierung abgeschlossen',             // lv_eval_abgeschlossen
			'Durchführungsdatum',                       // durchfuehrungsdatum
			'Durchführung Startzeit',                   // code_startzeit
			'Durchführung Endzeit',                     // code_endzeit
			// -- Pflichtfragen
			'Pflichtfrage 1',                           // pflichtfrage_1
			'Pflichtfrage 2',                           // pflichtfrage_2
			// -- Optionale Bereiche
			'Optionale Bereiche angeklickt',            // optionale_bereiche_angeklickt
			'Organisation - Frage 1',                   // organisation_frage_1
			'Organisation - Frage 2',                   // organisation_frage_2
			'Moodle Kurs - Frage 1',                    // moodle_frage_1
			'Moodle Kurs - Frage 2',                    // moodle_frage_2
			'Moodle Kurs - Frage 3',                    // moodle_frage_3
			'Durchführung der LV - Frage 1',            // durchfuehrung_frage_1
			'Durchführung der LV - Frage 2',            // durchfuehrung_frage_2
			'Durchführung der LV - Frage 3',            // durchfuehrung_frage_3
			'Infrastruktur - Frage 1',                  // infrastruktur_frage_1
			'Infrastruktur - Frage 2',                  // infrastruktur_frage_2
			'Infrastruktur - Frage 3',                  // infrastruktur_frage_3
			// -- Freitextfragen
			'Freitextfrage 1',                          // freitext_1
			'Freitextfrage 2',                          // freitext_2
		];

		$writer->addRowWithStyle($headers, $headerStyle);

		$this->load->model('extensions/FHC-Core-Evaluierung/Lvevaluierung_model', 'LvevaluierungModel');
		$result = $this->LvevaluierungModel->getExportData($studiensemester, $von, $bis);
		$this->addMeta('path', $tempPath);

		if (isError($result)) {
			$writer->close();
			return $this->terminateWithError($result->msg);
		}

		$wrapStyle = (new StyleBuilder())
			->setShouldWrapText(true)
			->build();

		if (hasData($result)) {
			foreach ($result->retval as $row) {
				$writer->addRow(array_values((array) $row), $wrapStyle);
			}
		}

		$writer->close();

		if (file_exists($tempPath)) {
			$this->terminateWithFileOutput('application/octet-stream', file_get_contents($tempPath), basename($tempPath));
		}

		$this->terminateWithError('No file at ' . $tempPath);
	}

}
