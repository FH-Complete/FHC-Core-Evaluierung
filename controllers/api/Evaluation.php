<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Evaluation extends FHCAPI_Controller
{
	const BERECHTIGUNG_KF = 'extension/lvevaluierung_kf';
	const BERECHTIGUNG_STG = 'extension/lvevaluierung_stg';
	const BERECHTIGUNG_INIT = 'extension/lvevaluierung_init';
	const BERECHTIGUNG_ADMIN = 'extension/lvevaluierung_admin';

	public function __construct()
	{
		parent::__construct([
				'getEvaluationDataByLve' => [
					self::BERECHTIGUNG_KF . ':r',
					self::BERECHTIGUNG_STG . ':r',
					self::BERECHTIGUNG_INIT . ':r',
					self::BERECHTIGUNG_ADMIN . ':r',
				],
				'getEvaluationDataByLveLv' => [
					self::BERECHTIGUNG_KF . ':r',
					self::BERECHTIGUNG_STG . ':r',
					self::BERECHTIGUNG_ADMIN . ':r',
				],
				'getEvaluationDataByLvTemplate' => [
					self::BERECHTIGUNG_KF . ':r',
					self::BERECHTIGUNG_ADMIN . ':r',
				],
				'getAuswertungDataByLve' => [
					self::BERECHTIGUNG_KF . ':r',
					self::BERECHTIGUNG_STG . ':r',
					self::BERECHTIGUNG_INIT . ':r',
					self::BERECHTIGUNG_ADMIN . ':r',
				],
				'getAuswertungDataByLveLv' => [
					self::BERECHTIGUNG_KF . ':r',
					self::BERECHTIGUNG_STG . ':r',
					self::BERECHTIGUNG_ADMIN . ':r',
				],
				'getAuswertungDataByLvTemplate' => [
					self::BERECHTIGUNG_KF . ':r',
					self::BERECHTIGUNG_ADMIN . ':r',
				],
				'getAuswertungHelpUrl' => [
					self::BERECHTIGUNG_KF . ':r',
					self::BERECHTIGUNG_STG . ':r',
					self::BERECHTIGUNG_INIT . ':r',
					self::BERECHTIGUNG_ADMIN . ':r',
				],
				'getTextantwortenByLve' => [
					self::BERECHTIGUNG_KF . ':r',
					self::BERECHTIGUNG_STG . ':r',
					self::BERECHTIGUNG_INIT . ':r',
					self::BERECHTIGUNG_ADMIN . ':r',
				],
				'getTextantwortenByLveLv' => [
					self::BERECHTIGUNG_KF . ':r',
					self::BERECHTIGUNG_STG . ':r',
					self::BERECHTIGUNG_ADMIN . ':r',
				],
				'getReflexionDataByLve' => [
					self::BERECHTIGUNG_KF . ':r',
					self::BERECHTIGUNG_STG . ':r',
					self::BERECHTIGUNG_INIT . ':r',
					self::BERECHTIGUNG_ADMIN . ':r',
				],
				'getReflexionDataByLveLv' => [
					self::BERECHTIGUNG_KF . ':r',
					self::BERECHTIGUNG_STG . ':r',
					self::BERECHTIGUNG_ADMIN . ':r',
				],
				'getReflexionDataByLvTemplate' => [
					self::BERECHTIGUNG_KF . ':r',
					self::BERECHTIGUNG_ADMIN . ':r',
				],
				'getEntitledKfs' => [
					self::BERECHTIGUNG_KF . ':r',
					self::BERECHTIGUNG_ADMIN . ':r',
				],
				'getEntitledStgs' => [
					self::BERECHTIGUNG_STG . ':r',
					self::BERECHTIGUNG_ADMIN . ':r',
				],
				'getOrgformsByStg' => [
					self::BERECHTIGUNG_STG . ':r',
					self::BERECHTIGUNG_ADMIN . ':r',
				],
				'getLvListByKf' => [
					self::BERECHTIGUNG_KF . ':r',
					self::BERECHTIGUNG_ADMIN . ':r',
				],
				'getLvTemplateListByKf' => [
				self::BERECHTIGUNG_KF . ':r',
				self::BERECHTIGUNG_ADMIN . ':r',
			],
				'getLvListByStg' => [
					self::BERECHTIGUNG_STG . ':r',
					self::BERECHTIGUNG_ADMIN . ':r',
				],
				'getMalveByStg' => [
					self::BERECHTIGUNG_STG . ':r',
					self::BERECHTIGUNG_ADMIN . ':r',
				],
				'getMalveByKf' => [
					self::BERECHTIGUNG_KF . ':r',
					self::BERECHTIGUNG_ADMIN . ':r',
				],
				'updateVerpflichtend' => [
					self::BERECHTIGUNG_STG . ':rw',
					self::BERECHTIGUNG_ADMIN . ':rw',
				],
				'updateReviewedLvInKf' => [
					self::BERECHTIGUNG_KF . ':rw',
					self::BERECHTIGUNG_ADMIN . ':rw',
				],
				'updateReviewedLvInStg' => [
					self::BERECHTIGUNG_STG . ':rw',
					self::BERECHTIGUNG_ADMIN . ':rw',
				],
				'saveOrUpdateReflexion' => [
					self::BERECHTIGUNG_INIT . ':rw',
					self::BERECHTIGUNG_ADMIN . ':rw',
				],
				'saveMalveByKf' => [
					self::BERECHTIGUNG_KF . ':rw',
					self::BERECHTIGUNG_ADMIN . ':rw',
				],
				'saveMalveByStg' => [
					self::BERECHTIGUNG_STG . ':rw',
					self::BERECHTIGUNG_ADMIN . ':rw',
				],
			]
		);

		$this->load->library('extensions/FHC-Core-Evaluierung/EvaluationLib');
		$this->load->config('extensions/FHC-Core-Evaluierung/initiierung');

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
		$role = $this->input->get('role');

		$lve = $this->getLvevaluierungOrFail($lvevaluierung_id);
		$lveLv = $this->getLvevaluierungLehrveranstaltungOrFail($lve->lvevaluierung_lehrveranstaltung_id);
		$lvData = $this->evaluationlib->getLvData($lveLv->lehrveranstaltung_id, $lveLv->studiensemester_kurzbz);

		// KFL, STGL, Last inserted LV-Leitung, Admin
		$isKfl = $this->evaluationlib->isKFL($this->_uid, $lveLv->lehrveranstaltung_id);
		$isStgl = $this->evaluationlib->isSTGL($this->_uid, $lveLv->lehrveranstaltung_id);
		$isLvLeitung = $this->evaluationlib->isLvLeitung($this->_uid, $lveLv->lehrveranstaltung_id, $lveLv->studiensemester_kurzbz);
		$lvLeitungen = $this->evaluationlib->getLvLeitung($lveLv->lehrveranstaltung_id, $lveLv->studiensemester_kurzbz);
		$isAdmin = $this->permissionlib->isBerechtigt(self::BERECHTIGUNG_ADMIN);

		// Lehrende
		$lehrende = $this->evaluationlib->getLehrendeByLve($lve, $lveLv, true);
		$isLektorOfLv = in_array($this->_uid, array_column($lehrende, 'uid'));

		// Permission Check
		if (
			$isLektorOfLv ||
			$isLvLeitung ||
			$isKfl ||
			$isStgl ||
			$isAdmin
		) {

			// Abgeschickte Frageboegen, Ruecklaufquote
			$result = $this->LvevaluierungCodeModel->getAbgeschlosseneEvaluierungenByLve($lve->lvevaluierung_id);
			$submittedLveCodes = hasData($result) ? getData($result) : [];
			$countSubmitted = count($submittedLveCodes);
			$ruecklaufquote = null;
			if ($lve->codes_ausgegeben !== null && $lve->codes_ausgegeben > 0) {
				$ruecklaufquote = round(($countSubmitted / $lve->codes_ausgegeben) * 100, 2);
			}

			// Reflexion Start- und Endezeit
			$reflexionZeitfenster = $this->evaluationlib->calculateReflexionZeitfenster($lve->endezeit);

			// For min/max duration
			$durations = $this->getDurations($submittedLveCodes);

			// Check if Evaluation view is open
			// ---------------------------------------------------------------------------------------------------------
			$isEvaluationViewOpen = true;
			$isEvaluationViewOpenMsg = [];

			$context = $this->getEvaluationViewOpenMsgContextText($lve, $lveLv->lv_aufgeteilt);

			if (!$this->hasSetEvaluierungszeitraum($lve))
			{
				$isEvaluationViewOpen = false;
				$isEvaluationViewOpenMsg[] = 'Evaluierung noch nicht gestartet'. $context;
			}
			elseif (!$this->hasSentEvaluierungscodes($lve))
			{
				$isEvaluationViewOpen = false;
				$isEvaluationViewOpenMsg[]= 'Evaluierungscodes noch nicht versendet'. $context;
			}
			elseif (!$this->isEvaluierungszeitraumAbgeschlossen($lve))
			{
				$isEvaluationViewOpen = false;
				$isEvaluationViewOpenMsg[]= 'Evaluierungszeitfenster noch nicht abgeschlossen'. $context
					. '. Zeitfenster: '
					. (new DateTime($lve->startzeit))->format('d.m.Y')
					. ' - '
					. (new DateTime($lve->endezeit))->format('d.m.Y');

			}
			elseif ($role === 'stg' || $role === 'kf')
			{
				if (!$this->isReflexionszeitraumAbgeschlossen($lve))
				{
					$isEvaluationViewOpen = false;
					$isEvaluationViewOpenMsg[]= 'LV-Reflexionszeitraum noch nicht abgeschlossen' . $context
						. '. Zeitfenster: '
						. $reflexionZeitfenster['von']->format('d.m.Y')
						. ' - '
						. $reflexionZeitfenster['bis']->format('d.m.Y');
				}
			}

			// Check dropdown rendering (Gesamt-/Gruppen-Ansicht)
			// ---------------------------------------------------------------------------------------------------------
			$canAggregate = $role === 'stg' || $role === 'kf';
			$aggregationOptions = null;

			if ($canAggregate)
			{
				$aggregationOptions = $this->getAggregationSelectOptions($lveLv->lvevaluierung_lehrveranstaltung_id);
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
				['startzeitReflexion' => $reflexionZeitfenster['von']->format('d.m.Y') ?? null],
				['endezeitReflexion' => $reflexionZeitfenster['bis']->format('d.m.Y') ?? null],
				['minDuration' => $durations ? min($durations) : 0],
				['maxDuration' => $durations ? max($durations) : 0]
			);

			$response = [
				'data' => $data,
				'evaluationView' => [
					'open' => $isEvaluationViewOpen,
					'msg' => $isEvaluationViewOpenMsg,
					'canAggregate' => $canAggregate,
					'aggregationOptions' => $aggregationOptions
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
					'msg' => ['Keine Berechtigung zur Ansicht dieser Evaluation'],
					'canAggregate' => false,
					'aggregationOptions' => []
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

		// KFL, STGL, LV-Leitung (last insertet LvLeitung), Admin
		$isKfl = $this->evaluationlib->isKFL($this->_uid, $lveLv->lehrveranstaltung_id);
		$isStgl = $this->evaluationlib->isSTGL($this->_uid, $lveLv->lehrveranstaltung_id);
		$lvLeitungen = $this->evaluationlib->getLvLeitung($lveLv->lehrveranstaltung_id, $lveLv->studiensemester_kurzbz);
		$isAdmin = $this->permissionlib->isBerechtigt(self::BERECHTIGUNG_ADMIN);

		// Lehrende
		$result = $this->LehrveranstaltungModel->getLecturersByLv($lveLv->studiensemester_kurzbz, $lveLv->lehrveranstaltung_id);
		$lehrende = hasData($result) ? getData($result) : [];

		// Permission Check
		if ($isKfl || $isStgl || $isAdmin)
		{
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

			// Evaluierungs- und Reflexionszeitraum: Min startzeit / max endezeit overall Evaluierungen
			$periodTimes = $this->getPeriodTimes($lves);

			// Check if Evaluation view is open
			// ---------------------------------------------------------------------------------------------------------
			$isEvaluationViewOpen = true;
			$isEvaluationViewOpenMsg = [];

			// No Evaluierung at all
			if (empty($lves))
			{
				$isEvaluationViewOpen = false;
				$isEvaluationViewOpenMsg[]= 'Noch keine Evaluierung gestartet';
			}

			// Check each Evaluierung
			foreach ($lves as $lve)
			{
				$context = $this->getEvaluationViewOpenMsgContextText($lve, $lveLv->lv_aufgeteilt);
				$reflexionZeitfenster = $this->evaluationlib->calculateReflexionZeitfenster($lve->endezeit);

				if (!$this->hasSetEvaluierungszeitraum($lve))
				{
					$isEvaluationViewOpen = false;
					$isEvaluationViewOpenMsg[]= 'Evaluierung noch nicht gestartet'. $context;
					continue;
				}

				if (!$this->hasSentEvaluierungscodes($lve))
				{
					$isEvaluationViewOpen = false;
					$isEvaluationViewOpenMsg[]= 'Evaluierungcodes noch nicht versendet'. $context;
					continue;
				}

				if (!$this->isEvaluierungszeitraumAbgeschlossen($lve))
				{
					$isEvaluationViewOpen = false;
					$isEvaluationViewOpenMsg[]= 'Evaluierungszeitfenster noch nicht abgeschlossen'. $context
						.'. Zeitfenster: '
						. (new DateTime($lve->startzeit))->format('d.m.Y')
						. ' - '
						. (new DateTime($lve->endezeit))->format('d.m.Y');

					continue;
				}

				if (!$this->isReflexionszeitraumAbgeschlossen($lve))
				{
					$isEvaluationViewOpen = false;
					$isEvaluationViewOpenMsg[]= 'LV-Reflexionszeitraum noch nicht abgeschlossen'. $context
						. '. Zeitfenster: '
						. $reflexionZeitfenster['von']->format('d.m.Y')
						. ' - '
						. $reflexionZeitfenster['bis']->format('d.m.Y');
				}
			}

			// Check dropdown rendering (Gesamt-/Gruppen-Ansicht)
			// ---------------------------------------------------------------------------------------------------------
			$canAggregate = $isKfl || $isStgl || $isAdmin;
			$aggregationOptions = null;

			if ($canAggregate)
			{
				$aggregationOptions = $this->getAggregationSelectOptions($lveLv->lvevaluierung_lehrveranstaltung_id);
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
				['startzeitReflexion' => $periodTimes['minStartzeitReflexion']],
				['endezeitReflexion' => $periodTimes['maxEndezeitReflexion']],
				['minDuration' => $durations ? min($durations) : 0],
				['maxDuration' => $durations ? max($durations) : 0]
			);

			$response = [
				'data' => $data,
				'evaluationView' => [
					'open' => $isEvaluationViewOpen,
					'msg' => $isEvaluationViewOpenMsg,
					'canAggregate' => $canAggregate,
					'aggregationOptions' => $aggregationOptions
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
					'msg' => ['Keine Berechtigung zur Ansicht dieser Evaluation'],
					'canAggregate' => false,
					'aggregationOptions' => []
				],
			];

			$this->terminateWithSuccess($response);
		}
	}

	public function getEvaluationDataByLvTemplate()
	{
		$lehrveranstaltung_template_id = $this->input->get('lehrveranstaltung_template_id');
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');

		// KFL, Admin
		$isKfl = $this->permissionlib->isBerechtigt(self::BERECHTIGUNG_KF);
		$isAdmin = $this->permissionlib->isBerechtigt(self::BERECHTIGUNG_ADMIN);

		// Permission Check
		if ($isKfl || $isAdmin)
		{
			// Quellkurs header info
			$lvData['lehrveranstaltung_template_id'] = $lehrveranstaltung_template_id;
			$lvData['studiensemester_kurzbz'] = $studiensemester_kurzbz;
			$lvData['bezeichnung'] = $this->evaluationlib->getLvBezeichnung($lehrveranstaltung_template_id);

			$result = $this->LvevaluierungLehrveranstaltungModel->getLveLvsByLvTemplateId(
				$lehrveranstaltung_template_id,
				$studiensemester_kurzbz
			);
			$lveLvs = hasData($result) ? getData($result) : [];
			$lvData['lveLvs'] = $lveLvs;

			// Abgeschickte Frageboegen, Ruecklaufquote
			$result = $this->LvevaluierungCodeModel->getAggregatedRuecklaufDataByLvTemplateIds(
				[$lehrveranstaltung_template_id],
				$studiensemester_kurzbz
			);

			$rlData = hasData($result) ? current(getData($result)) : null;

			$codesAusgegeben = $rlData ? (int)$rlData->sum_codes_ausgegeben : 0;
			$countSubmitted = $rlData ? (int)$rlData->count_submitted_codes : 0;
			$ruecklaufquote = $rlData ? (float)$rlData->ruecklaufquote : null;

			// Check if Evaluation view is open
			// ---------------------------------------------------------------------------------------------------------
			$isEvaluationViewOpen = true;
			$isEvaluationViewOpenMsg = [];

			foreach ($lveLvs as $lveLv)
			{
				$lves = $this->getLvevaluierungByLveLvOrFail($lveLv->lvevaluierung_lehrveranstaltung_id);

				// No Evaluierung at all
				if (empty($lves))
				{
					$isEvaluationViewOpen = false;
					$isEvaluationViewOpenMsg[] = 'Evaluierung noch nicht gestartet'
						. $this->getEvaluationViewOpenMsgContextTextByLveLv($lveLv);
				}

				// Check each Evaluierung
				foreach ($lves as $lve)
				{
					$context = $this->getEvaluationViewOpenMsgContextTextByLveLv($lveLv);

					if (!$this->hasSetEvaluierungszeitraum($lve))
					{
						$isEvaluationViewOpen = false;
						$isEvaluationViewOpenMsg[] = 'Evaluierung noch nicht gestartet' . $context;
						break;
					}

					if (!$this->hasSentEvaluierungscodes($lve))
					{
						$isEvaluationViewOpen = false;
						$isEvaluationViewOpenMsg[] = 'Evaluierungcodes noch nicht versendet' . $context;
						break;
					}

					if (!$this->isEvaluierungszeitraumAbgeschlossen($lve))
					{
						$isEvaluationViewOpen = false;
						$isEvaluationViewOpenMsg[] =
							'Evaluierungszeitfenster noch nicht abgeschlossen' . $context;

						break;
					}

					if (!$this->isReflexionszeitraumAbgeschlossen($lve))
					{
						$isEvaluationViewOpen = false;
						$isEvaluationViewOpenMsg[] =
							'LV-Reflexionszeitraum noch nicht abgeschlossen' . $context;
						break;
					}
				}
			}

			$data = array_merge(
				$lvData,
				['codes_ausgegeben' => $codesAusgegeben],
				['countSubmitted' => $countSubmitted],
				['ruecklaufquote' => $ruecklaufquote]
			);

			$response = [
				'data' => $data,
				'evaluationView' => [
					'open' => $isEvaluationViewOpen,
					'msg' => $isEvaluationViewOpenMsg,
					'canAggregate' => false,    // No dropdown for Evaluation view auf Quellkursebene (Gesamt-/Gruppen-Ansicht)
					'aggregationOptions' => false    // No dropdown for Evaluation view auf Quellkursebene (Gesamt-/Gruppen-Ansicht)
				],
			];

			$this->terminateWithSuccess($response);
		} else
		{
			$response = [
				'data' => null,
				'evaluationView' => [
					'open' => false,
					'msg' => ['Keine Berechtigung zur Ansicht dieser Evaluation'],
					'canAggregate' => false,
					'aggregationOptions' => []
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
		$role = $this->input->get('role');

		$lve = $this->getLvevaluierungOrFail($lvevaluierung_id);
		$lveLv = $this->getLvevaluierungLehrveranstaltungOrFail($lve->lvevaluierung_lehrveranstaltung_id);

		// KFL, STGL, Last inserted LV-Leitung, Admin
		$isKfl = $this->evaluationlib->isKFL($this->_uid, $lveLv->lehrveranstaltung_id);
		$isStgl = $this->evaluationlib->isSTGL($this->_uid, $lveLv->lehrveranstaltung_id);
		$isLvLeitung = $this->evaluationlib->isLvLeitung($this->_uid, $lveLv->lehrveranstaltung_id, $lveLv->studiensemester_kurzbz);
		$isAdmin = $this->permissionlib->isBerechtigt(self::BERECHTIGUNG_ADMIN);

		// Lehrende
		$lehrende = $this->evaluationlib->getLehrendeByLve($lve, $lveLv, true);
		$isLektorOfLv = in_array($this->_uid, array_column($lehrende, 'uid'));

		// Permission check
		if (
			!$isLektorOfLv &&
			!$isLvLeitung &&
			!$isKfl &&
			!$isStgl &&
			!$isAdmin
		)
		{
			$this->terminateWithError('Permission denied');
		}

		// Exit Auswertung view
		if (!$this->hasSetEvaluierungszeitraum($lve)) $this->terminateWithSuccess([]);
		if (!$this->hasSentEvaluierungscodes($lve)) $this->terminateWithSuccess([]);
		if (!$this->isEvaluierungszeitraumAbgeschlossen($lve)) $this->terminateWithSuccess([]);
		if ($role === 'stg' || $role === 'kf')
		{
			if (!$this->isReflexionszeitraumAbgeschlossen($lve)) $this->terminateWithSuccess([]);
		}

		// Get Auswertungen
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
		$lveLv = $this->getLvevaluierungLehrveranstaltungOrFail($lvevaluierung_lehrveranstaltung_id);

		// KFL, STGL
		$isKfl = $this->evaluationlib->isKFL($this->_uid, $lveLv->lehrveranstaltung_id);
		$isStgl = $this->evaluationlib->isSTGL($this->_uid, $lveLv->lehrveranstaltung_id);
		$isAdmin = $this->permissionlib->isBerechtigt(self::BERECHTIGUNG_ADMIN);

		// Permission check
		if (!$isKfl && !$isStgl && !$isAdmin)
		{
			$this->terminateWithError('Permission denied');
		}

		// Exit Auswertung view
		foreach ($lves as $lve)
		{
			if (!$this->hasSetEvaluierungszeitraum($lve)) $this->terminateWithSuccess([]);
			if (!$this->hasSentEvaluierungscodes($lve)) $this->terminateWithSuccess([]);
			if (!$this->isEvaluierungszeitraumAbgeschlossen($lve)) $this->terminateWithSuccess([]);
			if (!$this->isReflexionszeitraumAbgeschlossen($lve)) $this->terminateWithSuccess([]);
		}

		// Get Auswertungen
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
				$frage['antworten']['hodgesLehmann']['actYear'] = $this->evaluationlib->getHodgesLehmannEstimator($werte, $frequencies);
			}
		}

		$this->terminateWithSuccess($auswertungData);
	}

	public function getAuswertungDataByLvTemplate()
	{
		$lehrveranstaltung_template_id = $this->input->get('lehrveranstaltung_template_id');
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');

		$result = $this->LvevaluierungLehrveranstaltungModel->getLveLvsByLvTemplateId(
			$lehrveranstaltung_template_id,
			$studiensemester_kurzbz
		);
		$lveLvs = hasData($result) ? getData($result) : [];

		// KFL, Admin
		$isKfl = $this->permissionlib->isBerechtigt(self::BERECHTIGUNG_KF);
		$isAdmin = $this->permissionlib->isBerechtigt(self::BERECHTIGUNG_ADMIN);

		// Permission check
		if (!$isKfl && !$isAdmin)
		{
			$this->terminateWithError('Permission denied');
		}

		// Exit Auswertung view
		foreach ($lveLvs as $lveLv)
		{
			$lves = $this->getLvevaluierungByLveLvOrFail($lveLv->lvevaluierung_lehrveranstaltung_id);

			foreach ($lves as $lve)
			{
				if (!$this->hasSetEvaluierungszeitraum($lve)) $this->terminateWithSuccess([]);
				if (!$this->hasSentEvaluierungscodes($lve)) $this->terminateWithSuccess([]);
				if (!$this->isEvaluierungszeitraumAbgeschlossen($lve)) $this->terminateWithSuccess([]);
				if (!$this->isReflexionszeitraumAbgeschlossen($lve)) $this->terminateWithSuccess([]);
			}
		}

		// Get Auswertungen
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogenGruppe_model', 'LvevaluierungFragebogenGruppeModel');
		$result = $this->LvevaluierungFragebogenGruppeModel->getAuswertungDataByLvTemplateId($lehrveranstaltung_template_id, $studiensemester_kurzbz);
		$data = $this->getDataOrTerminateWithError($result);

		// Re-structure data
		$auswertungData = $this->mapAuswertungData($data);

		// Calculate interpolierten Median for each Antwort
		foreach ($auswertungData as &$gruppe)
		{
			foreach ($gruppe['fbFragen'] as &$frage)
			{
				$werte = $frage['antworten']['werte'];
				$frequencies = $frage['antworten']['frequencies'];
				$frage['antworten']['hodgesLehmann']['actYear'] = $this->evaluationlib->getHodgesLehmannEstimator($werte, $frequencies);
			}
		}

		$this->terminateWithSuccess($auswertungData);
	}

	/**
	 * Get the Auswertung help document URL ('Erläuterung Ergebnisse') from config
	 *
	 * @return string|null URL if configured, otherwise null
	 */
	public function getAuswertungHelpUrl()
	{
		$url = $this->config->item('auswertungHelpUrl');

		if ($url === null || $url === false || $url == '')
		{
			$this->terminateWithSuccess(null);
		}

		$this->terminateWithSuccess($url);
	}

	/**
	 * Fetch Textantworten by LVE ID.
	 *
	 * @return void
	 */
	public function getTextantwortenByLve()
	{
		$lvevaluierung_id = $this->input->get('lvevaluierung_id');
		$role = $this->input->get('role');

		$lve = $this->getLvevaluierungOrFail($lvevaluierung_id);
		$lveLv = $this->getLvevaluierungLehrveranstaltungOrFail($lve->lvevaluierung_lehrveranstaltung_id);

		// KFL, STGL, Last inserted LV-Leitung, Admin
		$isKfl = $this->evaluationlib->isKFL($this->_uid, $lveLv->lehrveranstaltung_id);
		$isStgl = $this->evaluationlib->isSTGL($this->_uid, $lveLv->lehrveranstaltung_id);
		$isLvLeitung = $this->evaluationlib->isLvLeitung($this->_uid, $lveLv->lehrveranstaltung_id, $lveLv->studiensemester_kurzbz);
		$isAdmin = $this->permissionlib->isBerechtigt(self::BERECHTIGUNG_ADMIN);

		// Lehrende
		$lehrende = $this->evaluationlib->getLehrendeByLve($lve, $lveLv, true);
		$isLektorOfLv = in_array($this->_uid, array_column($lehrende, 'uid'));

		// Permission check
		if (
			!$isLektorOfLv &&
			!$isLvLeitung &&
			!$isKfl &&
			!$isStgl &&
			!$isAdmin
		)
		{
			$this->terminateWithError('Permission denied');
		}

		// Exit Auswertung view
		if (!$this->hasSetEvaluierungszeitraum($lve)) $this->terminateWithSuccess([]);
		if (!$this->hasSentEvaluierungscodes($lve)) $this->terminateWithSuccess([]);
		if (!$this->isEvaluierungszeitraumAbgeschlossen($lve)) $this->terminateWithSuccess([]);
		if ($role === 'stg' || $role === 'kf')
		{
			if (!$this->isReflexionszeitraumAbgeschlossen($lve)) $this->terminateWithSuccess([]);
		}

		// Get Textantworten
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

		$lves = $this->getLvevaluierungByLveLvOrFail($lvevaluierung_lehrveranstaltung_id);
		$lveLv = $this->getLvevaluierungLehrveranstaltungOrFail($lvevaluierung_lehrveranstaltung_id);

		$isKfl = $this->evaluationlib->isKFL($this->_uid, $lveLv->lehrveranstaltung_id);
		$isStgl = $this->evaluationlib->isSTGL($this->_uid, $lveLv->lehrveranstaltung_id);
		$isAdmin = $this->permissionlib->isBerechtigt(self::BERECHTIGUNG_ADMIN);

		// Permission check
		if (!$isKfl && !$isStgl && !$isAdmin)
		{
			$this->terminateWithError('Permission denied');
		}

		// Exit Textantworten view
		foreach ($lves as $lve)
		{
			if (!$this->hasSetEvaluierungszeitraum($lve)) $this->terminateWithSuccess([]);
			if (!$this->hasSentEvaluierungscodes($lve)) $this->terminateWithSuccess([]);
			if (!$this->isEvaluierungszeitraumAbgeschlossen($lve)) $this->terminateWithSuccess([]);
			if (!$this->isReflexionszeitraumAbgeschlossen($lve)) $this->terminateWithSuccess([]);
		}

		// Get Textantworten
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungAntwort_model', 'LvevaluierungAntwortModel');
		$result = $this->LvevaluierungAntwortModel->getTextantwortenByLveLv($lvevaluierung_lehrveranstaltung_id);
		$data = $this->getDataOrTerminateWithError($result);

		$textantworten = $this->mapTextantworten($data);

		$this->terminateWithSuccess($textantworten);
	}

	private function getAggregationSelectOptions($lvevaluierung_lehrveranstaltung_id)
	{
		$lveLv = $this->getLvevaluierungLehrveranstaltungOrFail($lvevaluierung_lehrveranstaltung_id);
		$lves = $this->getLvevaluierungByLveLvOrFail($lvevaluierung_lehrveranstaltung_id);

		$options = [];

		// Always option Evaluation auf LV-Ebene
		// NOTE: for Gesamt-LV and also for Gruppenevaluierung (= aggregated on LV-Ebene)
		if (!empty($lveLv))
		{
			$options[] = [
				'value' => (int)$lvevaluierung_lehrveranstaltung_id,
				'param' => 'lvevaluierung_lehrveranstaltung_id',
				'text' => 'Ansicht Evaluation auf LV-Ebene'
			];
		}

		// If Gruppenevaluierung: Add options for Gruppen LVEs
		if ($lveLv->lv_aufgeteilt)
		{
			if (!empty($lves))
			{
				$this->load->model('person/Person_model', 'PersonModel');

				$items = [];
				$counts = [];

				// Loop Evaluierungen
				foreach ($lves as $lve)
				{
					// Get Lektor
					$result = $this->LehreinheitmitarbeiterModel->getLektorenByLe($lve->lehreinheit_id);
					$lektor = hasData($result) ? getData($result)[0] : null; // NOTE: aufgrund Gruppenlogik nur ein Lektor

					$uid = isset($lektor->mitarbeiter_uid) ? $lektor->mitarbeiter_uid : null;
					$nachname = isset($lektor->nachname) ? $lektor->nachname : 'Name fehlt';

					// Counter for lektor
					if (!isset($counts[$uid]))
					{
						$counts[$uid] = 0;
					}

					// Count up if same lektor
					$counts[$uid]++;

					$items[] = [
						'uid' => $uid,
						'nachname' => $nachname,
						'id' => $lve->lvevaluierung_id
					];
				}

				// Sort by nachname
				usort($items, function ($a, $b)
				{
					return strcmp($a['nachname'], $b['nachname']);
				});

				// Build options
				$index = [];

				foreach ($items as $item)
				{
					$uid = $item['uid'];
					$label = '';

					// Add Gruppen label for duplicate lecturers, e.g.:
					// Lector A - Gruppe 1
					// Lector A - Gruppe 2
					if ($uid !== null && isset($counts[$uid]) && $counts[$uid] > 1)
					{
						if (!isset($index[$uid]))
						{
							$index[$uid] = 1;
						} else
						{
							$index[$uid]++;
						}

						$label = ' - Gruppe ' . $index[$uid];
					}

					$options[] = [
						'value' => $item['id'],    // lvevaluierung_id
						'param' => 'lvevaluierung_id',
						'text' => 'Ansicht Evaluationsgruppe: ' . $item['nachname'] . $label
					];
				}
			}
		}

		return $options;
	}

	// -----------------------------------------------------------------------------------------------------------------
	// LV-REFLEXION
	// -----------------------------------------------------------------------------------------------------------------
	public function getReflexionDataByLve()
	{
		$lvevaluierung_id = $this->input->get('lvevaluierung_id');
		$role = $this->input->get('role');

		$lve = $this->getLvevaluierungOrFail($lvevaluierung_id);
		$lveLv = $this->getLvevaluierungLehrveranstaltungOrFail($lve->lvevaluierung_lehrveranstaltung_id);

		// KFL, STGL, Last inserted LV-Leitung, Admin
		$isKfl = $this->evaluationlib->isKFL($this->_uid, $lveLv->lehrveranstaltung_id);
		$isStgl = $this->evaluationlib->isSTGL($this->_uid, $lveLv->lehrveranstaltung_id);
		$isLvLeitung = $this->evaluationlib->isLvLeitung($this->_uid, $lveLv->lehrveranstaltung_id, $lveLv->studiensemester_kurzbz);
		$isAdmin = $this->permissionlib->isBerechtigt(self::BERECHTIGUNG_ADMIN);

		// Lehrende
		$lehrende = $this->evaluationlib->getLehrendeByLve($lve, $lveLv, true);
		$isLektorOfLv = in_array($this->_uid, array_column($lehrende, 'uid'));

		// Permission check
		if (
			!$isLektorOfLv &&
			!$isLvLeitung &&
			!$isKfl &&
			!$isStgl &&
			!$isAdmin
		)
		{
			$this->terminateWithError('Permission denied');
		}

		// Exit Reflexionen view
		if (!$this->hasSetEvaluierungszeitraum($lve)) $this->terminateWithSuccess([]);
		if (!$this->hasSentEvaluierungscodes($lve)) $this->terminateWithSuccess([]);
		if (!$this->isEvaluierungszeitraumAbgeschlossen($lve)) $this->terminateWithSuccess([]);
		if ($role === 'stg' || $role === 'kf')
		{
			if (!$this->isReflexionszeitraumAbgeschlossen($lve)) $this->terminateWithSuccess([]);
		}

		// Reflexionen
		// -------------------------------------------------------------------------------------------------------------
		// Build Reflexionen by Lehrende
		$reflexionen = $this->buildReflexionenByLve($lve, $lveLv);

		// Übersicht Reflexionen (before possible filtering in next step)
		// -------------------------------------------------------------------------------------------------------------
		$reflexionenUebersichtData = [];
		$isReflexionszeitRaumAbgeschlossen = $this->isReflexionszeitraumAbgeschlossen($lve);
		$showReflexionenUebersicht =
			$isAdmin ||
			$role === 'stg' ||
			$role === 'kf' ||
			($isLvLeitung && !$lveLv->lv_aufgeteilt);    // LV-Leitung darf Übersicht nur sehen, wenn Gesamt-LV

		if ($showReflexionenUebersicht && $isReflexionszeitRaumAbgeschlossen)
		{
			$reflexionenUebersichtData = $this->buildReflexionenUebersichtData($reflexionen); // note: use the unfiltered reflexionen
		}

		// Filter Reflexionen and add checks
		// -------------------------------------------------------------------------------------------------------------
		/* Filter Reflexionen if necessary (nicht alle dürfen alle Reflexionen sehen)
			Ausnahme: STGL und KFL, wenn Reflexionszeit abgeschlossen ist (dürfen immer alles sehen) */
		if (!(($role === 'stg' || $role === 'kf') && $isReflexionszeitRaumAbgeschlossen))
		{
			$reflexionen = $this->filterVisibleReflexionenForLehrende(
				$reflexionen,
				$lveLv
			);
		}

		$checkedReflexionen = $this->addZeitfensterAndBearbeitungsChecks(
			$reflexionen,
			$lve,
			$lveLv,
			$isKfl,
			$isStgl
		);

		$resultData = [
			'reflexionen' => $checkedReflexionen,
			'reflexionenUebersicht' => [
				'show' => $showReflexionenUebersicht,
				'data' => $reflexionenUebersichtData,
			]
		];

		$this->terminateWithSuccess($resultData);
	}
	public function getReflexionDataByLveLv()
	{
		$lvevaluierung_lehrveranstaltung_id = $this->input->get('lvevaluierung_lehrveranstaltung_id');

		$lveLv = $this->getLvevaluierungLehrveranstaltungOrFail($lvevaluierung_lehrveranstaltung_id);
		$lves = $this->getLvevaluierungByLveLvOrFail($lvevaluierung_lehrveranstaltung_id);

		$isKfl = $this->evaluationlib->isKFL($this->_uid, $lveLv->lehrveranstaltung_id);
		$isStgl = $this->evaluationlib->isSTGL($this->_uid, $lveLv->lehrveranstaltung_id);
		$isAdmin = $this->permissionlib->isBerechtigt(self::BERECHTIGUNG_ADMIN);

		if (!$isKfl && !$isStgl && !$isAdmin)
		{
			$this->terminateWithError('Permission denied');
		}

		// Reflexionen
		// -------------------------------------------------------------------------------------------------------------
		$reflexionenByLveLv = [];
		$allReflexionszeitraumAbgeschlossen = true;
		foreach ($lves as $lve)
		{
			// Skip if Evaluation period is still running or students did not get codes yet
			if (!$this->hasSetEvaluierungszeitraum($lve)) continue;
			if (!$this->hasSentEvaluierungscodes($lve)) continue;
			if (!$this->isEvaluierungszeitraumAbgeschlossen($lve)) continue;
			if (!$this->isReflexionszeitraumAbgeschlossen($lve))
			{
				$allReflexionszeitraumAbgeschlossen = false;
				continue;
			}

			// Build Reflexionen
			$reflexionen = $this->buildReflexionenByLve($lve, $lveLv);

			$checkedReflexionen = $this->addZeitfensterAndBearbeitungsChecks(
				$reflexionen,
				$lve,
				$lveLv,
				$isKfl,
				$isStgl
			);

			$reflexionenByLveLv = array_merge(
				$reflexionenByLveLv,
				$checkedReflexionen
			);
		}

		// Übersicht Reflexionen
		// -------------------------------------------------------------------------------------------------------------
		$showReflexionenUebersicht = $isKfl || $isStgl || $isAdmin;
		$reflexionenUebersichtData = [];

		if ($showReflexionenUebersicht && $allReflexionszeitraumAbgeschlossen)
		{
			$reflexionenUebersichtData = $this->buildReflexionenUebersichtData($reflexionenByLveLv);
		}

		$resultData = [
			'reflexionen' => $reflexionenByLveLv,
			'reflexionenUebersicht' => [
				'show' => $showReflexionenUebersicht,
				'data' => $reflexionenUebersichtData,
			]
		];

		$this->terminateWithSuccess($resultData);
	}

	public function getReflexionDataByLvTemplate()
	{
		$lehrveranstaltung_template_id = $this->input->get('lehrveranstaltung_template_id');
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');

		$result = $this->LvevaluierungLehrveranstaltungModel->getLveLvsByLvTemplateId(
			$lehrveranstaltung_template_id,
			$studiensemester_kurzbz
		);
		$lveLvs = hasData($result) ? getData($result) : [];

		// Permission check
		$isKfl = $this->permissionlib->isBerechtigt(self::BERECHTIGUNG_KF);
		$isAdmin = $this->permissionlib->isBerechtigt(self::BERECHTIGUNG_ADMIN);

		if (!$isKfl && !$isAdmin)
		{
			$this->terminateWithError('Permission denied');
		}

		// Reflexionen
		// -------------------------------------------------------------------------------------------------------------
		$reflexionenByLveLv = [];
		$allReflexionszeitraumAbgeschlossen = true;

		foreach ($lveLvs as $lveLv)
		{
			$lves = $this->getLvevaluierungByLveLvOrFail($lveLv->lvevaluierung_lehrveranstaltung_id);

			foreach ($lves as $lve)
			{
				// Skip if Evaluation period is still running or students did not get codes yet
				if (!$this->hasSetEvaluierungszeitraum($lve)) continue;
				if (!$this->hasSentEvaluierungscodes($lve)) continue;
				if (!$this->isEvaluierungszeitraumAbgeschlossen($lve)) continue;
				if (!$this->isReflexionszeitraumAbgeschlossen($lve))
				{
					$allReflexionszeitraumAbgeschlossen = false;
					continue;
				}

				// Build Reflexionen
				$reflexionen = $this->buildReflexionenByLve($lve, $lveLv);

				$checkedReflexionen = $this->addZeitfensterAndBearbeitungsChecks(
					$reflexionen,
					$lve,
					$lveLv,
					$isKfl,
					false
				);

				$reflexionenByLveLv = array_merge(
					$reflexionenByLveLv,
					$checkedReflexionen
				);
			}
		}

		// Übersicht Reflexionen
		// -------------------------------------------------------------------------------------------------------------
		$showReflexionenUebersicht = $isKfl || $isAdmin;
		$reflexionenUebersichtData = [];

		if ($showReflexionenUebersicht && $allReflexionszeitraumAbgeschlossen)
		{
			$reflexionenUebersichtData = $this->buildReflexionenUebersichtData($reflexionenByLveLv);
		}

		$resultData = [
			'reflexionen' => [], // NOTE: leeres array statt $reflexionenByLveLv, da im frontend pro LV nachgeladen wird
			'reflexionenUebersicht' => [
				'show' => $showReflexionenUebersicht,
				'data' => $reflexionenUebersichtData,
			]
		];

		$this->terminateWithSuccess($resultData);
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


		$lektorOfLve = $this->evaluationlib->getLehrendeByLve($lve, $lveLv, true);
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

	/**
	 * Builds reflexion dataset for a given LVE evaluation.
	 *
	 * Combines existing Reflexionen with expected lecturers derived from the Evaluierungsebene (Gruppe or Gesamt-LV)
	 * Ensures that every expected lecturer has a reflexion entry, even if none exists yet (null reflexion fallback).
	 *
	 * @param $lve
	 * @param $lveLv
	 * @param $lvLeitung
	 * @return array
	 */
	private function buildReflexionenByLve($lve, $lveLv)
	{
		$data = [];

		// Get Reflexionen
		$result = $this->LvevaluierungReflexionModel->loadWhere([
			'lvevaluierung_id' => $lve->lvevaluierung_id,
		]);

		$reflexionen = hasData($result) ? getData($result) : [];

		// Get all Lehrende of Lve that have to do Reflexion
		$lektoren = $this->evaluationlib->getLehrendeByLve($lve, $lveLv, true);

		$lektorenByUid = [];
		foreach ($lektoren as $lektor) {
			$lektorenByUid[$lektor->uid] = $lektor;
		}

		// Store lektoren with reflexionen done
		$reflexionenUids = [];

		foreach ($reflexionen as $reflexion)
		{
			$reflexionenUids[] = $reflexion->mitarbeiter_uid;

			// Lektor holen (einfach gehalten → über LV)
			$lektor = isset($lektorenByUid[$reflexion->mitarbeiter_uid])
				? $lektorenByUid[$reflexion->mitarbeiter_uid]
				: null;

			$leGruppeBezeichnung = '';
			if ($lveLv->lv_aufgeteilt)
			{
				$result = $this->evaluationlib->getLehreinheitgruppenByLe($lve->lehreinheit_id);
				$leGruppeBezeichnung = hasData($result) ? getData($result)[0]->gruppe_bezeichnung : '';
			}
			else
			{

				$result = $this->evaluationlib->getLehreinheitgruppenByLv($lveLv->lehrveranstaltung_id, $lveLv->studiensemester_kurzbz, $reflexion->mitarbeiter_uid);
				$leGruppen = hasData($result) ? getData($result) : [];
				$leGruppeBezeichnung = implode(', ', array_column($leGruppen, 'gruppe_bezeichnung'));
			}

			$data[] = [
				'lvevaluierung_reflexion_id' => $reflexion->lvevaluierung_reflexion_id,
				'lvevaluierung_id' => $lve->lvevaluierung_id,
				'mitarbeiter_uid' => $reflexion->mitarbeiter_uid,
				'lveReflexion' => $reflexion,
				'vorname' => $lektor->vorname,
				'nachname' => $lektor->nachname,
				'isVerpflichtend' => $lveLv->lv_aufgeteilt ? true : $lektor->isLvLeitung,
				'isLvLeitung' => $lektor->isLvLeitung,
				'gruppeBezeichnung'	=> $leGruppeBezeichnung
			];
		}

		// Add wrapper for missing Reflexionen
		foreach ($lektoren as $lektor)
		{
			if (in_array($lektor->uid, $reflexionenUids)) continue;

			$leGruppeBezeichnung = '';
			if ($lveLv->lv_aufgeteilt)
			{
				$result = $this->evaluationlib->getLehreinheitgruppenByLe($lve->lehreinheit_id);
				$leGruppeBezeichnung = hasData($result) ? getData($result)[0]->gruppe_bezeichnung : '';
			}
			else
			{

				$result = $this->evaluationlib->getLehreinheitgruppenByLv($lveLv->lehrveranstaltung_id, $lveLv->studiensemester_kurzbz, $lektor->uid);
				$leGruppen = hasData($result) ? getData($result) : [];
				$leGruppeBezeichnung = implode(', ', array_column($leGruppen, 'gruppe_bezeichnung'));
			}

			$data[] = [
				'lvevaluierung_reflexion_id' => null,
				'lvevaluierung_id' => $lve->lvevaluierung_id,
				'mitarbeiter_uid' => $lektor->uid,
				'lveReflexion' => null,
				'vorname' => $lektor->vorname,
				'nachname' => $lektor->nachname,
				'isVerpflichtend' => $lveLv->lv_aufgeteilt ? true : $lektor->isLvLeitung,
				'isLvLeitung' => $lektor->isLvLeitung,
				'gruppeBezeichnung' => $leGruppeBezeichnung
			];
		}

		// Sort: LV-Leitung first
		usort($data, function ($a, $b)
		{
			if ($a['isLvLeitung'] === $b['isLvLeitung']) return 0;
			return $a['isLvLeitung'] ? -1 : 1;
		});

		return $data;
	}

	private function buildReflexionenUebersichtData($reflexionenData)
	{
		// Get Antwort-Praesenz values
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungReflexionAntwortPraesenz_model', 'LvevaluierungReflexionAntwortPraesenzModel');
		$result = $this->LvevaluierungReflexionAntwortPraesenzModel->loadByUserLang();
		$antwortenPraesenz = hasData($result) ? getData($result) : [];

		// Get Antwort-Nachvollziehbarkeit values
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungReflexionAntwortNachvollziehbar_model', 'LvevaluierungReflexionAntwortNachvollziehbarModel');
		$result = $this->LvevaluierungReflexionAntwortNachvollziehbarModel->loadByUserLang();
		$antwortenNachvollziehbar = hasData($result) ? getData($result) : [];


		// Init basic structure
		$reflexionenUebersichtData = [
			'verpflichtend' => [
				'praesenz_kurzbz' => [
					'label' => 'LV in Präsenz',
					'values' => []
				],
				'nachvollziehbar_kurzbz' => [
					'label' => 'Ergebnisse nachvollziehbar',
					'values' => []
				],
				'massnahmennoetig' => [
					'label' => 'Maßnahmen abgeleitet',
					'values' => [
						'ja' => ['label' => 'Ja', 'anzahl' => 0],
						'nein' => ['label' => 'Nein', 'anzahl' => 0]
					]
				]
			],
			'optional' => [
				'praesenz_kurzbz' => [
					'label' => 'LV in Präsenz',
					'values' => []
				],
				'nachvollziehbar_kurzbz' => [
					'label' => 'Ergebnisse nachvollziehbar',
					'values' => []
				],
				'massnahmennoetig' => [
					'label' => 'Maßnahmen abgeleitet',
					'values' => [
						'ja' => ['label' => 'Ja', 'anzahl' => 0],
						'nein' => ['label' => 'Nein', 'anzahl' => 0]
					]
				]
			],
			'meta' => [
				'gesamtReflexionen' => 0,
				'ausgefuellteReflexionen' => 0,
				'ausfuellquote' => 0,
				'ausfuellquoteProzent' => 0,
				'hasOptionalReflexionen' => false
			]
		];

		// Init Antwortmöglichkeiten with 0 (e.g. ja = 0, nein = 0, unknown = 0,...)
		foreach ($antwortenPraesenz as $antwort) {
			$key = $antwort->praesenz_kurzbz;
			$label = $antwort->bezeichnung;

			$reflexionenUebersichtData['verpflichtend']['praesenz_kurzbz']['values'][$key] = [
				'label' => $label,
				'anzahl' => 0
			];
			$reflexionenUebersichtData['optional']['praesenz_kurzbz']['values'][$key] = [
				'label' => $label,
				'anzahl' => 0
			];
		}

		foreach ($antwortenNachvollziehbar as $antwort) {
			$key = $antwort->nachvollziehbar_kurzbz;
			$label = $antwort->bezeichnung;

			// Shorten Bezeichnung, that includes an example inside brackets
			// Concrete: Kann ich nicht beurteilen (zB. weil nicht genügend N) --> remove the example part
			$pos = strpos($label, '(');
			if ($pos !== false) {
				$label = trim(substr($label, 0, $pos));
			}

			$reflexionenUebersichtData['verpflichtend']['nachvollziehbar_kurzbz']['values'][$key] = [
				'label' => $label,
				'anzahl' => 0
			];
			$reflexionenUebersichtData['optional']['nachvollziehbar_kurzbz']['values'][$key] = [
				'label' => $label,
				'anzahl' => 0
			];
		}

		$gesamtVerpflichtendeReflexionen = 0;
		$ausgefuellteVerpflichtendeReflexionen = 0;
		$showUebersichtOptionale = false;

		// Build
		foreach ($reflexionenData as $item) {
			$isVerpflichtend = $item['isVerpflichtend'];

			// true if at least one optional Reflexion done
			if (!$isVerpflichtend && $item['lveReflexion'] !== null) {
				$showUebersichtOptionale = true;
			}

			// Gesamt verpflichtend zählen (egal ob ausgefüllt oder nicht)
			if ($isVerpflichtend) {
				$gesamtVerpflichtendeReflexionen++;
			}

			// skip if no reflexion
			if ($item['lveReflexion'] === null) continue;

			// Count ausgefüllte verpflichtende
			if ($isVerpflichtend) {
				$ausgefuellteVerpflichtendeReflexionen++;
			}

			$pflichtStatus = $isVerpflichtend ? 'verpflichtend' : 'optional';

			$lveReflexion = $item['lveReflexion'];

			// praesenz
			if (isset($reflexionenUebersichtData[$pflichtStatus]['praesenz_kurzbz']['values'][$lveReflexion->praesenz_kurzbz])) {
				$reflexionenUebersichtData[$pflichtStatus]['praesenz_kurzbz']['values'][$lveReflexion->praesenz_kurzbz]['anzahl']++;
			}

			// nachvollziehbar
			if (isset($reflexionenUebersichtData[$pflichtStatus]['nachvollziehbar_kurzbz']['values'][$lveReflexion->nachvollziehbar_kurzbz])) {
				$reflexionenUebersichtData[$pflichtStatus]['nachvollziehbar_kurzbz']['values'][$lveReflexion->nachvollziehbar_kurzbz]['anzahl']++;
			}

			// massnahmennoetig
			$key = $lveReflexion->massnahmennoetig ? 'ja' : 'nein';
			$reflexionenUebersichtData[$pflichtStatus]['massnahmennoetig']['values'][$key]['anzahl']++;


		}
		// Calculate Ausfüllquote
		$ausfuellquote = $gesamtVerpflichtendeReflexionen > 0
			? $ausgefuellteVerpflichtendeReflexionen / $gesamtVerpflichtendeReflexionen
			: 0;

		$reflexionenUebersichtData['meta'] = [
			'gesamtVerpflichtendeReflexionen' => $gesamtVerpflichtendeReflexionen, // Reflexionen to be done
			'ausgefuellteVerpflichtendeReflexionen' => $ausgefuellteVerpflichtendeReflexionen, // indeed done
			'ausfuellquote' => $ausfuellquote,
			'ausfuellquoteProzent' => round($ausfuellquote * 100, 1),
			'showUebersichtOptionale' => $showUebersichtOptionale,    // at least one optional done
		];

		return $reflexionenUebersichtData;
	}

	private function filterVisibleReflexionenForLehrende(
		$reflexionen,
		$lveLv
	)
	{
		$data = [];

		$lvLeitung = $this->evaluationlib->getLvLeitung($lveLv->lehrveranstaltung_id, $lveLv->studiensemester_kurzbz);

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
				if (
					$this->_uid !== $lvLeitung[0]->mitarbeiter_uid &&
					$reflexion['mitarbeiter_uid'] === $lvLeitung[0]->mitarbeiter_uid
				)
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
		$isStgl
	)
	{
		$data = [];

		$check = $this->checkBearbeitungOffenForReflexion($lve);

		$lvLeitung = $this->evaluationlib->getLvLeitung($lveLv->lehrveranstaltung_id, $lveLv->studiensemester_kurzbz);

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

			$isReadOnly =
				($isKfl || $isStgl) && $reflexion['mitarbeiter_uid'] !== $this->_uid

				|| (
					!$lveLv->lv_aufgeteilt
					&& !$isKfl
					&& !$isStgl
					&& $reflexion['mitarbeiter_uid'] === $lvLeitung[0]->mitarbeiter_uid
					&& $this->_uid !== $lvLeitung[0]->mitarbeiter_uid
				);

			if ($isReadOnly)
			{
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
		return $this->LvevaluierungZeitfensterModel->isZeitfensterOffenLve('stgauswahl', $lvevaluierung_lehrveranstaltung_id);
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

	/**
	 * Get MALVE by Studiengang and Studiensemester.
	 *
	 * If malve is found, it has been set to 'abgeschlossen' for this STG.
	 * @return void
	 */
	public function getMalveByStg()
	{
		$studiengang_kz = $this->input->get('studiengang_kz');
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');

		$this->load->model('organisation/Studiengang_model', 'StudiengangModel');
		$result = $this->StudiengangModel->load($studiengang_kz);

		if (hasData($result))
		{
			$studiengang = getData($result)[0];

			$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungMalve_model', 'LvevaluierungMalveModel');
			$result = $this->LvevaluierungMalveModel->loadWhere([
				'oe_kurzbz' => $studiengang->oe_kurzbz,
				'studiensemester_kurzbz' => $studiensemester_kurzbz
			]);

			$data = $this->getDataOrTerminateWithError($result);

			$this->terminateWithSuccess($data);
		}
		else
		{
			$this->terminateWithError('No Studiengang found to get MALVE data');
		}
	}

	/**
	 * Save MALVE by Studiengang and Studiensemester.
	 *
	 * Saving MALVE will give info that malve is 'abgeschlossen' for this STG.
	 *
	 * @return void
	 */
	public function saveMalveByStg()
	{
		$studiengang_kz = $this->input->post('studiengang_kz');
		$studiensemester_kurzbz = $this->input->post('studiensemester_kurzbz');

		$this->load->model('organisation/Studiengang_model', 'StudiengangModel');
		$result = $this->StudiengangModel->load($studiengang_kz);

		if (hasData($result))
		{
			$studiengang = getData($result)[0];

			$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungMalve_model', 'LvevaluierungMalveModel');

			// Check if already exist
			$result = $this->LvevaluierungMalveModel->loadWhere([
				'oe_kurzbz' => $studiengang->oe_kurzbz,
				'studiensemester_kurzbz' => $studiensemester_kurzbz
			]);

			// If not exist
			if (!hasData($result))
			{
				// Insert
				$result = $this->LvevaluierungMalveModel->insert([
					'oe_kurzbz' => $studiengang->oe_kurzbz,
					'studiensemester_kurzbz' => $studiensemester_kurzbz,
					'insertvon' => $this->_uid
				]);

				if (isError($result))
				{
					$this->terminateWithError(getError($result));
				}
				else
				{
					$insertId = getData($result);

					// Get new record
					$record = $this->LvevaluierungMalveModel->load($insertId);

					if (!hasData($record))
					{
						$this->terminateWithError('Inserted record not found');
					}

					$this->terminateWithSuccess(getData($record));
				}
			}
		}
		else
		{
			$this->terminateWithError('No Studiengang found to get MALVE data');
		}
	}

	// -----------------------------------------------------------------------------------------------------------------
	// Evaluation Kompetenzfeld
	// -----------------------------------------------------------------------------------------------------------------
	/**
	 * Get Kompetenzfelder for which the user is entitled.
	 *
	 * @return void
	 */
	public function getEntitledKfs()
	{
		$this->load->model('organisation/Organisationseinheit_model', 'OrganisationseinheitModel');

		// Kompetenzfelder for KF
		$entitledOes = $this->permissionlib->getOE_isEntitledFor(self::BERECHTIGUNG_KF) ?: [];

		// Kompetenzfelder for Admins
		if ($this->permissionlib->isBerechtigt(self::BERECHTIGUNG_ADMIN))
		{
			$entitledOes = $this->permissionlib->getOE_isEntitledFor(self::BERECHTIGUNG_ADMIN) ?: [];
		}

		$condition = '
                oe_kurzbz IN (\'' . implode('\',\'', $entitledOes) . '\') AND
                aktiv = TRUE AND
                ( 
                	organisationseinheittyp_kurzbz = \'Kompetenzfeld\' OR 
			 		organisationseinheittyp_kurzbz = \'Fachgebiet\' 
				)
            ';

		$this->OrganisationseinheitModel->addSelect('*');
		$this->OrganisationseinheitModel->addSelect('organisationseinheittyp_kurzbz || \' \' || bezeichnung AS bezeichnung');

		$result = $this->OrganisationseinheitModel->loadWhere($condition);

		$oes = $this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($oes);
	}

	/**
	 * Get Lv-List Data of all Lvs that shall be evaluated in given Studiensemester and Kompetenzfeld.
	 * (from Lvevaluierung-Lehrveranstaltung table)
	 *
	 * @return void
	 */
	public function getLvListByKf()
	{
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');
		$oe_kurzbz = $this->input->get('oe_kurzbz');

		// Permission check
		$entitledOes = $this->permissionlib->getOE_isEntitledFor(self::BERECHTIGUNG_KF) ?: [];
		$isAdmin = $this->permissionlib->isBerechtigt(self::BERECHTIGUNG_ADMIN);
	$this->addMeta('uid', $this->_uid);
	$this->addMeta('$isAdmin', $isAdmin);
		if (!in_array($oe_kurzbz, $entitledOes) && !$isAdmin) $this->terminateWithError('Permission denied');
	$this->addMeta('$entitledOes', $entitledOes);
	$this->addMeta('$oe_kurzbz', $oe_kurzbz);

		// Get LV List
		$result = $this->LvevaluierungLehrveranstaltungModel->getLveLvsByKf(
			$studiensemester_kurzbz,
			$oe_kurzbz
		);
		$data = $this->getDataOrTerminateWithError($result);

		// Get Ruecklauf data
		$lveLvIds = array_column($data, 'lvevaluierung_lehrveranstaltung_id');
		$result = $this->LvevaluierungCodeModel->getAggregatedRuecklaufDataByLveLv($lveLvIds);
		$rlData = hasData($result) ? getData($result) : [];

		// Add Ruecklauf values to data
		foreach ($data as $item)
		{
			$lveLvId = $item->lvevaluierung_lehrveranstaltung_id;
			$agg = current(array_filter($rlData, function ($r) use ($lveLvId)
			{
				return $r->lvevaluierung_lehrveranstaltung_id === $lveLvId;
			}));
			$item->codesAusgegeben = $agg ? $agg->sum_codes_ausgegeben : 0;
			$item->submittedCodes = $agg ? $agg->count_submitted_codes : 0;
			$item->ruecklaufQuote = ($agg && $agg->ruecklaufquote !== null)
				? (float)$agg->ruecklaufquote
				: null;
		}

		$this->terminateWithSuccess($data);
	}

	/**
	 * Get list of all Quellkurse that shall be evaluated in given Studiensemester and Kompetenzfeld.
	 * (from Lvevaluierung-Lehrveranstaltung table)
	 *
	 * @return void
	 */
	public function getLvTemplateListByKf()
	{
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');
		$oe_kurzbz = $this->input->get('oe_kurzbz');

		// Permission check
		$entitledOes = $this->permissionlib->getOE_isEntitledFor(self::BERECHTIGUNG_KF) ?: [];
		$isAdmin = $this->permissionlib->isBerechtigt(self::BERECHTIGUNG_ADMIN);

		if (!in_array($oe_kurzbz, $entitledOes) && !$isAdmin) $this->terminateWithError('Permission denied');

		// Get LV Templates
		$result = $this->LvevaluierungLehrveranstaltungModel->getLveLvTemplatesByKf(
			$studiensemester_kurzbz,
			$oe_kurzbz
		);
		$lvTemplates = $this->getDataOrTerminateWithError($result);

		// LV Template IDs
		$lvTemplateIds = array_column($lvTemplates, 'lehrveranstaltung_id');

		// Exit if no LV Templates found
		if (count($lvTemplateIds) === 0) $this->terminateWithSuccess([]);

		// Aggregated Rücklaufdata
		$result = $this->LvevaluierungCodeModel->getAggregatedRuecklaufDataByLvTemplateIds($lvTemplateIds, $studiensemester_kurzbz);
		$rlData = hasData($result) ? getData($result) : [];

		// Helper: set key to identify by ID
		$rlDataByTemplate = [];
		foreach ($rlData as $item)
		{
			$rlDataByTemplate[$item->lehrveranstaltung_template_id] = $item;
		}

		// Add Rücklaufvalues to Lv Templates
		foreach ($lvTemplates as $lvTemplate)
		{
			$agg = $rlDataByTemplate[$lvTemplate->lehrveranstaltung_id] ?? null;

			$lvTemplate->codesAusgegeben = $agg ? $agg->sum_codes_ausgegeben : 0;
			$lvTemplate->submittedCodes = $agg ? $agg->count_submitted_codes : 0;
			$lvTemplate->ruecklaufQuote = ($agg && $agg->ruecklaufquote !== null)
				? (float)$agg->ruecklaufquote
				: null;
		}

		$this->terminateWithSuccess($lvTemplates);
	}

	/**
	 * Get MALVE by Kompetenzfeld and Studiensemester.
	 *
	 * If malve is found, it has been set to 'abgeschlossen' for this STG.
	 * @return void
	 */
	public function getMalveByKf()
	{
		$oe_kurzbz = $this->input->get('oe_kurzbz');
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');

		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungMalve_model', 'LvevaluierungMalveModel');
		$result = $this->LvevaluierungMalveModel->loadWhere([
			'oe_kurzbz' => $oe_kurzbz,
			'studiensemester_kurzbz' => $studiensemester_kurzbz
		]);

		$data = $this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($data);
	}

	/**
	 * Save MALVE by Kompetenzfeld and Studiensemester.
	 *
	 * Saving MALVE will give info that malve is 'abgeschlossen' for this Kompetenzfeld.
	 *
	 * @return void
	 */
	public function saveMalveByKF()
	{
		$oe_kurzbz = $this->input->post('oe_kurzbz');
		$studiensemester_kurzbz = $this->input->post('studiensemester_kurzbz');

		$isKfl = $this->evaluationlib->isKFL($this->_uid, null, $oe_kurzbz);
		$isAdmin = $this->permissionlib->isBerechtigt(self::BERECHTIGUNG_ADMIN);

		if (!$isKfl && !$isAdmin) $this->terminateWithError('Permission denied');

		// Check if OE is Kompetenzfeld
		$this->load->model('organisation/Organisationseinheit_model', 'OrganisationseinheitModel');
		$result = $this->OrganisationseinheitModel->loadWhere([
			'oe_kurzbz' => $oe_kurzbz,
			'organisationseinheittyp_kurzbz' => 'Kompetenzfeld',
			'aktiv' => TRUE
		]);

		if (hasData($result))
		{
			$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungMalve_model', 'LvevaluierungMalveModel');

			// Check if MALVE already exist
			$result = $this->LvevaluierungMalveModel->loadWhere([
				'oe_kurzbz' => $oe_kurzbz,
				'studiensemester_kurzbz' => $studiensemester_kurzbz
			]);

			// If not exist
			if (!hasData($result))
			{
				// Insert
				$result = $this->LvevaluierungMalveModel->insert([
					'oe_kurzbz' => $oe_kurzbz,
					'studiensemester_kurzbz' => $studiensemester_kurzbz,
					'insertvon' => $this->_uid
				]);

				if (isError($result))
				{
					$this->terminateWithError(getError($result));
				}
				else
				{
					$insertId = getData($result);

					// Get new record
					$record = $this->LvevaluierungMalveModel->load($insertId);

					if (!hasData($record))
					{
						$this->terminateWithError('Inserted record not found');
					}

					$this->terminateWithSuccess(getData($record));
				}
			}
		}
		else
		{
			$this->terminateWithError('No Kompetenzfeld found to get MALVE data');
		}
	}

	/**
	 * Update reviewed Evaluierungen for given Lehrveranstaltung, reviewed by Kompetenzfeldleitung.
	 *
	 * @return void
	 */
	public function updateReviewedLvInKf()
	{
		$lvevaluierung_lehrveranstaltung_id = $this->input->post('lvevaluierung_lehrveranstaltung_id');
		$isReviewed = $this->input->post('isReviewed');

		$result = $this->LvevaluierungLehrveranstaltungModel->update(
			$lvevaluierung_lehrveranstaltung_id,
			['reviewed_kf' => $isReviewed]
		);
		$data = $this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($data);
	}

	//------------------------------------------------------------------------------------------------------------------

	private function hasSetEvaluierungszeitraum($lve)
	{
		return $lve->startzeit !== null && $lve->endezeit !== null;
	}

	private function hasSentEvaluierungscodes($lve)
	{
		return $lve->codes_gemailt === true && $lve->codes_ausgegeben !== null;
	}

	private function isEvaluierungszeitraumAbgeschlossen($lve)
	{
		// Genereller Evaluierungsansicht öffnen, wenn Codes versendet und Evaluierungszeitfenster abgeschlossen
		$now = (new DateTime())->format('Y-m-d H:i:s');
		return !(
			$lve->codes_ausgegeben === null ||
			$lve->endezeit === null ||
			$now < $lve->endezeit
		);
	}

	private function isReflexionszeitraumAbgeschlossen($lve)
	{
		return $this->LvevaluierungReflexionModel->isReflexionszeitraumAbgeschlossenForLve($lve->lvevaluierung_id);
	}

	private function getEvaluationViewOpenMsgContextText($lve, $isLvAufgeteilt)
	{
		if ($isLvAufgeteilt)
		{
			$result = $this->evaluationlib->getLehreinheitgruppenByLe($lve->lehreinheit_id);
			$gruppe = hasData($result) ? getData($result)[0]->gruppe_bezeichnung : '';

			return ' für Gruppe ' . $gruppe;
		}

		return ' für Gesamt-LV';
	}

	private function getEvaluationViewOpenMsgContextTextByLveLv($lveLv)
	{
		return ' für ' . $lveLv->kurzbzlang. '-'. $lveLv->semester. ': '. $lveLv->bezeichnung. ' - '. $lveLv->orgform_kurzbz;
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
			$end = new DateTime($item->endezeit);

			// duration in minutes
			return round(($end->getTimestamp() - $start->getTimestamp()) / 60, 2);
		}, $lveCodes);

		return $durations;
	}
	public function getPeriodTimes($lves)
	{
		$startTimes = array_column($lves, 'startzeit');
		$endTimes = array_column($lves, 'endezeit');

		// Reflexions min Start / max Ende
		$minStartzeitReflexionszeit = null;
		$maxEndezeitReflexionszeit = null;
		if ($startTimes)
		{
			$minStartzeitReflexionszeit = clone new DateTime(min($endTimes));
			$minStartzeitReflexionszeit->modify('+1 day');

			$maxEndezeitReflexionszeit = clone new DateTime(max($endTimes));
			$maxEndezeitReflexionszeit->modify($this->config->item('reflexionZeitfensterDauer'));
		}

		return [
			'minStartzeit' => $startTimes ? min($startTimes) : null,
			'maxEndezeit'   => $endTimes ? max($endTimes) : null,
			'minStartzeitReflexion'   => $startTimes ? $minStartzeitReflexionszeit->format('d.m.Y') : null,
			'maxEndezeitReflexion'   => $endTimes ? $maxEndezeitReflexionszeit->format('d.m.Y') : null,
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
						'hodgesLehmann' => [
							'actYear' => 0,
							'actYearMin1' => 0,
							'actYearMin2' => 0,
						]    // default
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
