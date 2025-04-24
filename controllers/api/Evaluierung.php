<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Evaluierung extends FHCAPI_Controller
{
	const BERECHTIGUNG_EVLUIERUNG = 'admin:rw';


	public function __construct()
	{
		parent::__construct(array(
				'getLvEvaluierung' => self::BERECHTIGUNG_EVLUIERUNG,
				'getInitFragebogen' => self::BERECHTIGUNG_EVLUIERUNG,
				'getLvInfo' => self::BERECHTIGUNG_EVLUIERUNG
			)
		);

		$this->load->library('extensions/FHC-Core-Evaluierung/EvaluierungLib');

		$this->load->model('extensions/FHC-Core-Evaluierung/Lvevaluierung_model', 'LvevaluierungModel');
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungCode_model', 'LvevaluierungCodeModel');
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogenGruppe_model', 'LvevaluierungFragebogenGruppeModel');
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogenFrage_model', 'LvevaluierungFragebogenFrageModel');
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogenFrageAntwort_model', 'LvevaluierungFragebogenFrageAntwortModel');
	}

	/**
	 * Get LvEvaluierung by Code.
	 *
	 * @return void
	 */
	public function getLvEvaluierung()
	{
		$code = $this->input->get('code');

		$this->LvevaluierungCodeModel->addSelect('lvevaluierung_id');
		$result = $this->LvevaluierungCodeModel->loadWhere([
			'code' => $code
		]);

		if (hasData($result))
		{
			$lvevaluierung_id = getData($result)[0]->lvevaluierung_id;
			$result = $this->LvevaluierungModel->load($lvevaluierung_id);

			$data = $this->getDataOrTerminateWithError($result);

			$this->terminateWithSuccess(current($data));
		}
		else
		{
			$this->terminateWithError('No LV-Evaluierung found');
		}
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
	 * Get Lehrveranstaltung Infos and its lecturers.
	 *
	 * @return void
	 */
	public function getLvInfo()
	{
		$lehrveranstaltung_id = $this->input->get('lehrveranstaltung_id');
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');

		$result = $this->evaluierunglib->getLvInfo($lehrveranstaltung_id, $studiensemester_kurzbz);

		$this->terminateWithSuccess($result);
	}
}
