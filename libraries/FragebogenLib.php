<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Laden und Speichern von Fragebögen inkl. Gruppen, Fragen, Antworten und Zuordnungen.
 */
class FragebogenLib
{
	const FRAGE_TYP_SINGLERESPONSE = 'singleresponse';
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
	 * Fragebogen löschen. Zuordnungen, Gruppen und Gruppe-Frage per Cascade.
	 * Fragen, die danach keiner Gruppe mehr zugeordnet und unbeantwortet sind, werden mitgelöscht.
	 *
	 * @param $fragebogen_id
	 * @return mixed
	 */
	public function deleteFragebogen($fragebogen_id)
	{
		// Fragen merken, bevor Gruppe-Frage per Cascade gelöscht wird
		$result = $this->_ci->LvevaluierungFragebogenFrageModel->getFragenForEditor($fragebogen_id);
		if (isError($result)) return $result;
		$frageIds = hasData($result)
			? array_values(array_unique(array_column(getData($result), 'lvevaluierung_frage_id')))
			: [];

		$this->_ci->db->trans_begin();

		// Fragebogen löschen
		$result = $this->_ci->LvevaluierungFragebogenModel->delete($fragebogen_id);

		// Wenn Fragen vorhanden
		if (!isError($result) && !empty($frageIds))
		{
			// Fragen löschen, die keiner Gruppe zugeordnet sind und nie beantwortet wurden.
			$result = $this->_ci->LvevaluierungFragebogenFrageModel->deleteUnbenutzteFragen($frageIds);
		}

		if (isError($result) || $this->_ci->db->trans_status() === false)
		{
			$this->_ci->db->trans_rollback();
			return isError($result) ? $result : error('Fragebogen konnte nicht gelöscht werden');
		}

		$this->_ci->db->trans_commit();

		return success($fragebogen_id);
	}

	/**
	 * Insert Gruppe am Ende des Fragebogens.
	 *
	 * @param $fragebogen_id
	 * @param $data
	 * @return mixed    ID der neuen Gruppe
	 */
	public function insertGruppe($fragebogen_id, $data)
	{
		// Neue Gruppe ans Ende: höchste Sortierung + 1
		$result = $this->_ci->LvevaluierungFragebogenGruppeModel->loadWhere(['fragebogen_id' => $fragebogen_id]);
		if (isError($result)) return $result;
		$sorts = hasData($result) ? array_column(getData($result), 'sort') : [];
		$sort = empty($sorts) ? 1 : max($sorts) + 1;

		$result = $this->_ci->LvevaluierungFragebogenGruppeModel->insertGruppe(
			$fragebogen_id,
			$data['typ'],
			$data['bezeichnung_de'],
			$data['bezeichnung_en'],
			null,
			$sort
		);
		if (isError($result)) return $result;

		return success(getData($result)[0]->lvevaluierung_fragebogen_gruppe_id);
	}

	/**
	 * Delete Gruppe. Gruppe-Frage per Cascade, Fragen ohne weitere Zuordnung und ohne Antworten werden mitgelöscht.
	 *
	 * @param $gruppe_id
	 * @return mixed
	 */
	public function deleteGruppe($gruppe_id)
	{
		// Fragen merken, bevor Gruppe-Frage per Cascade gelöscht wird
		$result = $this->_ci->LvevaluierungFragebogenGruppeFrageModel->loadWhere(['lvevaluierung_fragebogen_gruppe_id' => $gruppe_id]);
		if (isError($result)) return $result;
		$frageIds = hasData($result) ? array_column(getData($result), 'lvevaluierung_frage_id') : [];

		$this->_ci->db->trans_begin();

		$result = $this->_ci->LvevaluierungFragebogenGruppeModel->delete($gruppe_id);

		if (!isError($result) && !empty($frageIds))
		{
			$result = $this->_ci->LvevaluierungFragebogenFrageModel->deleteUnbenutzteFragen($frageIds);
		}

		// Bei Fehler alles zurücknehmen
		if (isError($result) || $this->_ci->db->trans_status() === false)
		{
			$this->_ci->db->trans_rollback();
			return isError($result) ? $result : error('Gruppe konnte nicht gelöscht werden');
		}

		$this->_ci->db->trans_commit();
		return success($gruppe_id);
	}

	/**
	 * Sort Gruppen nach übergebener Reihenfolge. Nur Gruppen dieses Fragebogens.
	 *
	 * @param $fragebogen_id
	 * @param $gruppeIds
	 * @return mixed
	 */
	public function sortGruppen($fragebogen_id, $gruppeIds)
	{
		$result = success(true);

		$this->_ci->db->trans_begin();

		foreach (array_values($gruppeIds) as $index => $gruppe_id)
		{
			$result = $this->_ci->LvevaluierungFragebogenGruppeModel->update(
				['lvevaluierung_fragebogen_gruppe_id' => $gruppe_id, 'fragebogen_id' => $fragebogen_id],
				['sort' => $index + 1]
			);
			if (isError($result)) break;
		}

		// Bei Fehler alles zurücknehmen
		if (isError($result) || $this->_ci->db->trans_status() === false)
		{
			$this->_ci->db->trans_rollback();
			return isError($result) ? $result : error('Reihenfolge konnte nicht gespeichert werden');
		}

		$this->_ci->db->trans_commit();
		return success($fragebogen_id);
	}

	/**
	 * Gruppe eines anderen Fragebogens übernehmen: neue Gruppe am Ende, Fragen samt Antworten mit gleicher ID zuordnen.
	 *
	 * @param $fragebogen_id
	 * @param $vorlage    Gruppe des anderen Fragebogens
	 * @return mixed Gruppe ID
	 */
	public function uebernehmenGruppe($fragebogen_id, $vorlage)
	{
		$this->_ci->db->trans_begin();

		// Bezeichnung als Array: 0 = DE, 1 = EN
		$result = $this->insertGruppe($fragebogen_id, [
			'typ' => $vorlage->typ,
			'bezeichnung_de' => isset($vorlage->bezeichnung[0]) ? $vorlage->bezeichnung[0] : null,
			'bezeichnung_en' => isset($vorlage->bezeichnung[1]) ? $vorlage->bezeichnung[1] : null
		]);

		if (!isError($result))
		{
			$gruppe_id = getData($result);
			$result = $this->_ci->LvevaluierungFragebogenGruppeFrageModel->copyFragen(
				$vorlage->lvevaluierung_fragebogen_gruppe_id,
				$gruppe_id
			);
		}

		if (isError($result) || $this->_ci->db->trans_status() === false)
		{
			$this->_ci->db->trans_rollback();
			return isError($result) ? $result : error('Gruppe konnte nicht übernommen werden');
		}

		$this->_ci->db->trans_commit();

		return success($gruppe_id);
	}

	/**
	 * Frage samt Antwortoptionen für das Modal.
	 *
	 * @param $frage_id
	 * @return mixed
	 */
	public function getFrage($frage_id)
	{
		$result = $this->_ci->LvevaluierungFragebogenFrageModel->getFrage($frage_id);
		if (isError($result)) return $result;
		if (!hasData($result)) return error('Frage nicht gefunden');
		$frage = getData($result)[0];

		$result = $this->_ci->LvevaluierungFragebogenFrageAntwortModel->getAntwortenByFragen([$frage_id]);
		if (isError($result)) return $result;
		$frage->antworten = hasData($result) ? getData($result) : [];

		return success($frage);
	}

	/**
	 * Neue Frage samt Antwortoptionen anlegen und der Gruppe zuordnen.
	 *
	 * @param $gruppe_id
	 * @param $data
	 * @return mixed    ID der neuen Frage
	 */
	public function insertFrage($gruppe_id, $data)
	{
		// Neue Frage ans Ende der Gruppe: höchste Sortierung + 1
		$result = $this->_ci->LvevaluierungFragebogenFrageModel->getFragenByFragebogengruppe($gruppe_id);
		if (isError($result)) return $result;
		$sorts = hasData($result) ? array_column(getData($result), 'sort') : [];
		$sort = empty($sorts) ? 1 : max($sorts) + 1;

		$this->_ci->db->trans_begin();

		$result = $this->_ci->LvevaluierungFragebogenFrageModel->insertFrage(
			$data['typ'],
			$data['bezeichnung_de'],
			$data['bezeichnung_en'],
			$data['placeholder_de'],
			$data['placeholder_en'],
			$data['verpflichtend'],
			$sort
		);

		if (!isError($result))
		{
			$frage_id = getData($result)[0]->lvevaluierung_frage_id;
			$result = $this->_ci->LvevaluierungFragebogenGruppeFrageModel->insert([
				'lvevaluierung_fragebogen_gruppe_id' => $gruppe_id,
				'lvevaluierung_frage_id' => $frage_id
			]);
		}

		if (!isError($result))
		{
			$result = $this->_insertAntworten($frage_id, $data);
		}

		// Bei Fehler alles zurücknehmen
		if (isError($result) || $this->_ci->db->trans_status() === false)
		{
			$this->_ci->db->trans_rollback();
			return isError($result) ? $result : error('Frage konnte nicht gespeichert werden');
		}

		$this->_ci->db->trans_commit();

		return success($frage_id);
	}

	/**
	 * Sort Fragen nach übergebener Reihenfolge (frage.sort). Nur Fragen dieser Gruppe.
	 *
	 * @param $gruppe_id
	 * @param $frageIds
	 * @return mixed
	 */
	public function sortFragen($gruppe_id, $frageIds)
	{
		// Nur Fragen dieser Gruppe
		$result = $this->_ci->LvevaluierungFragebogenGruppeFrageModel->loadWhere(['lvevaluierung_fragebogen_gruppe_id' => $gruppe_id]);
		if (isError($result)) return $result;
		$gruppeFrageIds = hasData($result) ? array_column(getData($result), 'lvevaluierung_frage_id') : [];

		if (!empty(array_diff($frageIds, $gruppeFrageIds))) return error('Frage gehört nicht zu dieser Gruppe');

		$result = success(true);

		$this->_ci->db->trans_begin();

		foreach (array_values($frageIds) as $index => $frage_id)
		{
			$result = $this->_ci->LvevaluierungFragebogenFrageModel->update($frage_id, ['sort' => $index + 1]);
			if (isError($result)) break;
		}

		if (isError($result) || $this->_ci->db->trans_status() === false)
		{
			$this->_ci->db->trans_rollback();
			return isError($result) ? $result : error('Reihenfolge konnte nicht gespeichert werden');
		}

		$this->_ci->db->trans_commit();
		return success($gruppe_id);
	}

	/**
	 * Frage ändern. Sortierung bleibt, Antwortoptionen werden neu angelegt.
	 *
	 * @param $frage    Gespeicherte Frage
	 * @param $data
	 * @return mixed    Frage ID
	 */
	public function updateFrage($frage, $data)
	{
		$frage_id = $frage->lvevaluierung_frage_id;

		$this->_ci->db->trans_begin();

		$result = $this->_ci->LvevaluierungFragebogenFrageModel->updateFrage(
			$frage_id,
			$data['typ'],
			$data['bezeichnung_de'],
			$data['bezeichnung_en'],
			$data['placeholder_de'],
			$data['placeholder_en'],
			$data['verpflichtend'],
			$frage->sort
		);

		if (!isError($result))
		{
			$result = $this->_ci->LvevaluierungFragebogenFrageAntwortModel->delete(['lvevaluierung_frage_id' => $frage_id]);
		}

		if (!isError($result))
		{
			$result = $this->_insertAntworten($frage_id, $data);
		}

		// Bei Fehler alles zurücknehmen
		if (isError($result) || $this->_ci->db->trans_status() === false)
		{
			$this->_ci->db->trans_rollback();
			return isError($result) ? $result : error('Frage konnte nicht gespeichert werden');
		}

		$this->_ci->db->trans_commit();

		return success($frage_id);
	}

	/**
	 * Antwortoptionen anlegen, nur bei singleresponse. Sortierung nach Reihenfolge.
	 *
	 * @param $frage_id
	 * @param $data
	 * @return mixed
	 */
	private function _insertAntworten($frage_id, $data)
	{
		if ($data['typ'] !== self::FRAGE_TYP_SINGLERESPONSE) return success();

		foreach (array_values($data['antworten']) as $index => $antwort)
		{
			$result = $this->_ci->LvevaluierungFragebogenFrageAntwortModel->insertAntwort(
				$frage_id,
				$antwort['bezeichnung_de'],
				$antwort['bezeichnung_en'],
				$index + 1,
				$antwort['wert']
			);
			if (isError($result)) return $result;
		}

		return success();
	}
}
