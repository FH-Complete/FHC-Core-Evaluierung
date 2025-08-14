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
				'getLvEvaluierungenByID' => 'admin:rw', // todo ändern
				'updateLvAufgeteilt' => 'admin:rw', // todo ändern
				'saveOrUpdateLvevaluierung' => 'admin:rw', // todo ändern
			)
		);

		$this->load->library('extensions/FHC-Core-Evaluierung/InitiierungLib');

		$this->load->model('extensions/FHC-Core-Evaluierung/Lvevaluierung_model', 'LvevaluierungModel');
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungLehrveranstaltung_model', 'LvevaluierungLehrveranstaltungModel');

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
		}

		$this->terminateWithSuccess(array_values($grouped));
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
	public function saveOrUpdateLvevaluierung(){
		$data = $this->input->post('data');

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
			$lvelv->studiensemester_kurzbz,
			$data['startzeit']
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
}
