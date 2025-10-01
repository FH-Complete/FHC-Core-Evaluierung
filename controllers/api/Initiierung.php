<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Initiierung extends FHCAPI_Controller
{
	public function __construct()
	{
		/** @noinspection PhpUndefinedClassConstantInspection */
		parent::__construct(array(
				'getLveLvs' => 'extension/lvevaluierung_init:r',
				'getLveLvsWithLes' => 'extension/lvevaluierung_init:r',
				'getLveLvDataGroups' => 'extension/lvevaluierung_init:r',
				'getLveLvPrestudenten' => 'extension/lvevaluierung_init:r',
				'getLvEvaluierungenByID' => 'extension/lvevaluierung_init:r',
				'updateLvAufgeteilt' => 'extension/lvevaluierung_init:rw',
				'saveOrUpdateLvevaluierung' => 'extension/lvevaluierung_init:rw',
				'generateCodesAndSendLinksToStudents' => 'extension/lvevaluierung_init:rw',
				'updateEvalStatusAndChecks' => 'extension/lvevaluierung_init:rw',
			)
		);

		$this->load->library('extensions/FHC-Core-Evaluierung/InitiierungLib');

		$this->load->model('extensions/FHC-Core-Evaluierung/Lvevaluierung_model', 'LvevaluierungModel');
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungLehrveranstaltung_model', 'LvevaluierungLehrveranstaltungModel');
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungCode_model', 'LvevaluierungCodeModel');
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungPrestudent_model', 'LvevaluierungPrestudentModel');

		// Load language phrases
		$this->loadPhrases([
			'ui'
		]);

		$this->_uid = getAuthUid();
	}

	/**
	 * Get Lvs that are scheduled for evaluation in the given Studiensemester, where the logged-in user is assigned
	 * to at least one Lehreinheit as a Lektor.
	 */
	public function getLveLvs()
	{
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');
		$lehrveranstaltung_id = $this->input->get('lehrveranstaltung_id'); // can be null

		$result = $this->LvevaluierungLehrveranstaltungModel->getLveLvs(
			$studiensemester_kurzbz,
			$lehrveranstaltung_id
		)
		;

		$data = $this->getDataOrTerminateWithError($result);

		// Add info if all students of LV received Mail with codes
		foreach ($data as &$item)
		{
			$isAllSent = $this->isAllSentLvEvaluierung($item->lvevaluierung_lehrveranstaltung_id);
			$item->isAllSent = $isAllSent;
		}

		$this->terminateWithSuccess($data);
	}
	/**
	 * Get Lvs, that are scheduled for evaluation, with their associated Lehreinheiten for the given Studiensemester,
	 * where the logged-in user is assigned to at least one Lehreinheit as a Lektor.
	 */
	public function getLveLvsWithLes()
	{
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');
		$lehrveranstaltung_id = $this->input->get('lehrveranstaltung_id'); // can be null

		$result = $this->LvevaluierungLehrveranstaltungModel->getLveLvsWithLes(
			$studiensemester_kurzbz,
			$lehrveranstaltung_id
		);

		$data = $this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($data);
	}

	/**
	 * Get grouped Data for Evaluierung by Gesamt-LV and for Evaluierung by Gruppenbasis.
	 * Returns LV data of given LVE-LV-ID, including its associated Gruppen and Lektoren and Studierenden.
	 *
	 * @return:
	 * lveLvDataGroupedByLv: returns data grouped by Lehrveranstaltung
	 * lveLvDataGroupedByLeUnique: returns data grouped by Lehreinheiten, but ONLY if each LE within the LV has UNIQUE
	 * lector+Gruppenzusammensetzung (purpose is: Students MUST be unique for each lector)
	 */
	public function getLveLvDataGroups()
	{
		$lvevaluierung_lehrveranstaltung_id = $this->input->get('lvevaluierung_lehrveranstaltung_id');
		$lvLeitungRequired = $this->config->item('lvLeitungRequired');
		$canSwitch = true;
		$canSwitchInfo = [];

		$lveLv = $this->getLvevaluierungLehrveranstaltungOrFail($lvevaluierung_lehrveranstaltung_id);

		// Get all Evaluierungen of that LV
		$lves = $this->getLvevaluierungByLveLvOrFail($lvevaluierung_lehrveranstaltung_id);

		// Base Data (raw rows with Lektoren + Gruppen info)
		$result = $this->LvevaluierungLehrveranstaltungModel->getLveLvWithLesAndGruppenById($lvevaluierung_lehrveranstaltung_id);
		$data = $this->getDataOrTerminateWithError($result);

		// Group Lektoren and Gruppen by Lehreinheit
		$groupedByLe = $this->initiierunglib->groupByLeAndAddData($data, $lvevaluierung_lehrveranstaltung_id);

		// Grouped data for Evaluierung by Gesamt-Lv
		$groupedByLv = $this->initiierunglib->groupByLvAndAddData(
			$groupedByLe,
			$lveLv->lehrveranstaltung_id,
			$lveLv->studiensemester_kurzbz
		);

		if ($this->config->item('filterLehreinheitenByUniqueLectorAndGruppen'))
		{
			// Filter only Lehreinheiten with unique Lector
			$groupedByLe = $this->initiierunglib->filterWhereUniqueLectorAndGruppe($groupedByLe);

			if (count($groupedByLe) === 0)
			{
				$canSwitch = false;
				$canSwitchInfo []= 'Gruppenbasis nur verfügbar, wenn Gruppen eindeutig Lehrenden zugeordnet sind';
			}
		}

		// Merge Evaluierungen in groupedByLe / groupedByLv
		$groupedByLe = $this->initiierunglib->mergeEvaluierungenIntoData($groupedByLe, $lves, $lveLv->lv_aufgeteilt);
		$groupedByLv = $this->initiierunglib->mergeEvaluierungenIntoData($groupedByLv, $lves, $lveLv->lv_aufgeteilt);

		// Add Evaluierung Editable Checks
		$groupedByLv = $this->addEvaluierungEditableChecks($groupedByLv);
		$groupedByLe = $this->addEvaluierungEditableChecks($groupedByLe);

		$lvLeitungen = null;

		if (count($lves) > 0)
		{
			$canSwitch = false;
			$canSwitchInfo []= 'At least one Evaluierung in LV started';
		}

		// If LV-Leitung is required
		if ($lvLeitungRequired)
		{
			// Get LV-Leitungen
			$this->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');
			$result = $this->LehrveranstaltungModel->getLvLeitung($lveLv->lehrveranstaltung_id, $lveLv->studiensemester_kurzbz);
			$lvLeitungen = hasData($result) ? getData($result) : [];

			// If user is not LV-Leitung
			if (!in_array($this->_uid, array_column($lvLeitungen, 'mitarbeiter_uid')))
			{
				// User cannot switch evaulation for Gesamt-LV or Gruppenbasis
				$canSwitch = false;
				$canSwitchInfo = ['Editable by LV-Leitung'];

				// User cannot start Lvevaluierung
				$groupedByLv[0]->editableCheck['isDisabledEvaluierung'] = true;
				$groupedByLv[0]->editableCheck['isDisabledEvaluierungInfo']= ['Editable by LV-Leitung'];

				// User should only see own Lehreinheiten
				$groupedByLe = array_filter($groupedByLe, function ($item) {
					return empty($item->editableCheck['isDisabledEvaluierung']) || $item->editableCheck['isDisabledEvaluierung'] === false;
				});
			}
		}

		$this->terminateWithSuccess([
			'lvLeitungen' => $lvLeitungen,
			'canSwitch' => $canSwitch,
			'canSwitchInfo' => $canSwitchInfo,
			'groupedByLv' => $groupedByLv,
			'groupedByLe' => $groupedByLe
		]);
	}

	/**
	 * Get all Prestudenten of given Lvevaluierung Lehrveranstaltung ID, that were already mailed.
	 *
	 * @return void
	 */
	public function getLveLvPrestudenten()
	{
		$lvevaluierung_lehrveranstaltung_id = $this->input->get('lvevaluierung_lehrveranstaltung_id');

		$result = $this->LvevaluierungPrestudentModel->getByLveLv($lvevaluierung_lehrveranstaltung_id);

		$data = $this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($data);
	}

	/**
	 * Get all Lvevaluierungen of given Lvevaluierung-Lehrveranstaltung-ID.
	 *
	 * @return void
	 */
	public function getLvEvaluierungenByID()
	{
		$lvevaluierung_lehrveranstaltung_id = $this->input->get('lvevaluierung_lehrveranstaltung_id');

		// Get Lv Evaluierungen
		$result = $this->LvevaluierungModel->loadWhere([
			'lvevaluierung_lehrveranstaltung_id' => $lvevaluierung_lehrveranstaltung_id
		]);

		$data = $this->getDataOrTerminateWithError($result);

		foreach ($data as &$item) {

			// Add Lvevaluierung-Prestudenten (mailed students)
			if ($item->lvevaluierung_id && !empty($item->lvevaluierung_id))
			{
				$result = $this->LvevaluierungPrestudentModel->getByLve($item->lvevaluierung_id);
			}
			$item->lvePrestudenten = hasData($result) ? getData($result) : [];
		}

		$this->terminateWithSuccess($data);
	}

	/**
	 * Update the lectors selection for type of Evaluation (Gesamt-Lv or Lehreinheiten/Gruppenbasis).
	 *
	 * @return void
	 */
	public function updateLvAufgeteilt(){
		$lvevaluierung_lehrveranstaltung_id = $this->input->post('lvevaluierung_lehrveranstaltung_id');
		$lv_aufgeteilt = $this->input->post('lv_aufgeteilt');

		$lveLv = $this->getLvevaluierungLehrveranstaltungOrFail($lvevaluierung_lehrveranstaltung_id);

		// If LV has LV-Leitung, user must be LV-Leitung
		if ($this->config->item('lvLeitungRequired'))
		{
			$this->checkLvLeitungAccessOrExit($lveLv->lehrveranstaltung_id, $lveLv->studiensemester_kurzbz);
		}

		// Return if at least one Lvevaluierung exists for this Lehrveranstaltung
		$result = $this->LvevaluierungModel->loadWhere([
			'lvevaluierung_lehrveranstaltung_id' => $lvevaluierung_lehrveranstaltung_id
		]);

		if (hasData($result))
		{
			$this->terminateWithError('Änderung nicht möglich. Mindestens eine Lvevaluierung ist bereits gespeichert worden.');
		}

		// Get Lv Evaluierungen
		$result = $this->LvevaluierungLehrveranstaltungModel->update(
			$lvevaluierung_lehrveranstaltung_id,
			[
				'lv_aufgeteilt' => $lv_aufgeteilt
			]
		);

		$data = $this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($data);
	}

	/**
	 * Insert or update Lvevaluierung. Update if Lvevaluierung ID is provided, otherwise insert new Lvevaluierung.
	 *
	 * Checks:
	 * - Studienplan of Lv and Studiensemester is retrieved by given Lvevaluierung-Lehrveranstaltung-ID.
	 * - Fragebogen must be assigned to that Studienplan.
	 * - Provided Evaluation-Startzeit must within period of Fragebogen.
	 *
	 * @return void
	 */
	public function saveOrUpdateLvevaluierung()
	{
		$data = $this->input->post('data');

		// Validate post data
		$this->_validateSaveOrUpdateLvevaluierungData($data);

		// Get LV-ID and Studiensemester
		$lveLv = $this->getLvevaluierungLehrveranstaltungOrFail($data['lvevaluierung_lehrveranstaltung_id']);

		// If Lvevaluierung is evaluated as Gesamt-Lv
		if ($this->config->item('lvLeitungRequired') && $lveLv->lv_aufgeteilt === false)
		{
			$this->checkLvLeitungAccessOrExit($lveLv->lehrveranstaltung_id, $lveLv->studiensemester_kurzbz);
		}

		// Get valid Fragebogen
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogen_model', 'LvevaluierungFragebogenModel');

		$result = $this->LvevaluierungFragebogenModel->getActiveFragebogen(
			$lveLv->lehrveranstaltung_id,
			$lveLv->studiensemester_kurzbz
		);

		if (!hasData($result))
		{
			$this->terminateWithError('No Active Fragebogen for this Lehrveranstaltung');
		}

		// Add Fragebogen ID to insert/update data
		$data['fragebogen_id']	= getData($result)[0]->fragebogen_id;
		

		// Insert / Update Lvevaluierung
		if (empty($data['lvevaluierung_id']))
		{
			unset($data['lvevaluierung_id']);
			$result = $this->LvevaluierungModel->insertLvevaluierung($data);
		}
		else
		{
			$this->exitIfStartzeitPassed($data['lvevaluierung_id']);

			$result = $this->LvevaluierungModel->updateLvevaluierung($data);
		}

		$data = $this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($data[0]);
	}

	/**
	 * Generate Codes and mail to students that are adressed by the given Lvevaluierung ID.
	 * This could be students of LV or students of LE, depending on evaluation type (Gesamt-LV or LV auf Gruppenbasis).
	 * Codes are only generated/mailed, if not done earlier (e.g.by sending via other Lehreinheit of the same LV)
	 *
	 * @return void
	 */
	public function generateCodesAndSendLinksToStudents()
	{
		$lvevaluierung_id = $this->input->post('lvevaluierung_id');

		// Get Lvevaluierung
		$lve = $this->getLvevaluierungOrFail($lvevaluierung_id);

		// Get Lvevaluierung-Lehrveranstaltung
		$lveLv = $this->getLvevaluierungLehrveranstaltungOrFail($lve->lvevaluierung_lehrveranstaltung_id);

		// If Lvevaluierung is evaluated as Gesamt-Lv
		if ($this->config->item('lvLeitungRequired') && $lveLv->lv_aufgeteilt === false)
		{
			// If LV has LV-Leitung, user must be LV-Leitung
			$this->checkLvLeitungAccessOrExit($lveLv->lehrveranstaltung_id, $lveLv->studiensemester_kurzbz);
		}

		// Get Students of LV or LE, depending on Evaluation type
		$studenten = $lveLv->lv_aufgeteilt
			? $this->getStudentsForLe($lve)
			: $this->getStudentsForLv($lveLv);

		if (empty($studenten))
		{
			$this->terminateWithError('No Students assigned to this course');
		}

		// Get all students of LV that already got a code
		$result = $this->LvevaluierungPrestudentModel->getByLveLv($lveLv->lvevaluierung_lehrveranstaltung_id);
		$mailedPrestudentIds = hasData($result) ? array_column(getData($result), 'prestudent_id') : [];

		// Filter studenten to keep only unmailed
		$unmailedStudenten = array_values(array_filter($studenten, function ($s) use ($mailedPrestudentIds) {
			return !in_array($s->prestudent_id, $mailedPrestudentIds, true);
		}));

		// Return if all Students of LV or LE already got mail
		if (empty($unmailedStudenten))
		{
			$this->terminateWithError('Cannot send. All Students of this LV already received emails. Reset if necessary.');
		}

		// Mail codes to Students
		$codes_ausgegeben = 0;
		$failedMailStudenten = [];

		foreach ($unmailedStudenten as $student)
		{
			if ($this->initiierunglib->generateAndSendCodeForStudent($lve, $student, $lveLv->lehrveranstaltung_id))
			{
				$codes_ausgegeben++;
			}
			else
			{
				// Collect students that did not get mail
				$failedMailStudenten[]= $student;
			}
		}

		// Update codes_ausgegeben and codes_gemailt values
		if ($codes_ausgegeben > 0)
		{
			$this->LvevaluierungModel->update(
				$lvevaluierung_id,
				[
					'codes_gemailt' => true,
					'codes_ausgegeben' => $lve->codes_ausgegeben + $codes_ausgegeben // Sum up already ausgegebene
				]
			);
		}

		$result = $this->LvevaluierungPrestudentModel->getByLve($lvevaluierung_id);
		if (isError($result))
		{
			$this->terminateWithError(getError($result));
		}

		$lvePrestudenten = getData($result);

		$result = $this->LvevaluierungPrestudentModel->getByLveLv($lveLv->lvevaluierung_lehrveranstaltung_id);
		if (isError($result))
		{
			$this->terminateWithError(getError($result));
		}

		$lvePrestudentenByLv = getData($result);

		$isAllSent = $this->isAllSentLvEvaluierung($lveLv->lvevaluierung_lehrveranstaltung_id);

		$result = success(
			[
				'codes_gemailt' => $codes_ausgegeben > 0,
				'codes_ausgegeben' => $lve->codes_ausgegeben + $codes_ausgegeben,
				'lvePrestudenten' => $lvePrestudenten,
				'lvePrestudentenByLv' => $lvePrestudentenByLv,
				'failedMailStudenten' => $failedMailStudenten,
				'isAllSent' => $isAllSent
			]
		);

		$data = $this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($data);
	}
	// Checks and Validations
	// -----------------------------------------------------------------------------------------------------------------
	/**
	 * Validates posted json data for saving/updateing Lvevaluierung.
	 *
	 * @param $data
	 * @return void
	 */
	private function _validateSaveOrUpdateLvevaluierungData($data)
	{
		$this->load->library('form_validation');

		$this->form_validation->set_data($data);
		$this->form_validation->set_rules('lvevaluierung_lehrveranstaltung_id', 'LveLv-ID', 'required');
		$this->form_validation->set_rules(
			'startzeit',
			'Startzeit',
			'required|callback_checkStartzeitNotPast[' . $data['lvevaluierung_id'] . ']'
		);
		$this->form_validation->set_message('checkStartzeitNotPast', $this->p->t('ui', 'datumInVergangenheit'));

		$this->form_validation->set_rules(
			'endezeit',
			'Endezeit',
			'required|callback_checkEndezeitAfterStartzeit[' . $data['startzeit'] . ']'
		);
		$this->form_validation->set_message('checkEndezeitAfterStartzeit', $this->p->t('ui', 'datumEndeVorDatumStart'));

		// If Evaluierung is done by Lehreinheit
		$lv_aufgeteilt = $this->initiierunglib->isLvAufgeteilt($data['lvevaluierung_lehrveranstaltung_id']);

		if ($lv_aufgeteilt)
		{
			$this->form_validation->set_rules('lehreinheit_id', 'LE-ID', 'required');
		}

		// On error
		if ($this->form_validation->run() == false)
		{
			$this->terminateWithValidationErrors($this->form_validation->error_array());
		}

	}

	/**
	 * Add evaluation status and button checks to grouped items (LV oder LE)
	 *
	 * @param array $grouped  array of grouped items (LV or LE)
	 * @param array $lvePrestudentenByLv  all students who got mails in this LV (any evaluation)
	 * @param array $lvePrestudenten      students who got mails in THIS evaluation
	 * @param bool $codes_gemailt
	 * @param int|null $lvevaluierung_id
	 * @return array
	 */
	public function addEvaluierungEditableChecks($grouped)
	{
		foreach ($grouped as &$item) {
			$lvevaluierung_id = isset($item->lvevaluierung_id) ? $item->lvevaluierung_id : null;
			$studenten = isset($item->studenten) ? $item->studenten : [];
			$sentByAnyEvaluierungOfLv = isset($item->sentByAnyEvaluierungOfLv) ? $item->sentByAnyEvaluierungOfLv : [];

			$isDisabledEvaluierungInfo = [];
			$isDisabledEvaluierung = false;

			// Case: noch keine Evaluierung und noch nicht alle Studierende gemailt
			if (!$lvevaluierung_id && count($sentByAnyEvaluierungOfLv) < count($studenten))
			{
				$isDisabledSendMailInfo[]= 'Cannot send. Save dates first';	// todo besser zu isDisabledEvaluierungInfo?
			}

			// Case: All students were already mailed
			if (count($sentByAnyEvaluierungOfLv) >= count($studenten))
			{
				$isDisabledEvaluierung = true;
			}

			// Case: LV aufgeteilt: nur Lektor darf bearbeiten
			if ($item->lv_aufgeteilt)
			{
				if ($item->lv_aufgeteilt && !in_array($this->_uid, array_column($item->lektoren, 'mitarbeiter_uid'))) {
					$isDisabledEvaluierung = true;
					$isDisabledEvaluierungInfo = ['Editable by Lector of LV'];
				}
			}

			// Case: Evaluierung bereits gestartet: Update nicht mehr möglich
			if ($lvevaluierung_id && !empty($item->startzeit)) {
				$today = new DateTime('today');
				$startzeit = new DateTime(date('Y-m-d', strtotime($item->startzeit)));

				if ($today > $startzeit) {
					$isDisabledEvaluierung = true;
					$isDisabledEvaluierungInfo = ['Cannot change. Evaluierungperiod already started'];
				}
			}

			// Case: Evaluierung was not started, but all students were mailed by other Evaluierung
			//if (!$lvevaluierung_id && count($sentByAnyEvaluierungOfLv) > $item->codes_ausgegeben)
			if (count($sentByAnyEvaluierungOfLv) > $item->codes_ausgegeben)
			{
				$isDisabledEvaluierung = true;
				$isDisabledEvaluierungInfo []= 'Students were mailed by other Evaluierung of this LV';
			}


			// Status für Mailversand
			$isDisabledSendMailInfo = [];
			if ($lvevaluierung_id && !$item->codes_gemailt && count($sentByAnyEvaluierungOfLv) === 0)
			{
				$isDisabledSendMailInfo[]= 'Ready to send';
			}

//			if ($lvevaluierung_id && $item->codes_gemailt)
//			{
//				$isDisabledSendMailInfo[]= $item->codes_ausgegeben. ' Codes generated';
//			}

			if (count($sentByAnyEvaluierungOfLv))
			{
				$isDisabledSendMailInfo[]= count($sentByAnyEvaluierungOfLv). ' Emails sent to';
			}

			// Button disable logic
			$isDisabledSendMail = (!$lvevaluierung_id && !$item->codes_gemailt) || count($sentByAnyEvaluierungOfLv) >= count($studenten);

			// Add infos
			$item->editableCheck = [
				'isDisabledEvaluierung' => $isDisabledEvaluierung,
				'isDisabledEvaluierungInfo' => $isDisabledEvaluierungInfo,
				'isDisabledSendMail' => $isDisabledSendMail,
				'isDisabledSendMailInfo' => $isDisabledSendMailInfo
			];
		}

		return $grouped;
	}

	/**
	 * Checks if Endezeit is after Startzeit.
	 *
	 * @param string $endezeit
	 * @param string $startzeit
	 * @return bool
	 */
	public function checkEndezeitAfterStartzeit($endezeit, $startzeit)
	{
		if (isEmptyString($endezeit) || isEmptyString($startzeit)) return true; // 'required' rule handles missing field

		return strtotime($endezeit) > strtotime($startzeit);
	}

	/**
	 * Checks if Startzeit before Today.
	 *
	 * @param string $startzeit
	 * @return bool
	 */
	public function checkStartzeitNotPast($startzeit, $lvevaluierung_id)
	{
		// Return if Evaluierung already exist
		if (is_numeric($lvevaluierung_id)) return true;

		if (isEmptyString($startzeit)) return true; // 'required' rule handles missing field

		$nowDate   = (new DateTime())->format('Y-m-d');
		$startDate = (new DateTime($startzeit))->format('Y-m-d');

		return $nowDate <= $startDate;
	}

	/**
	 * Exit if Evaluierung time period already started. (Now > Startzeit)
	 * @param $lvevaluierung_id
	 * @return void
	 * @throws DateMalformedStringException
	 */
	public function exitIfStartzeitPassed($lvevaluierung_id)
	{
		$lve = $this->getLvevaluierungOrFail($lvevaluierung_id);
		$nowDate   = (new DateTime())->format('Y-m-d');
		$startDate = (new DateTime($lve->startzeit))->format('Y-m-d');
		if ($nowDate > $startDate)
		{
			$this->terminateWithError('Cannot update after LV-Evaluierung has already startet.');
		}
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
		if (!$lvevaluierung_id)
		{
			$this->terminateWithError('Evaluierung needs to be initialised by saving Start- end Endzeit.');
		}

		$result = $this->LvevaluierungModel->load($lvevaluierung_id);

		if (isError($result))
		{
			$this->terminateWithError($result);
		}

		return getData($result)[0];
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

	public function getLveLvPrestudentenOrFail($lvevaluierung_lehrveranstaltung_id)
	{
		$result = $this->LvevaluierungPrestudentModel->getByLveLv($lvevaluierung_lehrveranstaltung_id);

		if (isError($result))
		{
			$this->terminateWithError($result);
		}

		return hasData($result) ? getData($result) : [];
	}


	/**
	 * Checks if all students of LV got mail with codes.
	 *
	 * @param $lvevaluierung_lehrveranstaltung_id
	 * @return bool True if all students got mail.
	 */
	private function isAllSentLvEvaluierung($lvevaluierung_lehrveranstaltung_id)
	{
		$lveLv = $this->getLvevaluierungLehrveranstaltungOrFail($lvevaluierung_lehrveranstaltung_id);

		$lvStudents = $this->getStudentsForLv($lveLv);
		$lvStudentsPrestudentIds = array_column($lvStudents, 'prestudent_id');

		$lveLvPrestudenten = $this->getLveLvPrestudentenOrFail($lvevaluierung_lehrveranstaltung_id);
		$lveLvPrestudentenIds = array_column($lveLvPrestudenten, 'prestudent_id');

		// Get mailed students by strongly checking against prestudent_ids
		// (Es könnten Studierende dazukommen und/oder ausfallen -> deshalb ist ein reines count auf beide nicht genug)
		$intersect = array_intersect($lvStudentsPrestudentIds, $lveLvPrestudentenIds);

		return count($intersect) >= count($lvStudents);	// True if all students got mail
	}

	/**
	 * Get Students of Lehrveranstaltung of given Lvevaluierung Lehrveranstaltung data.
	 * Terminate with error on fail.
	 *
	 * @param $lveLv
	 * @return array
	 */
	private function getStudentsForLv($lveLv)
	{
		$this->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');
		$result = $this->LehrveranstaltungModel->getStudentsByLv(
			$lveLv->studiensemester_kurzbz,
			$lveLv->lehrveranstaltung_id,
			true	// true = only active students
		);

		if(isError($result))
		{
			$this->terminateWithError(getError($result));
		}

		return hasData($result) ? getData($result) : [];
	}

	/**
	 * Get Students of Lehreinheit by given Lvevaluierung Lehreinheit ID.
	 * Terminate with error on fail.
	 *
	 * @param $lve
	 * @return array
	 */
	private function getStudentsForLe($lve)
	{
		$this->load->model('education/Lehreinheit_model', 'LehreinheitModel');
		$result = $this->LehreinheitModel->getStudenten($lve->lehreinheit_id);

		if(isError($result))
		{
			$this->terminateWithError(getError($result));
		}

		return hasData($result) ? getData($result) : [];
	}

	/**
	 * Check if current user has LV-Leitung access for a given Lehrveranstaltung. Exit if not.
	 *
	 * @param $lehrveranstaltung_id
	 * @param $studiensemester_kurzbz
	 * @return void
	 */
	private function checkLvLeitungAccessOrExit($lehrveranstaltung_id, $studiensemester_kurzbz)
	{
		$result = $this->initiierunglib->checkLvLeitungAccess($lehrveranstaltung_id, $studiensemester_kurzbz);
		if (isError($result))
		{
			$this->terminateWithError('Cannot save: '. getError($result));
		}
	}
}
