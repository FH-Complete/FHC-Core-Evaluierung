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
			)
		);

		$this->load->library('extensions/FHC-Core-Evaluierung/InitiierungLib');

		$this->load->model('extensions/FHC-Core-Evaluierung/Lvevaluierung_model', 'LvevaluierungModel');
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungLehrveranstaltung_model', 'LvevaluierungLehrveranstaltungModel');
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
}
