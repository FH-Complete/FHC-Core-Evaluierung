<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Initiierung extends FHCAPI_Controller
{
	public function __construct()
	{
		/** @noinspection PhpUndefinedClassConstantInspection */
		parent::__construct(array(
				'getLveLvs' => 'admin:rw', // todo ändern
				'getLveLvsWithLes' => 'admin:rw', // todo ändern
				'getLveLvWithLesAndGruppenById' => 'admin:rw', // todo ändern
				'getLveLvPrestudenten' => 'admin:rw', // todo ändern
				'getLvEvaluierungenByID' => 'admin:rw', // todo ändern
				'updateLvAufgeteilt' => 'admin:rw', // todo ändern
				'saveOrUpdateLvevaluierung' => 'admin:rw', // todo ändern
				'generateCodesAndSendLinksToStudents' => 'admin:rw', // todo ändern
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
	 * Get Lv of given ID, including its Lehreinheiten, associated Gruppen and Lektoren, grouped by Lehreinheit ID.
	 *
	 * @return void
	 */
	public function getLveLvWithLesAndGruppenById()
	{
		$lvevaluierung_lehrveranstaltung_id = $this->input->get('lvevaluierung_lehrveranstaltung_id');

		// Get Lvs with Lehreinheiten and Gruppen
		$result = $this->LvevaluierungLehrveranstaltungModel->getLveLvWithLesAndGruppenById($lvevaluierung_lehrveranstaltung_id);

		$data = $this->getDataOrTerminateWithError($result);

		// Group data by Lehreinheit
		$grouped = [];
		foreach($data as $item)
		{
			$lehreinheit_id = $item->lehreinheit_id;

			if (!isset($grouped[$lehreinheit_id])) {
				$grouped[$lehreinheit_id] = clone $item;
				$grouped[$lehreinheit_id]->lektoren = [];
				$grouped[$lehreinheit_id]->gruppen = [];
			}

			// Uniquely group Lektoren
			$grouped = $this->initiierunglib->groupLektoren($grouped, $item);

			// Uniquely group Gruppen
			$grouped = $this->initiierunglib->groupGruppen($grouped, $item);

			// Remove redundant fields that were grouped yet
			foreach ($grouped as $g) {
				unset(
					$g->mitarbeiter_uid,
					$g->fullname,
					$g->lehrfunktion_kurzbz,
					$g->semester,
					$g->verband,
					$g->gruppe
				);

				$g->lektoren = array_values($g->lektoren);
				$g->gruppen = array_values($g->gruppen);
			}

			// Add group Studenten
			$this->load->model('education/Lehreinheit_model', 'LehreinheitModel');
			$result = $this->LehreinheitModel->getStudenten($item->lehreinheit_id);
			$g->studenten = hasData($result) ? getData($result) : [];
		}

		$this->terminateWithSuccess(array_values($grouped));
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
			$item->lvevaluierung_prestudenten = hasData($result) ? getData($result) : [];
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
		$result = $this->LvevaluierungLehrveranstaltungModel->load($data['lvevaluierung_lehrveranstaltung_id']);
		if (!hasData($result))
		{
			$this->terminateWithError('No Evaluierung assigned to this Lehrveranstaltung');
		}
		$lvelv = getData($result)[0];


		// Get valid Fragebogen
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogen_model', 'LvevaluierungFragebogenModel');

		$result = $this->LvevaluierungFragebogenModel->getActiveFragebogen(
			$lvelv->lehrveranstaltung_id,
			$lvelv->studiensemester_kurzbz
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
			if ($this->initiierunglib->generateAndSendCodeForStudent($lvevaluierung_id, $student))
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

		$lveLvPrestudenten = getData($result);

		$result = success(
			[
				'codes_gemailt' => $codes_ausgegeben > 0,
				'codes_ausgegeben' => $lve->codes_ausgegeben + $codes_ausgegeben,
				'lvevaluierung_prestudenten' => $lvePrestudenten,
				'lveLvPrestudenten' => $lveLvPrestudenten,
				'failedMailStudenten' => $failedMailStudenten
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
		$this->form_validation->set_rules('startzeit', 'Startzeit', 'required');
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

		return getData($result)[0];
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
}
