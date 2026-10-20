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
			'insertFragebogen' => self::BERECHTIGUNG_ADMIN . ':rw',
			'updateFragebogen' => self::BERECHTIGUNG_ADMIN . ':rw',
			'copyFragebogen' => self::BERECHTIGUNG_ADMIN . ':rw',
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

	/**
	 * Insert Fragebogen (Basisdaten).
	 *
	 * @return void
	 */
	public function insertFragebogen()
	{
		$data = $this->input->post();

		$this->_validateFragebogenData($data);

		$result = $this->LvevaluierungFragebogenModel->insertFragebogen(
			$data['bezeichnung_de'],
			$data['bezeichnung_en'],
			$data['gueltig_von'],
			$this->_getGueltigBis($data),
			getAuthUid()
		);
		$fragebogen = $this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($fragebogen[0]->fragebogen_id);
	}

	/**
	 * Update Fragebogen (Basisdaten).
	 * Bereits beantwortet: nur Gültig bis änderbar.
	 *
	 * @return void
	 */
	public function updateFragebogen()
	{
		$data = $this->input->post();
		$fragebogen = $this->_getFragebogenOrFail($this->input->post('fragebogen_id'));

		// Bereits beantwortet: gespeicherte Werte beibehalten, nur Gültig bis übernehmen
		if ($fragebogen->hasAntworten)
		{
			$data['bezeichnung_de'] = $fragebogen->bezeichnung_de;
			$data['bezeichnung_en'] = $fragebogen->bezeichnung_en;
			$data['gueltig_von'] = $fragebogen->gueltig_von;
		}

		$this->_validateFragebogenData($data);

		$result = $this->LvevaluierungFragebogenModel->updateFragebogen(
			$fragebogen->fragebogen_id,
			$data['bezeichnung_de'],
			$data['bezeichnung_en'],
			$data['gueltig_von'],
			$this->_getGueltigBis($data),
			getAuthUid()
		);
		$this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($fragebogen->fragebogen_id);
	}

	/**
	 * Copy Fragebogen: Gruppen und Fragen, ohne Zuordnungen.
	 * Gepostete fragebogen_id ist die Vorlage.
	 *
	 * @return void
	 */
	public function copyFragebogen()
	{
		$data = $this->input->post();
		$vorlage = $this->_getFragebogenOrFail($this->input->post('fragebogen_id'));

		$this->_validateFragebogenData($data);

		$data['gueltig_bis'] = $this->_getGueltigBis($data);

		$result = $this->fragebogenlib->copyFragebogen($vorlage->fragebogen_id, $data);
		$fragebogen_id = $this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($fragebogen_id);
	}

	/**
	 * Check if Gültig bis is after Gültig von. (Leer = unbefristet)
	 */
	public function checkGueltigBisAfterGueltigVon($gueltig_bis, $gueltig_von)
	{
		if (!$gueltig_bis) return true;

		return $gueltig_bis >= $gueltig_von;
	}

	// Checks and Validations
	// -----------------------------------------------------------------------------------------------------------------
	/**
	 * Fragebogen laden, sonst mit Fehler abbrechen.
	 *
	 * @param $fragebogen_id
	 * @return object    Fragebogen inkl. hasAntworten
	 */
	private function _getFragebogenOrFail($fragebogen_id)
	{
		$result = $this->LvevaluierungFragebogenModel->getFragebogen($fragebogen_id);
		$data = $this->getDataOrTerminateWithError($result);

		if (empty($data))
		{
			$this->terminateWithError('Fragebogen nicht gefunden');
		}

		return $data[0];
	}

	/**
	 * Leeres Gültig bis als NULL speichern (= unbefristet).
	 *
	 * @param $data
	 * @return mixed
	 */
	private function _getGueltigBis($data)
	{
		return !empty($data['gueltig_bis']) ? $data['gueltig_bis'] : null;
	}

	/**
	 * Validates posted Basisdaten of Fragebogen.
	 *
	 * @param $data
	 * @return void
	 */
	private function _validateFragebogenData($data)
	{
		$gueltig_von = isset($data['gueltig_von']) ? $data['gueltig_von'] : '';

		$this->load->library('form_validation');

		$this->form_validation->set_data($data);
		$this->form_validation->set_rules('bezeichnung_de', 'Bezeichnung (DE)', 'required');
		$this->form_validation->set_rules('bezeichnung_en', 'Bezeichnung (EN)', 'required');
		$this->form_validation->set_rules('gueltig_von', 'Gültig von', 'required');
		$this->form_validation->set_rules(
			'gueltig_bis',
			'Gültig bis',
			'callback_checkGueltigBisAfterGueltigVon[' . $gueltig_von . ']',
			['checkGueltigBisAfterGueltigVon' => $this->p->t('ui', 'datumEndeVorDatumStart')]
		);

		// On error
		if ($this->form_validation->run() == false)
		{
			$this->terminateWithValidationErrors($this->form_validation->error_array());
		}
	}
}
