<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Fragebogen extends FHCAPI_Controller
{
	const BERECHTIGUNG_ADMIN = 'extension/lvevaluierung_admin';

	public function __construct()
	{
		parent::__construct([
			'getFrageboegen' => self::BERECHTIGUNG_ADMIN . ':r',
			'getFragebogen' => self::BERECHTIGUNG_ADMIN . ':r',
		]);

		$this->load->library('extensions/FHC-Core-Evaluierung/FragebogenLib');

		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogen_model', 'LvevaluierungFragebogenModel');

		// Load language phrases
		$this->loadPhrases([
			'ui',
			'global'
		]);
	}

	/**
	 * Alle Fragebögen für Dropdown (inkl. Info, ob bereits beantwortet).
	 */
	public function getFrageboegen()
	{
		$result = $this->LvevaluierungFragebogenModel->getFrageboegen();

		if (isError($result))
		{
			$this->terminateWithError(getError($result));
		}

		$this->terminateWithSuccess(hasData($result) ? getData($result) : []);
	}

	/**
	 * Fragebogen mit Zuordnungen und Struktur Gruppe > Frage > Antworten.
	 */
	public function getFragebogen()
	{
		$fragebogen_id = $this->input->get('fragebogen_id');

		if (!$fragebogen_id)
		{
			$this->terminateWithError('Missing Fragebogen ID');
		}

		$result = $this->fragebogenlib->getFragebogenStruktur($fragebogen_id);
		$data = $this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($data);
	}
}
