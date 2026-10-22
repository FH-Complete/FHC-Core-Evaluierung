<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Fragebogen extends FHCAPI_Controller
{
	const BERECHTIGUNG_ADMIN = 'extension/lvevaluierung_admin';
	const GRUPPE_TYP_LABEL = 'label';

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
			'deleteFragebogen' => self::BERECHTIGUNG_ADMIN . ':rw',
			'insertZuordnung' => self::BERECHTIGUNG_ADMIN . ':rw',
			'deleteZuordnung' => self::BERECHTIGUNG_ADMIN . ':rw',
			'insertGruppe' => self::BERECHTIGUNG_ADMIN . ':rw',
			'updateGruppe' => self::BERECHTIGUNG_ADMIN . ':rw',
			'deleteGruppe' => self::BERECHTIGUNG_ADMIN . ':rw',
			'sortGruppen' => self::BERECHTIGUNG_ADMIN . ':rw',
		]);

		$this->load->library('extensions/FHC-Core-Evaluierung/FragebogenLib');

		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogen_model', 'LvevaluierungFragebogenModel');
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogenZuordnung_model', 'LvevaluierungFragebogenZuordnungModel');
		$this->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogenGruppe_model', 'LvevaluierungFragebogenGruppeModel');

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
	 * Delete Fragebogen, nur wenn in keiner Evaluierung verwendet.
	 *
	 * @return void
	 */
	public function deleteFragebogen()
	{
		$fragebogen_id = $this->input->post('fragebogen_id');

		$fragebogen = $this->_getFragebogenOrFail($fragebogen_id);

		// Exit, wenn Fragebogen schon zu Evaluierung(en) zugeordnet ist
		if ($fragebogen->isVerwendet)
		{
			$this->terminateWithError('Fragebogen wird bereits in einer Evaluierung verwendet und kann nicht gelöscht werden');
		}

		// Fragebogen löschen
		$result = $this->fragebogenlib->deleteFragebogen($fragebogen->fragebogen_id);
		$this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($fragebogen->fragebogen_id);
	}

	// Zuordnungen
	// -----------------------------------------------------------------------------------------------------------------
	/**
	 * Insert Zuordnung zu OE, Studienplan oder LV. Nur solange Fragebogen in keiner Evaluierung verwendet.
	 *
	 * @return void
	 */
	public function insertZuordnung()
	{
		$fragebogen = $this->_checkFragebogenNotVerwendet($this->input->post('fragebogen_id'));

		$zuordnung = [
			'fragebogen_id' => $fragebogen->fragebogen_id,
			'oe_kurzbz' => $this->input->post('oe_kurzbz'),
			'studienplan_id' => $this->input->post('studienplan_id'),
			'lehrveranstaltung_id' => $this->input->post('lehrveranstaltung_id')
		];

		// Genau eine Ebene: OE, Studienplan oder LV
		$ebenen = array_filter([$zuordnung['oe_kurzbz'], $zuordnung['studienplan_id'], $zuordnung['lehrveranstaltung_id']]);

		if (count($ebenen) !== 1)
		{
			$this->terminateWithError('Ungültige Zuordnung');
		}

		// Nicht doppelt zuordnen
		$result = $this->LvevaluierungFragebogenZuordnungModel->loadWhere($zuordnung);

		if (hasData($result))
		{
			$this->terminateWithError('Zuordnung bereits vorhanden');
		}

		$result = $this->LvevaluierungFragebogenZuordnungModel->insert($zuordnung);
		$zuordnung_id = $this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($zuordnung_id);
	}

	/**
	 * Delete Zuordnung. Nur solange Fragebogen in keiner Evaluierung verwendet.
	 *
	 * @return void
	 */
	public function deleteZuordnung()
	{
		$zuordnung = $this->_getZuordnungOrFail($this->input->post('lvevaluierung_fragebogen_zuordnung_id'));
		$this->_checkFragebogenNotVerwendet($zuordnung->fragebogen_id);

		$result = $this->LvevaluierungFragebogenZuordnungModel->delete($zuordnung->lvevaluierung_fragebogen_zuordnung_id);
		$this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($zuordnung->lvevaluierung_fragebogen_zuordnung_id);
	}

	// Gruppen
	// -----------------------------------------------------------------------------------------------------------------
	/**
	 * Insert Gruppe am Ende des Fragebogens.
	 *
	 * @return void
	 */
	public function insertGruppe()
	{
		$data = $this->input->post();
		$fragebogen = $this->_checkFragebogenNotLocked($this->input->post('fragebogen_id'));

		$this->_validateGruppeData($data);

		$result = $this->fragebogenlib->insertGruppe($fragebogen->fragebogen_id, $data);
		$gruppe_id = $this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($gruppe_id);
	}

	/**
	 * Update Gruppe (Typ und Bezeichnung). Überschrift nur ohne Fragen.
	 *
	 * @return void
	 */
	public function updateGruppe()
	{
		$data = $this->input->post();
		$gruppe = $this->_getGruppeOrFail($this->input->post('lvevaluierung_fragebogen_gruppe_id'));
		$this->_checkFragebogenNotLocked($gruppe->fragebogen_id);

		$this->_validateGruppeData($data);

		// Überschrift hat keine Fragen
		if ($data['typ'] === self::GRUPPE_TYP_LABEL)
		{
			$result = $this->LvevaluierungFragebogenGruppeFrageModel->loadWhere([
				'lvevaluierung_fragebogen_gruppe_id' => $gruppe->lvevaluierung_fragebogen_gruppe_id
			]);

			if (hasData($result))
			{
				$this->terminateWithError('Nur Überschrift ist erst ohne Fragen möglich');
			}
		}

		// Style und Sortierung bleiben
		$result = $this->LvevaluierungFragebogenGruppeModel->updateGruppe(
			$gruppe->lvevaluierung_fragebogen_gruppe_id,
			$data['typ'],
			$data['bezeichnung_de'],
			$data['bezeichnung_en'],
			$gruppe->style,
			$gruppe->sort
		);
		$this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($gruppe->lvevaluierung_fragebogen_gruppe_id);
	}

	/**
	 * Delete Gruppe inkl. Fragen, die sonst nirgends verwendet werden.
	 *
	 * @return void
	 */
	public function deleteGruppe()
	{
		$gruppe = $this->_getGruppeOrFail($this->input->post('lvevaluierung_fragebogen_gruppe_id'));
		$this->_checkFragebogenNotLocked($gruppe->fragebogen_id);

		$result = $this->fragebogenlib->deleteGruppe($gruppe->lvevaluierung_fragebogen_gruppe_id);
		$this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($gruppe->lvevaluierung_fragebogen_gruppe_id);
	}

	/**
	 * Sort Gruppen nach übergebener Reihenfolge.
	 *
	 * @return void
	 */
	public function sortGruppen()
	{
		$fragebogen = $this->_checkFragebogenNotLocked($this->input->post('fragebogen_id'));
		$gruppeIds = $this->input->post('gruppeIds');

		if (!is_array($gruppeIds) || empty($gruppeIds))
		{
			$this->terminateWithError('Missing Gruppen');
		}

		$result = $this->fragebogenlib->sortGruppen($fragebogen->fragebogen_id, $gruppeIds);
		$this->getDataOrTerminateWithError($result);

		$this->terminateWithSuccess($fragebogen->fragebogen_id);
	}

	// Checks and Validations
	// -----------------------------------------------------------------------------------------------------------------

	/**
	 * Check if Gültig bis is after Gültig von. (Leer = unbefristet)
	 */
	public function checkGueltigBisAfterGueltigVon($gueltig_bis, $gueltig_von)
	{
		if (!$gueltig_bis) return true;

		return $gueltig_bis >= $gueltig_von;
	}

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

	/**
	 * Zuordnung laden, sonst mit Fehler abbrechen.
	 *
	 * @param $zuordnung_id
	 * @return object
	 */
	private function _getZuordnungOrFail($zuordnung_id)
	{
		if (!$zuordnung_id)
		{
			$this->terminateWithError('Missing Zuordnung ID');
		}

		$result = $this->LvevaluierungFragebogenZuordnungModel->load($zuordnung_id);
		$data = $this->getDataOrTerminateWithError($result);

		if (empty($data))
		{
			$this->terminateWithError('Zuordnung nicht gefunden');
		}

		return $data[0];
	}

	/**
	 * Fragebogen laden und abbrechen, wenn bereits in einer Evaluierung verwendet.
	 *
	 * @param $fragebogen_id
	 * @return object    Fragebogen
	 */
	private function _checkFragebogenNotVerwendet($fragebogen_id)
	{
		$fragebogen = $this->_getFragebogenOrFail($fragebogen_id);

		if ($fragebogen->isVerwendet)
		{
			$this->terminateWithError('Fragebogen wird bereits in einer Evaluierung verwendet. Für neue Zuordnungen bitte kopieren.');
		}

		return $fragebogen;
	}

	/**
	 * Fragebogen laden und abbrechen, wenn bereits beantwortet.
	 *
	 * @param $fragebogen_id
	 * @return object    Fragebogen
	 */
	private function _checkFragebogenNotLocked($fragebogen_id)
	{
		$fragebogen = $this->_getFragebogenOrFail($fragebogen_id);

		if ($fragebogen->hasAntworten)
		{
			$this->terminateWithError('Fragebogen wurde bereits beantwortet. Für Änderungen bitte kopieren.');
		}

		return $fragebogen;
	}

	/**
	 * Gruppe laden, sonst mit Fehler abbrechen.
	 *
	 * @param $gruppe_id
	 * @return object
	 */
	private function _getGruppeOrFail($gruppe_id)
	{
		if (!$gruppe_id)
		{
			$this->terminateWithError('Missing Gruppe ID');
		}

		$result = $this->LvevaluierungFragebogenGruppeModel->load($gruppe_id);
		$data = $this->getDataOrTerminateWithError($result);

		if (empty($data))
		{
			$this->terminateWithError('Gruppe nicht gefunden');
		}

		return $data[0];
	}

	/**
	 * Validates posted Gruppe.
	 *
	 * @param $data
	 * @return void
	 */
	private function _validateGruppeData($data)
	{
		$this->load->library('form_validation');

		$this->form_validation->set_data($data);
		$this->form_validation->set_rules('bezeichnung_de', 'Bezeichnung (DE)', 'required');
		$this->form_validation->set_rules('bezeichnung_en', 'Bezeichnung (EN)', 'required');
		$this->form_validation->set_rules('typ', 'Typ', 'required|in_list[group,card,label]');

		// On error
		if ($this->form_validation->run() == false)
		{
			$this->terminateWithValidationErrors($this->form_validation->error_array());
		}
	}
}
