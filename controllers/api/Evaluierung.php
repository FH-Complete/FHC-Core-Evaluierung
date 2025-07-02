<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Evaluierung extends FHCAPI_Controller
{
	public function __construct()
	{
		/** @noinspection PhpUndefinedClassConstantInspection */
		parent::__construct(array(
				'getLvEvaluierungCode' => self::PERM_ANONYMOUS,
				'getLvEvaluierung' => self::PERM_ANONYMOUS,
				'getInitFragebogen' => self::PERM_ANONYMOUS,
				'getLvInfo' => self::PERM_ANONYMOUS,
				'setStartzeit' => self::PERM_ANONYMOUS,
				'setEndezeit' => self::PERM_ANONYMOUS,
				'saveAntwortenAndSetEndezeit' => self::PERM_ANONYMOUS
			)
		);

		$this->load->library('extensions/FHC-Core-Evaluierung/EvaluierungLib');

		$this->load->model('extensions/FHC-Core-Evaluierung/Lvevaluierung_model', 'LvevaluierungModel');
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungCode_model', 'LvevaluierungCodeModel');
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungLehrveranstaltung_model', 'LvevaluierungLehrveranstaltungModel');
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogenGruppe_model', 'LvevaluierungFragebogenGruppeModel');
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogenFrage_model', 'LvevaluierungFragebogenFrageModel');
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogenFrageAntwort_model', 'LvevaluierungFragebogenFrageAntwortModel');

		// Load language phrases
		$this->loadPhrases([
			'fragebogen'
		]);
	}

	/**
	 * Get LvEvaluierungCode by Code.
	 *
	 * @return void
	 */
	public function getLvEvaluierungCode()
	{
		$code = $this->input->get('code');

		$result = $this->LvevaluierungCodeModel->loadWhere([
			'code' => $code
		]);

		$data = $this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess(current($data));
	}

	/**
	 * Get LvEvaluierung by LvevaluierungID.
	 *
	 * @return void
	 */
	public function getLvEvaluierung()
	{
		$lvevaluierung_id = $this->input->get('lvevaluierung_id');

		$result = $this->LvevaluierungModel->load($lvevaluierung_id);

		$data = $this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess(current($data));
	}

	/**
	 * Get initial Fragebogen by FragebogenID.
	 *
	 * @return void Array of the Fragebogens' Fragebogengruppen, its Fragen and its possible Antworten.
	 */
	public function getInitFragebogen()
	{
		$fragebogen_id = $this->input->get('fragebogen_id');

		// Get Fragebogengruppen
		$result = $this->LvevaluierungFragebogenGruppeModel->getFragebogengruppeByFragebogen($fragebogen_id);
		$fragebogengruppen = hasData($result) ? getData($result) : [];

		// Result array
		$initFragebogen = [];

		// Loop Fragebogengruppen
		foreach ($fragebogengruppen as $fragebogengruppe)
		{
			$fragebogengruppe->fbFrage = [];

			// Get Fragen by Fragebogengruppe
			$result = $this->LvevaluierungFragebogenFrageModel->getFragenByFragebogengruppe(
				$fragebogengruppe->lvevaluierung_fragebogen_gruppe_id
			);

			$fragen = hasData($result) ? getData($result) : [];

			// Loop Fragen
			foreach ($fragen as $frage)
			{
				// Get Antworten by Frage
				$result = $this->LvevaluierungFragebogenFrageAntwortModel->getAntwortenByFrage(
					$frage->lvevaluierung_frage_id
				);

				$frage->fbFrageAntwort = hasData($result) ? getData($result) : [];

				// Append Frage to Fragegruppen Fragen array
				$fragebogengruppe->fbFrage[] = $frage;
			}

			// Append Fragebogengruppe to result array
			$initFragebogen[] = $fragebogengruppe;
		}

		// Return result array
		$this->terminateWithSuccess($initFragebogen);
	}

	/**
	 * Get Lehrveranstaltung Infos and its lecturers by given LvevaluierungLehrveranstaltungID.
	 *
	 * @return void
	 */
	public function getLvInfo()
	{
		$lvevaluierung_lehrveranstaltung_id = $this->input->get('lvevaluierung_lehrveranstaltung_id');

		// Get Lvevaluierung-Lehrveranstaltung
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungLehrveranstaltung_model', 'LvevaluierungLehrveranstaltungModel');
		$result = $this->LvevaluierungLehrveranstaltungModel->load($lvevaluierung_lehrveranstaltung_id);

		if (hasData($result))
		{
			$data = getData($result)[0];

			// Get LvInfos and lecturers by LehrveranstaltungID and Studiensemester
			$result = $this->evaluierunglib->getLvInfo($data->lehrveranstaltung_id, $data->studiensemester_kurzbz);

			$this->terminateWithSuccess($result);
		}
		else
		{
			$this->terminateWithError('No Lv-Evaluierung-Lehrveranstaltung found');
		}
	}

	/**
	 * Start Evaluierung by setting Startzeit.
	 *
	 * @return void
	 */
	public function setStartzeit(){
		$lvevaluierung_code_id = $this->input->post('lvevaluierung_code_id');

		// Validate Evaluation
		$this->_validateEvaluationBeforeSaving($lvevaluierung_code_id);

		$result = $this->LvevaluierungCodeModel->update(
			[
				'lvevaluierung_code_id' => $lvevaluierung_code_id
			],
			[
				'startzeit' => 'NOW()'
			]
		);

		if (isError($result)) $this->terminateWithError(getError($result));

		$this->terminateWithSuccess(true);
	}

	/**
	 * End Evaluierung by setting Endezeit.
	 * Before it is checked if Evaluierung was sent within the maximal Endezeit.
	 * Maximal Endezeit = Startzeit + Dauer + Buffer for request retry handling
	 *
	 * @return void
	 */
	public function setEndezeit(){
		$lvevaluierung_code_id = $this->input->post('lvevaluierung_code_id');

		// Check if Evaluierung time has exceeded
		$result = $this->evaluierunglib->checkIfEvaluierungTimeExceeded($lvevaluierung_code_id);
		if (isError($result))
		{
			$this->terminateWithError(getError($result));
		}

		// Validate Evaluation
		$this->_validateEvaluationBeforeSaving($lvevaluierung_code_id);

		// Update Endezeit
		$result = $this->LvevaluierungCodeModel->update(
			['lvevaluierung_code_id' => $lvevaluierung_code_id],
			['endezeit' => 'NOW()']
		);

		if (isError($result))
		{
			$this->terminateWithError(getError($result));
		}

		// On success
		$this->terminateWithSuccess(true);
	}

	/**
	 * Save Students' Antworten.
	 * @return void
	 */
	public function saveAntwortenAndSetEndezeit()
	{
		$lvevaluierung_code_id = $this->input->post('lvevaluierung_code_id');
		$data = $this->input->post('data');

		// Check if Evaluierung time has exceeded
		$result = $this->evaluierunglib->checkIfEvaluierungTimeExceeded($lvevaluierung_code_id);
		if (isError($result))
		{
			$this->terminateWithError(getError($result));
		}

		// Validate Evaluation
		$this->_validateEvaluationBeforeSaving($lvevaluierung_code_id);

		// Validate Antworten
		$result = $this->evaluierunglib->validateAntworten($data);
		$validatedAntworten = $this->getDataOrTerminateWithError($result);

		// Save Antworten
		$insertedIds = [];
		if (!empty($validatedAntworten))
		{
			$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungAntwort_model', 'LvevaluierungAntwortModel');
			$result = $this->LvevaluierungAntwortModel->saveAntworten($validatedAntworten);
			$insertedIds = $this->getDataOrTerminateWithError($result);
		}

		// Update Endezeit
		$result = $this->LvevaluierungCodeModel->update(
			['lvevaluierung_code_id' => $lvevaluierung_code_id],
			['endezeit' => 'NOW()']
		);

		if (isError($result))
		{
			$this->terminateWithError(getError($result));
		}

		$this->terminateWithSuccess($insertedIds);
	}

	/**
	 * Validate Evaluation by Evaluation Code ID.
	 *
	 * @param $lvevaluierung_code_id
	 * @return void
	 */
	private function _validateEvaluationBeforeSaving($lvevaluierung_code_id)
	{
		// Validate and get Evaluierung Code
		$result = $this->evaluierunglib->getValidatedLvevaluierungCode($lvevaluierung_code_id);
		if (isError($result)) $this->terminateWithError(getError($result));
		$lvevaluierungCode = getData($result);

		// Check if Evaluierung was already submitted
		$result = $this->evaluierunglib->checkIfEvaluierungAlreadySubmitted($lvevaluierungCode);
		if (isError($result)) $this->terminateWithError(getError($result));

		// Validate and get Evaluierung
		$result = $this->evaluierunglib->getValidatedLvevaluierung($lvevaluierungCode->lvevaluierung_id);
		if (isError($result)) $this->terminateWithError(getError($result));
		$lvevaluierung = getData($result);

		// Check if Evaluierung Period is valid (between start- and endezeit)
		$result = $this->evaluierunglib->checkIfEvaluierungPeriodIsValid($lvevaluierung);
		if (isError($result)) $this->terminateWithError(getError($result));

		// Validate and get Evaluierung-Lehrveranstaltung assignement
		$result = $this->evaluierunglib->getValidatedLvevaluierungLehrveranstaltung(
			$lvevaluierung->lvevaluierung_lehrveranstaltung_id
		);
		if (isError($result)) $this->terminateWithError(getError($result));
		$lvevaluierungLehrveranstaltung = getData($result);

		// Validate and get Lehrveranstaltung
		$result = $this->evaluierunglib->getValidatedLehrveranstaltung(
			$lvevaluierungLehrveranstaltung->lehrveranstaltung_id
		);
		if (isError($result)) $this->terminateWithError(getError($result));
		$lehrveranstaltung = getData($result);

		// Check if Lehrveranstaltung is evalubable
		$result = $this->evaluierunglib->checkIfLehrveranstaltungIsEvaluable($lehrveranstaltung);
		if (isError($result)) $this->terminateWithError(getError($result));
	}
}
