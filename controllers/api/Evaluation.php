<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Evaluation extends FHCAPI_Controller
{
	const BERECHTIGUNG_STG = 'extension/lvevaluierung_stg';
	public function __construct()
	{
		/** @noinspection PhpUndefinedClassConstantInspection */

		parent::__construct(array(
				'getEvaluationDataByLve' => array('extension/lvevaluierung_stg:r','extension/lvevaluierung_init:r'),
				'getEvaluationDataByLveLv' => array('extension/lvevaluierung_stg:r','extension/lvevaluierung_init:r'),
				'getAuswertungDataByLve' => array('extension/lvevaluierung_stg:r','extension/lvevaluierung_init:r'),
				'getAuswertungDataByLveLv' => array('extension/lvevaluierung_stg:r','extension/lvevaluierung_init:r'),
				'getTextantwortenByLve' => array('extension/lvevaluierung_stg:r','extension/lvevaluierung_init:r'),
				'getTextantwortenByLveLv' => array('extension/lvevaluierung_stg:r','extension/lvevaluierung_init:r'),
				'getReflexionDataByLve' => array('extension/lvevaluierung_stg:r','extension/lvevaluierung_init:r'),
				'getReflexionDataByLveLv' => array('extension/lvevaluierung_stg:r','extension/lvevaluierung_init:r'),
				'saveOrUpdateReflexion' => 'extension/lvevaluierung_init:r',
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
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungZeitfenster_model', 'LvevaluierungZeitfensterModel');
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungReflexion_model', 'LvevaluierungReflexionModel');
		$this->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');
		$this->load->model('education/Lehreinheitmitarbeiter_model', 'LehreinheitmitarbeiterModel');

		$this->_uid = getAuthUid();
		$this->_lvLeitungRequired = $this->config->item('lvLeitungRequired');
		$this->_reflexionZeitfensterDauer = $this->config->item('reflexionZeitfensterDauer');
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

		// Permission check
		$isKfl = $this->evaluationlib->isKFL($this->_uid, $lveLv->lehrveranstaltung_id);
		$isStgl = $this->evaluationlib->isSTGL($this->_uid, $lveLv->lehrveranstaltung_id);

		// LV-Leitungen, if required
		$lvLeitungen = [];
		if ($this->_lvLeitungRequired)
		{
			$result = $this->LehrveranstaltungModel->getLvLeitung($lveLv->lehrveranstaltung_id, $lveLv->studiensemester_kurzbz);
			$lvLeitungen = hasData($result) ? getData($result) : [];
		}

		// Lehrende
		$lehrende = $this->evaluationlib->getLehrendeByLve($lve, $lveLv, null, true);

//		$this->addMeta('this uid', $this->_uid);
//		$this->addMeta('$lvLeitungen', $lvLeitungen);
//		$this->addMeta('$lehrende', $lehrende);
//		$this->addMeta('$isKfl', $isKfl);
//		$this->addMeta('$isStgl', $isStgl);

		if (
			in_array($this->_uid, array_column($lvLeitungen, 'mitarbeiter_uid')) ||
			in_array($this->_uid, array_column($lehrende, 'uid')) ||
			$isKfl ||
			$isStgl
		) {

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

			// Evaluation open check
			$isEvaluationViewOpen = $this->isEvaluationViewOpen($lve);
			$isEvaluationViewOpenMsg = null;

			if (!$isEvaluationViewOpen) {
				$lektorOfLve = $this->evaluationlib->getLehrendeByLve($lve, $lveLv);
				$isLektorOfLve = in_array($this->_uid, array_column($lektorOfLve, 'uid'));

				$isEvaluationViewOpenMsg = (($isKfl || $isStgl) && !$isLektorOfLve)
					? 'Keine Daten vorhanden oder LV-Reflexionszeitraum noch nicht abgeschlossen.'
					: 'Keine Daten vorhanden oder Evaluierungszeitfenster noch nicht abgeschlossen.';
			}

			$data = array_merge(
				(array)$lveLv,
				(array)$lvData,
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

			$response = [
				'data' => $data,
				'evaluationView' => [
					'open' => $isEvaluationViewOpen,
					'msg'  => $isEvaluationViewOpenMsg,
				],
			];

			$this->terminateWithSuccess($response);
		}
		else
		{
			$response = [
				'data' => null,
				'evaluationView' => [
					'open' => false,
					'msg' => 'Keine Berechtigung zur Ansicht dieser Evaluation'
				],
			];

			$this->terminateWithSuccess($response);
		}
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

		// Permission check
		$isKfl = $this->evaluationlib->isKFL($this->_uid, $lveLv->lehrveranstaltung_id);
		$isStgl = $this->evaluationlib->isSTGL($this->_uid, $lveLv->lehrveranstaltung_id);

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

//		$this->addMeta('this uid', $this->_uid);
//		$this->addMeta('$lvLeitungen', $lvLeitungen);
//		$this->addMeta('$lehrende', $lehrende);
//		$this->addMeta('$isKfl', $isKfl);
//		$this->addMeta('$isStgl', $isStgl);

		if (
			in_array($this->_uid, array_column($lvLeitungen, 'mitarbeiter_uid')) ||
			in_array($this->_uid, array_column($lehrende, 'uid')) ||
			$isKfl ||
			$isStgl
		) {

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

			// Evaluation open check
			$isEvaluationViewOpen = true;
			$now = new DateTime();

			if (empty($lves)) {
				$isEvaluationViewOpen = false;
			}

			// Prüfe: irgendeine LVE hat keine Codes oder kein Endezeit
			foreach ($lves as $lve) {
				if ($lve->codes_ausgegeben === null || $lve->codes_ausgegeben === 0 || $lve->endezeit === null) {
					$isEvaluationViewOpen = false;
					break;
				}
			}

			// Prüfe: irgendeine Evaluierungsperiode ist noch nicht abgeschlossen
			$maxEndezeit = new DateTime($periodTimes['maxEndezeit']);
			if ($isEvaluationViewOpen && $now < $maxEndezeit) {
				$isEvaluationViewOpen = false;
			}

			if (($isKfl || $isStgl) && !in_array($this->_uid, array_column($lehrende, 'mitarbeiter_uid'))) {
				$isReflexionszeitRaumAbgeschlossen = $this->LvevaluierungReflexionModel->isReflexionszeitraumAbgeschlossenForAllLvesInLveLv($lvevaluierung_lehrveranstaltung_id);
				if (!$isReflexionszeitRaumAbgeschlossen) {
					$isEvaluationViewOpen = false;
				}
			}
			// Evaluation Open Message
			$isEvaluationViewOpenMsg = null;
			if (!$isEvaluationViewOpen) {
				$isEvaluationViewOpenMsg = ($isKfl || $isStgl)
					? 'Keine Daten vorhanden oder LV-Reflexionszeitraum noch nicht abgeschlossen.'
					: 'Keine Daten vorhanden oder Evaluierungszeitfenster noch nicht abgeschlossen.';
			}

			$data = array_merge(
				(array)$lveLv,
				(array)$lvData,
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

			$response = [
				'data' => $data,
				'evaluationView' => [
					'open' => $isEvaluationViewOpen,
					'msg'  => $isEvaluationViewOpenMsg,
				],
			];

			$this->terminateWithSuccess($response);
		}
		else
		{
			$response = [
				'data' => null,
				'evaluationView' => [
					'open' => false,
					'msg' => 'Keine Berechtigung zur Ansicht dieser Evaluation'
				],
			];

			$this->terminateWithSuccess($response);
		}
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

		$lve = $this->getLvevaluierungOrFail($lvevaluierung_id);

		// Return if Evaluation not accessible
		if (!$this->isEvaluationViewOpen($lve))
		{
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

		$lves = $this->getLvevaluierungByLveLvOrFail($lvevaluierung_lehrveranstaltung_id);

		// Return if Evaluation period is still running
		$periodTimes = $this->getPeriodTimes($lves);
		$now = (new DateTime())->format('Y-m-d H:i:s');
		if ($now < $periodTimes['maxEndezeit'])
		{
			$this->terminateWithSuccess([]);
		}

		// Return if students did not get codes yet
		$codes = array_column($lves, 'codes_ausgegeben');
		if (in_array(null, $codes, true)) {
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

	// -----------------------------------------------------------------------------------------------------------------
	// LV-REFLEXION
	// -----------------------------------------------------------------------------------------------------------------
	public function getReflexionDataByLve()
	{
		$lvevaluierung_id = $this->input->get('lvevaluierung_id');

		$lve = $this->getLvevaluierungOrFail($lvevaluierung_id);
		$lveLv = $this->getLvevaluierungLehrveranstaltungOrFail($lve->lvevaluierung_lehrveranstaltung_id);

		$isKfl = $this->evaluationlib->isKFL($this->_uid, $lveLv->lehrveranstaltung_id);
		$isStgl = $this->evaluationlib->isSTGL($this->_uid, $lveLv->lehrveranstaltung_id);
		$result = $this->LehrveranstaltungModel->getLvLeitung(
			$lveLv->lehrveranstaltung_id,
			$lveLv->studiensemester_kurzbz
		);
		$lvLeitung = hasData($result) ? getData($result)[0] : null;

		// Skip if Evaluation period is still running or students did not get codes yet
		if (!$this->isEvaluationViewOpen($lve))
		{
			$this->terminateWithSuccess([]);
		}

		// Build Reflexionen by Lehrende
		$reflexionen = $this->buildReflexionenByLehrendeOfLve($lve, $lveLv, $lvLeitung);

		// Filter Reflexionen (nicht alle dürfen alle Reflexionen sehen)
		$filteredReflexionen = $this->filterReflexionenByPermission(
			$reflexionen,
			$lveLv,
			$lvLeitung->mitarbeiter_uid
		);

		$checkedReflexionen = $this->addZeitfensterAndBearbeitungsChecks(
			$filteredReflexionen,
			$lve,
			$lveLv,
			$isKfl,
			$isStgl,
			$lvLeitung->mitarbeiter_uid
		);

		$this->terminateWithSuccess($checkedReflexionen);
	}
	public function getReflexionDataByLveLv()
	{
		$lvevaluierung_lehrveranstaltung_id = $this->input->get('lvevaluierung_lehrveranstaltung_id');

		$lveLv = $this->getLvevaluierungLehrveranstaltungOrFail($lvevaluierung_lehrveranstaltung_id);
		$lves = $this->getLvevaluierungByLveLvOrFail($lvevaluierung_lehrveranstaltung_id);

		$isKfl = $this->evaluationlib->isKFL($this->_uid, $lveLv->lehrveranstaltung_id);
		$isStgl = $this->evaluationlib->isSTGL($this->_uid, $lveLv->lehrveranstaltung_id);
		$result = $this->LehrveranstaltungModel->getLvLeitung(
			$lveLv->lehrveranstaltung_id,
			$lveLv->studiensemester_kurzbz
		);
		$lvLeitung = hasData($result) ? getData($result)[0] : null;

		$reflexionenByLveLv = [];
		foreach ($lves as $lve)
		{
			// Skip if Evaluation period is still running or students did not get codes yet
			if (!$this->isEvaluationViewOpen($lve))
			{
				continue;
			}

			// Build Reflexionen
			$reflexionen = $this->buildReflexionenByLehrendeOfLve($lve, $lveLv, $lvLeitung);

			$checkedReflexionen = $this->addZeitfensterAndBearbeitungsChecks(
				$reflexionen,
				$lve,
				$lveLv,
				$isKfl,
				$isStgl,
				$lvLeitung->mitarbeiter_uid
			);

			$reflexionenByLveLv = array_merge(
				$reflexionenByLveLv,
				$checkedReflexionen
			);
		}

		$this->terminateWithSuccess($reflexionenByLveLv);
	}
	public function saveOrUpdateReflexion()
	{
		$lvevaluierung_reflexion_id = $this->input->post('lvevaluierung_reflexion_id');
		$lvevaluierung_id = $this->input->post('lvevaluierung_id');
		$mitarbeiterUid = $this->input->post('mitarbeiter_uid');
		$data = $this->input->post('data');

		$lve = $this->getLvevaluierungOrFail($lvevaluierung_id);
		$lveLv = $this->getLvevaluierungLehrveranstaltungOrFail($lve->lvevaluierung_lehrveranstaltung_id);

		$data['verpflichtend'] = $this->isReflexionVerpflichtendForUid($lveLv, $this->_uid);

		if ($data['mitarbeiter_uid'] !== null &&
			($mitarbeiterUid != $data['mitarbeiter_uid'] || $mitarbeiterUid != $this->_uid)
		)
		{
			$this->terminateWithError('Nicht berechtigt zum Speichern oder Ändern dieser LV-Reflexion');
		}


		$lektorOfLve = $this->evaluationlib->getLehrendeByLve($lve, $lveLv, null, true);
		if (!in_array($this->_uid, array_column($lektorOfLve, 'uid')) ||
			$mitarbeiterUid != $this->_uid)
		{
			$this->terminateWithError('Nicht berechtigt zum Speichern oder Ändern dieser LV-Reflexion');
		}

		// Alternativ sicher gehen, ob Reflexion existiert
		if (!$lvevaluierung_reflexion_id)
		{
			$result = $this->LvevaluierungReflexionModel->loadWhere([
				'lvevaluierung_id' => $lvevaluierung_id,
				'mitarbeiter_uid' => $this->_uid
			]);

			if (hasData($result))
			{
				$lvevaluierung_reflexion_id = getData($result)[0]->lvevaluierung_reflexion_id;
			}
		}

		// Insert / Update Reflexion
		if (!$lvevaluierung_reflexion_id)
		{
			unset($data['lvevaluierung_reflexion_id']);
			$data['lvevaluierung_id'] = $lvevaluierung_id;
			$data['mitarbeiter_uid'] = $this->_uid;
			$data['insertvon'] = $this->_uid;

			// Insert
			$result = $this->LvevaluierungReflexionModel->insert($data);
		}
		else
		{
			$result = $this->LvevaluierungReflexionModel->load($lvevaluierung_reflexion_id);
			$reflexion = hasData($result) ? getData($result)[0] : null;

			// Update only if user is owner of Reflexion
			if ($reflexion && $reflexion->mitarbeiter_uid === $this->_uid)
			{
				// todo: refactor code. now workaround: aktuell wird beim speichern das $data object nicht im frontend aktualisiert.
				// --> wenn direkt nach dem speichern (ohne reload) die Reflexion geändert wird (update), dann werden
				// hier null values übergeben.----------------
				unset($data['lvevaluierung_reflexion_id']);
				$data['lvevaluierung_id'] = $lvevaluierung_id;
				$data['mitarbeiter_uid'] = $this->_uid;
				// -------------------------------------------
				$data['updatevon'] = $this->_uid;
				$data['updateamum'] = 'NOW()';

				$result = $this->LvevaluierungReflexionModel->update(
					$reflexion->lvevaluierung_reflexion_id,
					$data
				);
			}
			else
			{
				$this->terminateWithError('Keine Berechtigung für diese LV-Reflexion');
			}
		}

		$data = $this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($data[0]);
	}
	private function checkBearbeitungOffenForReflexion($lve)
	{
		if (!$this->_reflexionZeitfensterDauer)
		{
			$this->terminateWithError('Missing config entry zeitfensterDauerReflexion');
		}

		// Get Start- and Endedatum of Reflexionszeitraum
		$zeitfenster = $this->evaluationlib->calculateReflexionZeitfenster($lve->endezeit);

		$sperreGrund = [];

		// Zeitfenster offen
		if ($this->evaluationlib->isZeitfensterOffen($zeitfenster['von'], $zeitfenster['bis']) === false)
		{
			$sperreGrund[] = 'Nicht im Bearbeitungszeitraum';
		}

		// Bearbeitung sperren, wenn es einen Fehler gibt
		$isBearbeitungOffen = empty($sperreGrund);

		return [
			'isBearbeitungOffen' => $isBearbeitungOffen,
			'sperreGrund' => $sperreGrund,
			'zeitfensterVon' => $zeitfenster['von']->format('d.m.Y'),
			'zeitfensterBis' => $zeitfenster['bis']->format('d.m.Y')
		];
	}
	private function isReflexionVerpflichtendForUid($lveLv, $uid)
	{
		$verpflichtend = true;

		// If Gesamt-LV Evaluierung
		if ($lveLv->lv_aufgeteilt === false)
		{
			// Reflexion is optional for lectors that are not LV-Leitung
			$isLvLeitung = $this->evaluationlib->isLvLeitung(
				$uid,
				$lveLv->lehrveranstaltung_id,
				$lveLv->studiensemester_kurzbz
			);

			if($isLvLeitung === false)
			{
				$verpflichtend = false;
			}
		}

		return $verpflichtend;
	}
	private function buildReflexionenByLehrendeOfLve($lve, $lveLv, $lvLeitung){

		// Get Lehrende
		$lektoren = $this->evaluationlib->getLehrendeByLve($lve, $lveLv, $lvLeitung, true);

		// ReflexionData
		$data = [];
		foreach ($lektoren as $lektor) {
			$isVerpflichtend = $this->isReflexionVerpflichtendForUid($lveLv, $lektor->uid);

			// Get Reflexion
			$result = $this->LvevaluierungReflexionModel->loadWhere([
				'lvevaluierung_id' => $lve->lvevaluierung_id,
				'mitarbeiter_uid' => $lektor->uid,
			]);
			$reflexion = hasData($result) ? getData($result)[0] : null;

			// ReflexionData immer zurückgeben mit Lektoren Info.
			// Eigentliche LV-Reflexion kann null sein -> frontend bildet dann leeres Formular zum Bearbeiten
			$data[] = [
				'lvevaluierung_reflexion_id' => !is_null($reflexion) ? $reflexion->lvevaluierung_reflexion_id : null,
				'lvevaluierung_id' => $lve->lvevaluierung_id,
				'mitarbeiter_uid' => $lektor->uid,
				'lveReflexion' => $reflexion, // kann null sein
				'vorname' => $lektor->vorname,
				'nachname' => $lektor->nachname,
				'isVerpflichtend' => $isVerpflichtend,
				'isLvLeitung' => $lektor->isLvLeitung
			];
		}

		// Sort: LV-Leitung first
		usort($data, function($a, $b) {
			if ($a['isLvLeitung'] === $b['isLvLeitung']) return 0;
			return $a['isLvLeitung'] ? -1 : 1;
		});

		return $data;

	}

	// todo check. moved to library
//	/**
//	 * Get Lehrende depending on Gesamt or GruppenEvaluierung.
//	 * Add optionale Lehrende for reflexion.
//	 *
//	 * @param $lve
//	 * @param $lveLv
//	 * @param $lvLeitung
//	 * @param $addOptionale
//	 * @return array
//	 */
//	private function getLehrendeByLve($lve, $lveLv, $lvLeitung = null, $addOptionale = false)
//	{
//		if (is_null($lvLeitung))
//		{
//			$result = $this->LehrveranstaltungModel->getLvLeitung(
//				$lveLv->lehrveranstaltung_id,
//				$lveLv->studiensemester_kurzbz
//			);
//			$lvLeitung = hasData($result) ? getData($result)[0] : null;
//		}
//
//		if ($lveLv->lv_aufgeteilt && is_int($lve->lehreinheit_id)) // Gruppen Evaluierung
//		{
//			// Aufgrund Gruppen Logik sollte hier nur ein Lektor zurückgegeben werden
//			$result = $this->LehreinheitmitarbeiterModel->getLektorenByLe($lve->lehreinheit_id);	// Must be only one because of Gruppen logic
//			$lektoren = hasData($result) ? array(getData($result)[0]) : [];	// todo Fallback erster im array noch ändern
//		}
//		else // Gesamt-LV
//		{
//			if ($addOptionale === true)
//			{
//				// Alle Lektoren (LV-Leitung Pflicht, andere optional)
//				$result = $this->LehrveranstaltungModel->getLecturersByLv(
//					$lveLv->studiensemester_kurzbz,
//					$lveLv->lehrveranstaltung_id
//				);
//
//				$lektoren = hasData($result) ? getData($result) : [];
//
//				// LV-Leitung ergänzen, falls nicht Lehrender ist
//				if (!in_array($lvLeitung->mitarbeiter_uid, array_column($lektoren, 'uid')))
//				{
//					$lektoren[]= $lvLeitung;
//				}
//			}
//			else
//			{
//				// Reflexion nur für LV-Leitung verpflichtend
//				$lektoren = array($lvLeitung);
//			}
//		}
//
//		// Result data vereinheitlichen
//		$result = [];
//		foreach ($lektoren as $lektor)
//		{
//			$isLvLeitung = null;
//			if(isset($lektor->lehrfunktion_kurzbz))
//			{
//				$isLvLeitung = $lektor->lehrfunktion_kurzbz === 'LV-Leitung' ? true : false;
//			}
//			elseif (isset($lektor->lvleiter))
//			{
//				$isLvLeitung = $lektor->lvleiter;
//			}
//			$result[]= (object) [
//				'vorname' => $lektor->vorname,
//				'nachname' => $lektor->nachname,
//				'uid' => isset($lektor->mitarbeiter_uid) ? $lektor->mitarbeiter_uid : $lektor->uid,
//				'isLvLeitung' => $isLvLeitung
//			];
//		}
//
//		return $result;
//	}

	private function filterReflexionenByPermission(
		$reflexionen,
		$lveLv,
		$lvLeitungUid
	)
	{
		$data = [];

		foreach ($reflexionen as $reflexion) {
			// Gruppen-Evaluierung: nur eigene Reflexion sichtbar
			if ($lveLv->lv_aufgeteilt && $reflexion['mitarbeiter_uid'] === $this->_uid) {
				$data[] = $reflexion;
				continue;
			}

			// Gesamt-LV Evaluierung
			if (!$lveLv->lv_aufgeteilt) {

				// Eigene Reflexion sichtbar
				if ($reflexion['mitarbeiter_uid'] === $this->_uid) {
					$data[] = $reflexion;
				}

				// Wenn nicht LV-Leitung: auch Reflexion von LV-Leitung sichtbar
				if ($this->_uid !== $lvLeitungUid &&
					$reflexion['mitarbeiter_uid'] === $lvLeitungUid)
				{
					$data[] = $reflexion;

				}
			}
		}
		return $data;
	}
	private function addZeitfensterAndBearbeitungsChecks(
		$reflexionen,
		$lve,
		$lveLv,
		$isKfl,
		$isStgl,
		$lvLeitungUid
	)
	{
		$data = [];

		$check = $this->checkBearbeitungOffenForReflexion($lve);

		foreach ($reflexionen as $reflexion)
		{
			$reflexion['isBearbeitungOffen'] = $check['isBearbeitungOffen'];
			$reflexion['sperreGrund'] = $check['sperreGrund'];
			$reflexion['zeitfensterVon'] = $check['zeitfensterVon'];
			$reflexion['zeitfensterBis'] = $check['zeitfensterBis'];

			$reflexion['display'] = [
				'showSaveButton' => true,
				'showEinmeldungButton' => true,
			];

			// KFL / STGL, aber nur wenn nicht selbst Bearbeiter der Reflexion ist
			if (($isKfl || $isStgl) && $reflexion['mitarbeiter_uid'] !== $this->_uid)
			{
				$reflexion['isBearbeitungOffen'] = false;
				$reflexion['sperreGrund'][] = 'Nur Ansicht möglich';
			}

			// Gesamt LV + fremde LV Leitung Reflexion
			if (
				!$lveLv->lv_aufgeteilt
				&& $this->_uid !== $lvLeitungUid
				&& $reflexion['mitarbeiter_uid'] === $lvLeitungUid
			) {
				$reflexion['isBearbeitungOffen'] = false;
				$reflexion['sperreGrund'][] = 'Nur Ansicht möglich';
			}

			$data[] = $reflexion;
		}

		return $data;
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

		if(!$this->isAllowedToSwitchVerpflichtung($lvevaluierung_lehrveranstaltung_id))
		{
			$this->terminateWithError("Die Verbindlichkeit darf zu diesem Zeitpunkt nicht mehr geändert werden");
		}

		$result = $this->LvevaluierungLehrveranstaltungModel->update(
			$lvevaluierung_lehrveranstaltung_id,
			['verpflichtend' => $isVerpflichtend]
		);
		$data = $this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($data);
	}

	/**
	 * Prueft ob Studiengänge LVs als verpflichtend an/abwaehlen dürfen
	 * @param $lvevaluierung_lehrveranstaltung_id
	 * @return boolean
	 */
	private function isAllowedToSwitchVerpflichtung($lvevaluierung_lehrveranstaltung_id)
	{
		return $this->LvevaluierungZeitfensterModel->isBearbeitungOffenLve('stgauswahl', $lvevaluierung_lehrveranstaltung_id);
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
	 * Return if Evaluation has not been ended yet, Evaluation period is still running or codes were not even sent.
	 *
	 * @param $lve
	 * @return bool
	 */
	private function isEvaluationViewOpen($lve)
	{
		$lveLv = $this->getLvevaluierungLehrveranstaltungOrFail($lve->lvevaluierung_lehrveranstaltung_id);
		$isKfl = $this->evaluationlib->isKFL($this->_uid, $lveLv->lehrveranstaltung_id);
		$isStgl = $this->evaluationlib->isSTGL($this->_uid, $lveLv->lehrveranstaltung_id);

		// Für KFL und STGL Evaluierungsansicht öffnen, wenn LV-Reflexionszeitraum abgeschlossen
		if ($isKfl || $isStgl)
		{
			$isReflexionszeitRaumAbgeschlossen = $this->LvevaluierungReflexionModel->isReflexionszeitraumAbgeschlossenForLve($lve->lvevaluierung_id);
			if (!$isReflexionszeitRaumAbgeschlossen)
			{
				return false;
			}
		}

		// Genereller Evaluierungsansicht öffnen, wenn Codes versendet und Evaluierungszeitfenster abgeschlossen
		$now = (new DateTime())->format('Y-m-d H:i:s');
		return !(
			$lve->codes_ausgegeben === null ||
			$lve->endezeit === null ||
			$now < $lve->endezeit
		);
	}
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
					'verpflichtend' => $item->fbFrageVerpflichtend,
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
		elseif (!hasData($result))
		{
			$this->terminateWithError('EvaluierungID ' . $lvevaluierung_id. ' does not exist');
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
