<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Evaluation extends FHCAPI_Controller
{
	const BERECHTIGUNG_STG = 'extension/lvevaluierung_stg';
	public function __construct()
	{
		/** @noinspection PhpUndefinedClassConstantInspection */

		parent::__construct(array(
				'getEvaluationDataByLve' => 'extension/lvevaluierung_stg:r',
				'getEvaluationDataByLveLv' => 'extension/lvevaluierung_stg:r',
				'getAuswertungDataByLve' => 'extension/lvevaluierung_stg:r',
				'getAuswertungDataByLveLv' => 'extension/lvevaluierung_stg:r',
				'getTextantwortenByLve' => 'extension/lvevaluierung_stg:r',
				'getTextantwortenByLveLv' => 'extension/lvevaluierung_stg:r',
				'getReflexionDataByLve' => 'extension/lvevaluierung_stg:r',
				'getReflexionDataByLveLv' => 'extension/lvevaluierung_stg:r',
				'getEntitledStgs' => 'extension/lvevaluierung_stg:r',
				'getOrgformsByStg' => 'extension/lvevaluierung_stg:r',
				'getLvListByStg' => 'extension/lvevaluierung_stg:r',
				'updateVerpflichtend' => 'extension/lvevaluierung_stg:rw',
				'updateReviewedLvInStg' => 'extension/lvevaluierung_stg:rw',
			)
		);

		$this->load->library('extensions/FHC-Core-Evaluierung/EvaluationLib');

		$this->load->model('extensions/FHC-Core-Evaluierung/Lvevaluierung_model', 'LvevaluierungModel');
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungLehrveranstaltung_model', 'LvevaluierungLehrveranstaltungModel');
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungCode_model', 'LvevaluierungCodeModel');

		$this->_uid = getAuthUid();
		$this->_lvLeitungRequired = $this->config->item('lvLeitungRequired');
	}

	/**
	 * Get basic Evaluation data by LVE ID.
	 *
	 * @return void
	 */
	public function getEvaluationDataByLve()
	{
		$lvevaluierung_id = $this->input->get('lvevaluierung_id');

		$lve = $this->getLvevaluierungOrFail($lvevaluierung_id);
		$lveLv = $this->getLvevaluierungLehrveranstaltungOrFail($lve->lvevaluierung_lehrveranstaltung_id);
		$lvData = $this->evaluationlib->getLvData($lveLv->lehrveranstaltung_id, $lveLv->studiensemester_kurzbz);

		// LV-Leitungen, if required
		$lvLeitungen = [];
		if ($this->_lvLeitungRequired)
		{
			$result = $this->LehrveranstaltungModel->getLvLeitung($lveLv->lehrveranstaltung_id, $lveLv->studiensemester_kurzbz);
			$lvLeitungen = hasData($result) ? getData($result) : [];
		}

		// Lehrende
		if ($lveLv->lv_aufgeteilt && is_int($lve->lehreinheit_id))
		{
			$this->load->model('education/Lehreinheitmitarbeiter_model', 'LehreinheitmitarbeiterModel');
			$result = $this->LehreinheitmitarbeiterModel->getLektorenByLe($lve->lehreinheit_id);
		}
		else
		{
			$this->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');
			$result = $this->LehrveranstaltungModel->getLecturersByLv($lveLv->studiensemester_kurzbz, $lveLv->lehrveranstaltung_id);
		}
		$lehrende = hasData($result) ? getData($result) : [];

		// Abgeschickte Frageboegen, Ruecklaufquote
		$result = $this->LvevaluierungCodeModel->getAbgeschlosseneEvaluierungenByLve($lve->lvevaluierung_id);
		$submittedLveCodes = hasData($result) ? getData($result) : [];
		$countSubmitted = count($submittedLveCodes);
		$ruecklaufquote = null;
		if ($lve->codes_ausgegeben !== null && $lve->codes_ausgegeben > 0) {
			$ruecklaufquote = round(($countSubmitted / $lve->codes_ausgegeben) * 100, 2);
		}

		// For min/max duration
		$durations = $this->getDurations($submittedLveCodes);

		$data = array_merge(
			(array) $lveLv,
			(array) $lvData,
			['lvLeitungen' => $lvLeitungen],
			['lehrende' => $lehrende],
			['codes_ausgegeben' => $lve->codes_ausgegeben],
			['countSubmitted' => $countSubmitted],
			['ruecklaufquote' => $ruecklaufquote],
			['startzeit' => $lve->startzeit],
			['endezeit' => $lve->endezeit],
			['minDuration' => $durations ? min($durations) : 0],
			['maxDuration' => $durations ? max($durations) : 0]
		);

		$this->terminateWithSuccess($data);
	}

	/**
	 * Get basic Evaluation data by LVE-LV ID.
	 *
	 * @return void
	 */
	public function getEvaluationDataByLveLv()
	{
		$lvevaluierung_lehrveranstaltung_id = $this->input->get('lvevaluierung_lehrveranstaltung_id');

		$lveLv = $this->getLvevaluierungLehrveranstaltungOrFail($lvevaluierung_lehrveranstaltung_id);
		$lves = $this->getLvevaluierungByLveLvOrFail($lvevaluierung_lehrveranstaltung_id);
		$lvData = $this->evaluationlib->getLvData($lveLv->lehrveranstaltung_id, $lveLv->studiensemester_kurzbz);

		// LV-Leitungen, if required
		$lvLeitungen = [];
		if ($this->_lvLeitungRequired)
		{
			$result = $this->LehrveranstaltungModel->getLvLeitung($lveLv->lehrveranstaltung_id, $lveLv->studiensemester_kurzbz);
			$lvLeitungen = hasData($result) ? getData($result) : [];
		}

		// Lehrende
		$result = $this->LehrveranstaltungModel->getLecturersByLv($lveLv->studiensemester_kurzbz, $lveLv->lehrveranstaltung_id);
		$lehrende = hasData($result) ? getData($result) : [];

		// Abgeschickte Frageboegen, Ruecklaufquote
		$submittedLveCodes = $this->getAbgeschlosseneEvaluierungenByLveLv($lvevaluierung_lehrveranstaltung_id);
		$countSubmitted = count($submittedLveCodes);
		$codesAusgegeben = (array_sum(array_column($lves, 'codes_ausgegeben')));
		$ruecklaufquote = null;
		if ($codesAusgegeben !== null && $codesAusgegeben > 0) {
			$ruecklaufquote = round(($countSubmitted / $codesAusgegeben) * 100, 2);
		}

		// For min/max duration
		$durations = $this->getDurations($submittedLveCodes);

		// Min startzeit / max endezeit overall Evaluierungen
		$periodTimes = $this->getPeriodTimes($lves);

		$data = array_merge(
			(array) $lveLv,
			(array) $lvData,
			['lvLeitungen' => $lvLeitungen],
			['lehrende' => $lehrende],
			['codes_ausgegeben' => $codesAusgegeben],
			['countSubmitted' => $countSubmitted],
			['ruecklaufquote' => $ruecklaufquote],
			['startzeit' => $periodTimes['minStartzeit']],
			['endezeit' => $periodTimes['maxEndezeit']],
			['minDuration' => $durations ? min($durations) : 0],
			['maxDuration' => $durations ? max($durations) : 0]
		);

		$this->terminateWithSuccess($data);
	}

	/**
	 * Fetch evaluation data for a given LVE ID.
	 * Structured into Gruppe > Frage > Antwort and calculates the interpolated median for each answer.
	 *
	 * @return void
	 */
	public function getAuswertungDataByLve()
	{
		$lvevaluierung_id = $this->input->get('lvevaluierung_id');

		// Return if Evaluation period is still running
		$lve = $this->getLvevaluierungOrFail($lvevaluierung_id);
		$now = (new DateTime())->format('Y-m-d H:i:s');

		if ($now < $lve->endezeit) {
			$this->terminateWithSuccess([]);
		}

		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogenGruppe_model', 'LvevaluierungFragebogenGruppeModel');
		$result = $this->LvevaluierungFragebogenGruppeModel->getAuswertungDataByLve($lvevaluierung_id);
		$data = $this->getDataOrTerminateWithError($result);

		// Re-structure data
		$auswertungData = $this->mapAuswertungData($data);

		// Calculate interpolierten Median for each Antwort
		foreach ($auswertungData as &$gruppe) {
			foreach ($gruppe['fbFragen'] as &$frage) {
				$werte = $frage['antworten']['werte'];
				$frequencies = $frage['antworten']['frequencies'];
				$frage['antworten']['iMedian']['actYear'] = $this->evaluationlib->getInterpolMedian($werte, $frequencies);
				$frage['antworten']['hodgesLehmann']['actYear'] = $this->evaluationlib->getHodgesLehmannEstimator($werte, $frequencies);
			}
		}

		$this->terminateWithSuccess($auswertungData);
	}

	/**
	 * Fetch evaluation data for a given LVE-LV ID. (Which aggregates all belonging LVE Evaluation data)
	 * Structured into Gruppe > Frage > Antwort and calculates the interpolated median for each answer.
	 *
	 * @return void
	 */
	public function getAuswertungDataByLveLv()
	{
		$lvevaluierung_lehrveranstaltung_id = $this->input->get('lvevaluierung_lehrveranstaltung_id');

		// Return if Evaluation period is still running
		$lves = $this->getLvevaluierungByLveLvOrFail($lvevaluierung_lehrveranstaltung_id);
		$periodTimes = $this->getPeriodTimes($lves);
		$now = (new DateTime())->format('Y-m-d H:i:s');

		if ($now < $periodTimes['maxEndezeit']) {
			$this->terminateWithSuccess([]);
		}

		// Get Auswertungdata
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogenGruppe_model', 'LvevaluierungFragebogenGruppeModel');
		$result = $this->LvevaluierungFragebogenGruppeModel->getAuswertungDataByLveLv($lvevaluierung_lehrveranstaltung_id);
		$data = $this->getDataOrTerminateWithError($result);

		// Re-structure data
		$auswertungData = $this->mapAuswertungData($data);

		// Calculate interpolierten Median for each Antwort
		foreach ($auswertungData as &$gruppe) {
			foreach ($gruppe['fbFragen'] as &$frage) {
				$werte = $frage['antworten']['werte'];
				$frequencies = $frage['antworten']['frequencies'];
				$frage['antworten']['iMedian']['actYear'] = $this->evaluationlib->getInterpolMedian($werte, $frequencies);
				$frage['antworten']['hodgesLehmann']['actYear'] = $this->evaluationlib->getHodgesLehmannEstimator($werte, $frequencies);
			}
		}

		$this->terminateWithSuccess($auswertungData);
	}

	/**
	 * Fetch Textantworten by LVE ID.
	 *
	 * @return void
	 */
	public function getTextantwortenByLve()
	{
		$lvevaluierung_id = $this->input->get('lvevaluierung_id');

		// Return if Evaluation period is still running
		$lve = $this->getLvevaluierungOrFail($lvevaluierung_id);
		$now = (new DateTime())->format('Y-m-d H:i:s');

		if ($now < $lve->endezeit) {
			$this->terminateWithSuccess([]);
		}

		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungAntwort_model', 'LvevaluierungAntwortModel');
		$result = $this->LvevaluierungAntwortModel->getTextantwortenByLve($lvevaluierung_id);
		$data = $this->getDataOrTerminateWithError($result);

		$textantworten = $this->mapTextantworten($data);

		$this->terminateWithSuccess($textantworten);
	}

	/**
	 * Fetch Textantworten by LVE-LV ID.
	 *
	 * @return void
	 */
	public function getTextantwortenByLveLv()
	{
		$lvevaluierung_lehrveranstaltung_id = $this->input->get('lvevaluierung_lehrveranstaltung_id');

		// Return if Evaluation period is still running
		$lves = $this->getLvevaluierungByLveLvOrFail($lvevaluierung_lehrveranstaltung_id);
		$periodTimes = $this->getPeriodTimes($lves);
		$now = (new DateTime())->format('Y-m-d H:i:s');

		if ($now < $periodTimes['maxEndezeit']) {
			$this->terminateWithSuccess([]);
		}

		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungAntwort_model', 'LvevaluierungAntwortModel');
		$result = $this->LvevaluierungAntwortModel->getTextantwortenByLveLv($lvevaluierung_lehrveranstaltung_id);
		$data = $this->getDataOrTerminateWithError($result);

		$textantworten = $this->mapTextantworten($data);

		$this->terminateWithSuccess($textantworten);
	}

	public function getReflexionDataByLve()
	{
		$lvevaluierung_id = $this->input->get('lvevaluierung_id');

		// Return if Evaluation period is still running
		$lve = $this->getLvevaluierungOrFail($lvevaluierung_id);
		$now = (new DateTime())->format('Y-m-d H:i:s');

		if ($now < $lve->endezeit) {
			$this->terminateWithSuccess([]);
		}
	}

	public function getReflexionDataLveLv()
	{
		$lvevaluierung_lehrveranstaltung_id = $this->input->get('lvevaluierung_lehrveranstaltung_id');

		// Return if Evaluation period is still running
		$lves = $this->getLvevaluierungByLveLvOrFail($lvevaluierung_lehrveranstaltung_id);
		$periodTimes = $this->getPeriodTimes($lves);
		$now = (new DateTime())->format('Y-m-d H:i:s');

		if ($now < $periodTimes['maxEndezeit']) {
			$this->terminateWithSuccess([]);
		}
	}

	//------------------------------------------------------------------------------------------------------------------
	// Evaluation Studiengaenge
	//------------------------------------------------------------------------------------------------------------------
	/**
	 * Get Studiengaenge by given Studiensemester for which the user is entitled.
	 *
	 * @return void
	 */
	public function getEntitledStgs()
	{
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');

		$this->load->model('organisation/Studiengang_model', 'StudiengangModel');
		$entitledStgs = $this->permissionlib->getSTG_isEntitledFor(self::BERECHTIGUNG_STG) ?: [];
		$result = $this->StudiengangModel->getByStgs($entitledStgs, $studiensemester_kurzbz);
		$stgs = $this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($stgs);
	}

	/**
	 * Get Orgforms by given Studiengang and Studiensemester.
	 *
	 * @return void
	 */
	public function getOrgformsByStg()
	{
		$studiengang_kz = $this->input->get('studiengang_kz');
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');

		$this->load->model('organisation/Studiengang_model', 'StudiengangModel');
		$result = $this->StudiengangModel->getOrgformsByStg($studiengang_kz, $studiensemester_kurzbz);
		$orgforms = $this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($orgforms);
	}

	/**
	 * Get Lv-List Data of all Lvs that shall be evaluated in given Studiensemester and Studiengang.
	 * (from Lvevaluierung-Lehrveranstaltung table)
	 *
	 * @return void
	 */
	public function getLvListByStg()
	{
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');
		$studiengang_kz = $this->input->get('studiengang_kz');
		$orgform_kurzbz = $this->input->get('orgform_kurzbz');

		// Permission check
		$entitledStgs = $this->permissionlib->getSTG_isEntitledFor(self::BERECHTIGUNG_STG) ?: [];
		if ($studiengang_kz && !in_array($studiengang_kz, $entitledStgs)) $this->terminateWithError('Permission denied');

		// Get LV List
		$result = $this->LvevaluierungLehrveranstaltungModel->getLveLvsByStg(
			$studiensemester_kurzbz,
			$studiengang_kz,
			$orgform_kurzbz
		);
		$data = $this->getDataOrTerminateWithError($result);

		// Get Ruecklauf data
		$lveLvIds = array_column($data, 'lvevaluierung_lehrveranstaltung_id');
		$result = $this->LvevaluierungCodeModel->getAggregatedRuecklaufDataByLveLv($lveLvIds);
		$rlData = hasData($result) ? getData($result) : [];

		// Add Ruecklauf values to data
		foreach ($data as $item) {
			$lveLvId = $item->lvevaluierung_lehrveranstaltung_id;
			$agg = current(array_filter($rlData, function($r) use ($lveLvId) {
				return $r->lvevaluierung_lehrveranstaltung_id === $lveLvId;
			}));
			$item->codesAusgegeben = $agg ? $agg->sum_codes_ausgegeben : 0;
			$item->submittedCodes = $agg ? $agg->count_submitted_codes : 0;
			$item->ruecklaufQuote = ($agg && $agg->ruecklaufquote !== null)
				? (float) $agg->ruecklaufquote
				: null;
		}

		$this->terminateWithSuccess($data);
	}

	/**
	 * Update verpflichtende Evaluierungen for given Lehrveranstaltungen.
	 *
	 * @return void
	 */
	public function updateVerpflichtend(){
		$lvevaluierung_lehrveranstaltung_id = $this->input->post('lvevaluierung_lehrveranstaltung_id');
		$isVerpflichtend = $this->input->post('isVerpflichtend');

		$result = $this->LvevaluierungLehrveranstaltungModel->update(
			$lvevaluierung_lehrveranstaltung_id,
			['verpflichtend' => $isVerpflichtend]
		);
		$data = $this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($data);
	}

	/**
	 * Update verpflichtende Evaluierungen for given Lehrveranstaltungen.
	 *
	 * @return void
	 */
	public function updateReviewedLvInStg(){
		$lvevaluierung_lehrveranstaltung_id = $this->input->post('lvevaluierung_lehrveranstaltung_id');
		$isReviewed = $this->input->post('isReviewed');

		$result = $this->LvevaluierungLehrveranstaltungModel->update(
			$lvevaluierung_lehrveranstaltung_id,
			['reviewed_stg' => $isReviewed]
		);
		$data = $this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($data);
	}

	// -----------------------------------------------------------------------------------------------------------------
	// Helper methods
	// -----------------------------------------------------------------------------------------------------------------
	/**
	 * Calculate all durations in minutes.
	 *
	 * @param $lveCodes
	 * @return float[]|int[]
	 */
	public function getDurations($lveCodes)
	{
		$durations = array_map(function($item) {
			$start = new DateTime($item->startzeit);
			$end   = new DateTime($item->endezeit);

			// duration in minutes
			return round(($end->getTimestamp() - $start->getTimestamp()) / 60, 2);
		}, $lveCodes);

		return $durations;
	}
	public function getPeriodTimes($lves)
	{
		$startTimes = array_column($lves, 'startzeit');
		$endTimes = array_column($lves, 'endezeit');

		return [
			'minStartzeit' => $startTimes ? min($startTimes) : null,
			'maxEndezeit'   => $endTimes ? max($endTimes) : null,
		];
	}

	/**
	 * Converts flat SQL rows into nested structure: Gruppe > Frage > Antworten.
	 * Collects each Antwort's values and their selection frequencies.
	 *
	 * @param $data
	 * @return array
	 */
	public function mapAuswertungData($data)
	{
		$fbGruppen = [];

		foreach ($data as $item)
		{
			$fbGruppeId = $item->lvevaluierung_fragebogen_gruppe_id;
			$frageId = $item->lvevaluierung_frage_id;

			// Create group if not exists
			if (!isset($fbGruppen[$fbGruppeId])) {
				$fbGruppen[$fbGruppeId] = [
					'lvevaluierung_fragebogen_gruppe_id' => $fbGruppeId,
					'bezeichnung' => $item->fbGruppenBezeichnung,
					'typ' => $item->fbGruppenTyp,
					'sort' => $item->fbGruppenSort,
					'fbFragen' => []
				];
			}

			// Create question if not exists
			if (!isset($fbGruppen[$fbGruppeId]['fbFragen'][$frageId])) {
				$fbGruppen[$fbGruppeId]['fbFragen'][$frageId] = [
					'lvevaluierung_frage_id' => $frageId,
					'bezeichnung' => $item->fbFrageBezeichnung,
					'typ' => $item->fbFrageTyp,
					'sort' => $item->fbFrageSort,
					'antworten' => [
						'werte' => [],
						'frequencies' => [],
						'bezeichnungen' => [],
						'iMedian' => [
							'actYear' => 0,
							'actYearMin1' => 0,
							'actYearMin2' => 0,
						],	// default
						'hodgesLehmann' => [
							'actYear' => 0,
							'actYearMin1' => 0,
							'actYearMin2' => 0,
						]	// default
					]
				];
			}

			// Push antworten values
			$fbGruppen[$fbGruppeId]['fbFragen'][$frageId]['antworten']['werte'][] = $item->wert;
			$fbGruppen[$fbGruppeId]['fbFragen'][$frageId]['antworten']['frequencies'][] = $item->frequency;
			$fbGruppen[$fbGruppeId]['fbFragen'][$frageId]['antworten']['bezeichnungen'][] = $item->fbFrageAntwortBezeichnung;
		}

		// Re-index arrays
		$fbGruppen = array_map(function($gruppe) {
			$gruppe['fbFragen'] = array_values($gruppe['fbFragen']);
			return $gruppe;
		}, array_values($fbGruppen));

		return $fbGruppen;
	}

	public function mapTextantworten($data)
	{
		$textantworten = [];

		foreach($data as $item)
		{
			$frageId = $item->lvevaluierung_frage_id;

			// Create group if not exists
			if (!isset($textantworten[$frageId])) {
				$textantworten[$frageId] = [
					'lvevaluierung_frage_id' => $frageId,
					'bezeichnung' => $item->fbFrageBezeichnung,
					'sort' => $item->fbFrageSort,
					'antworten' => []
				];
			}

			// Add answer
			$textantworten[$frageId]['antworten'][] = [
				'lvevaluierung_antwort_id' => $item->lvevaluierung_antwort_id,
				'antwort' => $item->antwort
			];
		}

		// Re-index array
		$textantworten = array_values($textantworten);

		return $textantworten;
	}
	/**
	 * Get Lvevaluierung.
	 * Terminate with error on fail.
	 *
	 * @param $lvevaluierung_id
	 * @return mixed
	 */
	public function getLvevaluierungOrFail($lvevaluierung_id)
	{
		$result = $this->LvevaluierungModel->load($lvevaluierung_id);

		if (isError($result))
		{
			$this->terminateWithError($result);
		}

		return getData($result)[0];
	}
	/**
	 * Get Lvevaluierung by Lvevaluierung Lehrveranstaltung ID.
	 * Terminate with error on fail.
	 *
	 * @param $lvevaluierung_id
	 * @return mixed
	 */
	public function getLvevaluierungByLveLvOrFail($lvevaluierung_lehrveranstaltung_id)
	{
		$result = $this->LvevaluierungModel->loadWhere([
			'lvevaluierung_lehrveranstaltung_id' => $lvevaluierung_lehrveranstaltung_id
		]);

		if (isError($result))
		{
			$this->terminateWithError($result);
		}

		return hasData($result) ? getData($result) : [];
	}
	/**
	 * Get Lvevaluierung Lehrveranstaltung.
	 * Terminate with error on fail.
	 *
	 * @param $lvevaluierung_lehrveranstaltung_id
	 * @return mixed
	 */
	public function getLvevaluierungLehrveranstaltungOrFail($lvevaluierung_lehrveranstaltung_id)
	{
		$result = $this->LvevaluierungLehrveranstaltungModel->load($lvevaluierung_lehrveranstaltung_id);

		if (isError($result))
		{
			$this->terminateWithError(getError($result));
		}

		if (!hasData($result))
		{
			$this->terminateWithError('No Evaluierung assigned to this Lehrveranstaltung');
		}

		return getData($result)[0];
	}
	/**
	 * Get submitted Evaluierungen of given LV. (= Student submitted Evaluierung)
	 * @param $lvevaluierung_lehrveranstaltung_id
	 * @return array
	 */
	public function getAbgeschlosseneEvaluierungenByLveLv($lvevaluierung_lehrveranstaltung_id)
	{
		$result = $this->LvevaluierungCodeModel->getAbgeschlosseneEvaluierungenByLveLv($lvevaluierung_lehrveranstaltung_id);

		if(isError($result))
		{
			$this->terminateWithError(getError($result));
		}

		return hasData($result) ? getData($result) : [];
	}
}
