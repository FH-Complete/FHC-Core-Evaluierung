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
			'getOrganisationseinheiten' => self::BERECHTIGUNG_ADMIN . ':r',
			'getStudienplaene' => self::BERECHTIGUNG_ADMIN . ':r',
			'searchLehrveranstaltungen' => self::BERECHTIGUNG_ADMIN . ':r',
		]);

		$this->load->library('extensions/FHC-Core-Evaluierung/FragebogenLib');

		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogen_model', 'LvevaluierungFragebogenModel');
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogenZuordnung_model', 'LvevaluierungFragebogenZuordnungModel');

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

	/**
	 * Organisationseinheiten für Zuordnungs-Dropdown.
	 *
	 * @return void
	 */
	public function getOrganisationseinheiten()
	{
		$this->load->model('organisation/Organisationseinheit_model', 'OrganisationseinheitModel');

		$this->OrganisationseinheitModel->addSelect('oe_kurzbz, bezeichnung, organisationseinheittyp_kurzbz');
		$this->OrganisationseinheitModel->addOrder('organisationseinheittyp_kurzbz, bezeichnung');
		$result = $this->OrganisationseinheitModel->loadWhere([
			'lehre' => true,
			'aktiv' => true
		]);

		if (isError($result))
		{
			$this->terminateWithError(getError($result));
		}

		$this->terminateWithSuccess(hasData($result) ? getData($result) : []);
	}

	/**
	 * Studienpläne für Zuordnungs-Dropdown.
	 *
	 * @return void
	 */
	public function getStudienplaene()
	{
		$this->load->model('organisation/Studienplan_model', 'StudienplanModel');
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');

		$this->StudienplanModel->addSelect('studienplan_id, tbl_studienplan.bezeichnung, semester');
		$this->StudienplanModel->addJoin('lehre.tbl_studienordnung', 'studienordnung_id');
		$this->StudienplanModel->addJoin('lehre.tbl_studienplan_semester', 'studienplan_id');
		$this->StudienplanModel->addOrder('bezeichnung');
		$this->StudienplanModel->addOrder('semester', 'DESC');

		$result = $this->StudienplanModel->loadWhere([
			'studiensemester_kurzbz' => $studiensemester_kurzbz,
			'aktiv' => true
		]);

		if (isError($result))
		{
			$this->terminateWithError(getError($result));
		}

		$this->terminateWithSuccess(hasData($result) ? getData($result) : []);
	}

	/**
	 * LV-Suche für Autocomplete (ab 2 Zeichen).
	 * Nur LVs, die für Evaluierung markiert sind. (lv.evaluierung = TRUE)
	 */
	public function searchLehrveranstaltungen()
	{
		$query = trim((string)$this->input->get('query'));
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');

		if (mb_strlen($query) < 2)
		{
			$this->terminateWithSuccess([]);
		}

		$result = $this->LvevaluierungFragebogenZuordnungModel->searchLehrveranstaltungen($query, $studiensemester_kurzbz);

		if (isError($result))
		{
			$this->terminateWithError(getError($result));
		}

		$this->terminateWithSuccess(hasData($result) ? getData($result) : []);
	}
}
