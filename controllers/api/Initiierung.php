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
			)
		);

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
}
