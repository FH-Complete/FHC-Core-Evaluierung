<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Evaluation extends FHCAPI_Controller
{
	public function __construct()
	{
		/** @noinspection PhpUndefinedClassConstantInspection */
		// todo check permissions
		parent::__construct(array(
				'getEvaluationDataByLve' => 'extension/lvevaluierung_init:r',
				'getEvaluationDataByLveLv' => 'extension/lvevaluierung_init:r',
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

		// For min/max duration
		$durations = $this->getDurations($submittedLveCodes);

		$data = array_merge(
			(array) $lveLv,
			(array) $lvData,
			['lvLeitungen' => $lvLeitungen],
			['lehrende' => $lehrende],
			['codes_ausgegeben' => $lve->codes_ausgegeben],
			['countSubmitted' => $countSubmitted],
			['ruecklaufquote' => $countSubmitted > 0 ? round(($countSubmitted / $lve->codes_ausgegeben) * 100, 2) : 0],
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
			['ruecklaufquote' => $countSubmitted > 0 ? round(($countSubmitted / $codesAusgegeben) * 100, 2) : 0],
			['startzeit' => $periodTimes['minStartzeit']],
			['endezeit' => $periodTimes['maxEndezeit']],
			['minDuration' => $durations ? min($durations) : 0],
			['maxDuration' => $durations ? max($durations) : 0]
		);

		$this->terminateWithSuccess($data);
	}

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
