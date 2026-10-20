<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Laden und Speichern von Fragebögen inkl. Gruppen, Fragen, Antworten und Zuordnungen.
 */
class FragebogenLib
{
	private $_ci;

	public function __construct()
	{
		$this->_ci =& get_instance();

		$this->_ci->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogen_model', 'LvevaluierungFragebogenModel');
		$this->_ci->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogenZuordnung_model', 'LvevaluierungFragebogenZuordnungModel');
		$this->_ci->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogenGruppe_model', 'LvevaluierungFragebogenGruppeModel');
		$this->_ci->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogenFrage_model', 'LvevaluierungFragebogenFrageModel');
		$this->_ci->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogenFrageAntwort_model', 'LvevaluierungFragebogenFrageAntwortModel');
		$this->_ci->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogenGruppeFrage_model', 'LvevaluierungFragebogenGruppeFrageModel');
	}

	/**
	 * Fragebogen mit Zuordnungen und Struktur Gruppe > Frage > Antworten laden.
	 *
	 * @param $fragebogen_id
	 * @return mixed
	 */
	public function getFragebogenStruktur($fragebogen_id)
	{
		$result = $this->_ci->LvevaluierungFragebogenModel->getFragebogen($fragebogen_id);
		if (isError($result)) return $result;
		if (!hasData($result)) return error('Fragebogen nicht gefunden');
		$fragebogen = getData($result)[0];

		$result = $this->_ci->LvevaluierungFragebogenZuordnungModel->getByFragebogen($fragebogen_id);
		if (isError($result)) return $result;
		$fragebogen->zuordnungen = hasData($result) ? getData($result) : [];

		$result = $this->_ci->LvevaluierungFragebogenGruppeModel->getByFragebogen($fragebogen_id);
		if (isError($result)) return $result;
		$gruppen = hasData($result) ? getData($result) : [];

		$result = $this->_ci->LvevaluierungFragebogenFrageModel->getFragenForEditor($fragebogen_id);
		if (isError($result)) return $result;
		$fragen = hasData($result) ? getData($result) : [];

		$antworten = [];
		if (!empty($fragen))
		{
			$frageIds = array_values(array_unique(array_column($fragen, 'lvevaluierung_frage_id')));
			$result = $this->_ci->LvevaluierungFragebogenFrageAntwortModel->getAntwortenByFragen($frageIds);
			if (isError($result)) return $result;
			$antworten = hasData($result) ? getData($result) : [];
		}

		// Struktur aufbauen: Gruppe > Frage > Antworten
		foreach ($gruppen as $gruppe)
		{
			$gruppe->fragen = [];

			foreach ($fragen as $frage)
			{
				if ($frage->lvevaluierung_fragebogen_gruppe_id !== $gruppe->lvevaluierung_fragebogen_gruppe_id) continue;

				$frage->antworten = array_values(array_filter($antworten, function ($antwort) use ($frage)
				{
					return $antwort->lvevaluierung_frage_id === $frage->lvevaluierung_frage_id;
				}));

				$gruppe->fragen[] = $frage;
			}
		}

		$fragebogen->gruppen = $gruppen;

		return success($fragebogen);
	}

	/**
	 * Fragebogen kopieren: Gruppen werden neu angelegt, Fragen bleiben verknüpft (n:m). Ohne Zuordnungen.
	 *
	 * @param $vorlage_id
	 * @param $data    Basisdaten der Kopie
	 * @return mixed    ID des neuen Fragebogens
	 */
	public function copyFragebogen($vorlage_id, $data)
	{
		// Gruppen der Vorlage
		$result = $this->_ci->LvevaluierungFragebogenGruppeModel->getByFragebogen($vorlage_id);
		if (isError($result)) return $result;
		$gruppen = hasData($result) ? getData($result) : [];

		$this->_ci->db->trans_begin();

		// Neuen Fragebogen anlegen
		$result = $this->_ci->LvevaluierungFragebogenModel->insertFragebogen(
			$data['bezeichnung_de'],
			$data['bezeichnung_en'],
			$data['gueltig_von'],
			$data['gueltig_bis'],
			getAuthUid()
		);

		if (!isError($result))
		{
			$fragebogen_id = getData($result)[0]->fragebogen_id;

			// Gruppen neu anlegen (gehören genau einem Fragebogen), Fragen samt Antworten mit gleicher ID zuordnen
			foreach ($gruppen as $gruppe)
			{
				$result = $this->_ci->LvevaluierungFragebogenGruppeModel->insertGruppe(
					$fragebogen_id,
					$gruppe->typ,
					$gruppe->bezeichnung_de,
					$gruppe->bezeichnung_en,
					$gruppe->style,
					$gruppe->sort
				);
				if (isError($result)) break;

				$result = $this->_ci->LvevaluierungFragebogenGruppeFrageModel->copyFragen(
					$gruppe->lvevaluierung_fragebogen_gruppe_id,
					getData($result)[0]->lvevaluierung_fragebogen_gruppe_id
				);
				if (isError($result)) break;
			}
		}

		// Bei Fehler alles zurücknehmen
		if (isError($result) || $this->_ci->db->trans_status() === false)
		{
			$this->_ci->db->trans_rollback();
			return isError($result) ? $result : error('Fragebogen konnte nicht kopiert werden');
		}

		$this->_ci->db->trans_commit();
		return success($fragebogen_id);
	}
}
