<?php

/**
 * FH-Complete
 *
 * @package             FHC-Helper
 * @author              FHC-Team
 * @copyright           Copyright (c) 2022 fhcomplete.net
 * @license             GPLv3
 */

if (! defined('BASEPATH')) exit('No direct script access allowed');

class InitiierungLib
{
	private $_ci; // Code igniter instance
	public function __construct()
	{
		$this->_ci =& get_instance();

		$this->_ci->load->helper('hlp_sancho_helper');
	}

	/**
	 * Group data by Lehreinheit.
	 *
	 * @param $data
	 * @return array
	 */
	public function groupByLeAndAddData($data, $lvevaluierung_lehrveranstaltung_id)
	{
		$grouped = [];

		$this->_ci->load->model('ressource/Stundenplan_model', 'StundenplanModel');
		$this->_ci->load->model('education/Lehreinheit_model', 'LehreinheitModel');

		foreach ($data as $item)
		{
			$lehreinheitId = $item->lehreinheit_id;

			// If new Lehreinheit found
			if (!isset($grouped[$lehreinheitId]))
			{
				$grouped[$lehreinheitId] = clone $item;

				// Uniquely group Gruppen
				$grouped[$lehreinheitId]->gruppen = $this->groupGruppenByLe($data, $lehreinheitId);

				// Uniquely group Lehrende
				$grouped[$lehreinheitId]->lektoren = $this->groupLektorenByLe($data, $lehreinheitId);

				// Add Studenten
				$result = $this->_ci->LehreinheitModel->getStudenten($item->lehreinheit_id);
				$grouped[$lehreinheitId]->studenten = hasData($result) ? getData($result) : [];

				// Add Stundenplantermine
				$result = $this->_ci->StundenplanModel->getTermineByLe($item->lehreinheit_id);
				$grouped[$lehreinheitId]->stundenplan = hasData($result) ? getData($result) : [];

				// Add Studierende, that got mail by this or any other LE
				$result = $this->_ci->LvevaluierungPrestudentModel->getByLveLv($lvevaluierung_lehrveranstaltung_id);
				$lvePrestudentenByLv = hasData($result) ? getData($result) : [];
				$grouped[$lehreinheitId]->sentByAnyEvaluierungOfLv = array_filter($lvePrestudentenByLv, function ($pre) use ($grouped, $lehreinheitId) {
					foreach ($grouped[$lehreinheitId]->studenten as $s) {
						if ($s->prestudent_id === $pre->prestudent_id) {
							return $pre;
						}
					}
					return false;
				});
			}
		}

		$grouped = array_values($grouped);

		// Remove properties that were grouped in gruppen and lektoren
		foreach ($grouped as $g)
		{
			unset(
				$g->mitarbeiter_uid,
				$g->vorname,
				$g->nachname,
				$g->fullname,
				$g->lehrfunktion_kurzbz,
				$g->semester,
				$g->verband,
				$g->gruppe,
				$g->gruppe_kurzbz,
				$g->gruppe_bezeichnung,
				$g->direktinskription
			);
		}

		return $grouped;
	}

	/**
	 * Group data by Lehrveranstaltung.
	 *
	 * @param $data
	 * @param $lehrveranstaltung_id
	 * @param $studiensemester_kurzbz
	 * @return array|mixed
	 */
	public function groupByLvAndAddData($data, $lvevaluierung_lehrveranstaltung_id, $lehrveranstaltung_id, $studiensemester_kurzbz)
	{
		$grouped = [];

		foreach ($data as $item)
		{
			if (!isset($grouped[$lehrveranstaltung_id]))
			{
				$clone = clone $item;
				$clone->lehreinheit_id = null;

				$grouped[$lehrveranstaltung_id] = $clone;

				// Group unique Gruppen
				$grouped[$lehrveranstaltung_id]->gruppen = $this->groupGruppenByLv($data);

				// Group unique Lehrende
				$grouped[$lehrveranstaltung_id]->lektoren = $this->groupLektorenByLv($data);

				// Add Students of LV
				$this->_ci->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');
				$result = $this->_ci->LehrveranstaltungModel->getStudentsByLv(
					$studiensemester_kurzbz,
					$lehrveranstaltung_id,
					true	// true = only active students
				);
				$grouped[$lehrveranstaltung_id]->studenten = hasData($result) ? getData($result) : [];

				// Add Stundenplantermine for LV
				$this->_ci->load->model('ressource/Stundenplan_model', 'StundenplanModel');
				$result = $this->_ci->StundenplanModel->getTermineByLv($lehrveranstaltung_id, $studiensemester_kurzbz);
				$grouped[$lehrveranstaltung_id]->stundenplan = hasData($result) ? getData($result) : [];

				// Add Studierende, that got mail by this or any other LE
				$result = $this->_ci->LvevaluierungPrestudentModel->getByLveLv($lvevaluierung_lehrveranstaltung_id);
				$lvePrestudentenByLv = hasData($result) ? getData($result) : [];
				$grouped[$lehrveranstaltung_id]->sentByAnyEvaluierungOfLv = array_values(array_filter($lvePrestudentenByLv, function ($pre) use ($grouped, $lehrveranstaltung_id) {
					foreach ($grouped[$lehrveranstaltung_id]->studenten as $s) {
						if ($s->prestudent_id === $pre->prestudent_id) {
							return $pre;
						}
					}
					return false;
				}));
			}
		}

		$grouped = array_values($grouped);

		// Remove properties that were grouped in gruppen and lektoren
		foreach ($grouped as $g)
		{
			unset(
				$g->mitarbeiter_uid,
				$g->vorname,
				$g->nachname,
				$g->fullname,
				$g->lehrfunktion_kurzbz,
				$g->semester,
				$g->verband,
				$g->gruppe,
				$g->gruppe_kurzbz,
				$g->gruppe_bezeichnung,
				$g->direktinskription
			);
		}

		return $grouped;
	}

	public function mergeEvaluierungenIntoData($data, $evaluierungen, $isAufgeteilt)
	{
		foreach ($data as &$item) {
			$evalMatch = null;

			if ($isAufgeteilt) {
				foreach ($evaluierungen as $ev) {
					if ($ev->lehreinheit_id == $item->lehreinheit_id) {
						$evalMatch = $ev;
						break;
					}
				}
			}
			else
			{
				foreach ($evaluierungen as $ev) {
					if ($ev->lvevaluierung_lehrveranstaltung_id == $item->lvevaluierung_lehrveranstaltung_id) {
						$evalMatch = $ev;
						break;
					}
				}
			}

			if ($evalMatch) {
				$item->lvevaluierung_id = $evalMatch->lvevaluierung_id;
				$item->startzeit        = $evalMatch->startzeit;
				$item->endezeit         = $evalMatch->endezeit;
				$item->dauer            = $evalMatch->dauer;
				$item->codes_gemailt    = $evalMatch->codes_gemailt;
				$item->codes_ausgegeben = $evalMatch->codes_ausgegeben;
				$item->insertvon        = $evalMatch->insertvon;
				$item->insertamum       = $evalMatch->insertamum;
			}
			else
			{
				// Fallback Defaults
				$now = new DateTime();
				$ende = (clone $now)->modify('+3 days');

				$item->lvevaluierung_id = null;
				$item->startzeit        = $now->format('Y-m-d H:i:s');
				$item->endezeit         = $ende->format('Y-m-d H:i:s');
				$item->dauer            = null;
				$item->codes_gemailt    = false;
				$item->codes_ausgegeben = 0;
				$item->insertvon        = '';
				$item->insertamum       = '';
			}
		}

		return $data;
	}

	/**
	 * Check if current user has LV-Leitung access for a given Lehrveranstaltung.
	 *
	 * @param $lehrveranstaltung_id
	 * @param $studiensemester_kurzbz
	 * @return void
	 */
	public function checkLvLeitungAccess($lehrveranstaltung_id, $studiensemester_kurzbz)
	{
		// Check for LV-Leitung
		$this->_ci->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');
		$result = $this->_ci->LehrveranstaltungModel->getLvLeitung($lehrveranstaltung_id, $studiensemester_kurzbz);

		// If LV-Leitung exist
		if (hasData($result))
		{
			// check if user is LV-Leitung
			if (getData($result)[0]->mitarbeiter_uid != getAuthUid())
			{
				// exit if not
				return error('Access for LV-Leitung only.');

			}

			return success(getData($result)[0]);
		}
		else
		{
			// todo check mit Ösi: config ok?
			if ($this->_ci->config->item('lvLeitungRequired'))
			{
				return error('No LV-Leitung assigned for this LV.');
			}
		}
	}

	/**
	 * Check if Evaluation is evaluated by Lehreinheit/Gruppenbasis.
	 *
	 * @param $lvevaluierung_lehrveranstaltung_id
	 * @return mixed If true, Evaluation is evaluated by Lehreinheiten (Gruppenbasis). If false, then on Gesamt-LV.
	 */
	public function isLvAufgeteilt($lvevaluierung_lehrveranstaltung_id)
	{
		$this->_ci->LvevaluierungLehrveranstaltungModel->addSelect('lv_aufgeteilt');
		$result = $this->_ci->LvevaluierungLehrveranstaltungModel->load($lvevaluierung_lehrveranstaltung_id);
		$lvelv = hasData($result) ? getData($result)[0] : [];

		return $lvelv->lv_aufgeteilt;
	}

	/**
	 * Generates code and sends mail to single student: transaction safe
	 */
	public function generateAndSendCodeForStudent($lve, $student, $lehrveranstaltung_id)
	{
		$lvevaluierung_id = $lve->lvevaluierung_id;

		$this->_ci->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');
		$this->_ci->LehrveranstaltungModel->addSelect('bezeichnung');
		$result = $this->_ci->LehrveranstaltungModel->load($lehrveranstaltung_id);
		$lvBezeichnung = hasData($result) ? getData($result)[0]->bezeichnung : '';

		$this->_ci->db->trans_begin();

		$code = $this->_ci->LvevaluierungCodeModel->getUniqueCode();
		$url  = APP_ROOT . 'index.ci.php/extensions/FHC-Core-Evaluierung/Evaluierung?code=' . urlencode($code);

		$mailData = [
			'vorname'         => $student->vorname,
			'nachname'        => $student->nachname,
			'startzeit'        => (new DateTime($lve->startzeit))->format('d.m.Y, H:i'),
			'endezeit'         => (new DateTime($lve->endezeit))->format('d.m.Y, H:i'),
			'lvbezeichnung' => $lvBezeichnung,
			'evaluierunglink' => $url,
		];

		$mailSent = sendSanchoMail(
			'Lvevaluierung_Mail_Codeversand',
			$mailData,
			$student->uid . '@' . DOMAIN,
			'Evaluieren Sie jetzt Ihre LV '. $lvBezeichnung
		);

		if ($mailSent)
		{
			// Save Code mapping
			$this->_ci->LvevaluierungCodeModel->insert([
				'lvevaluierung_id' => $lvevaluierung_id,
				'code'             => $code
			]);

			// Save Prestudent mapping
			$this->_ci->LvevaluierungPrestudentModel->insert([
				'prestudent_id'     => $student->prestudent_id,
				'lvevaluierung_id'  => $lvevaluierung_id,
				'insertvon'         => getAuthUid(),
			]);

			$this->_ci->db->trans_commit();
			return true;
		}
		else
		{
			$this->_ci->db->trans_rollback();
			return false;
		}
	}

	/**
	 * Checks if each Lehreinheit has exactly one unique Lektor.
	 *
	 * @param array $data
	 * @return bool
	 */
	public function hasUniqueLectorPerLehreinheit($data)
	{
		$lectorsPerLe = [];

		foreach ($data as $row) {
			if (empty($row->lehreinheit_id) || empty($row->mitarbeiter_uid)) {
				continue;
			}

			$leId = $row->lehreinheit_id;
			$lectorsPerLe[$leId][$row->mitarbeiter_uid] = true;
		}

		foreach ($lectorsPerLe as $uids) {
			if (count($uids) !== 1) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks if Lehrveranstaltung has:
	 *  - duplicate Studierendengruppen (BBE-2A1 <-> BBE-2A1)
	 *  - duplicate combination of Studierendengruppen
	 *    (BBE-2A1, BBE-2A2 <-> BBE-2A1, BBE-2A2)
	 *  - hierarchical duplicates (BBE-2 <-> BBE-2A1)
	 *
	 * @param array $data
	 * @return bool True if duplicates found
	 */
	public function hasHierarchicalDuplicateGruppen($data)
	{
		$gruppenPerLe = [];

		// Skip row check if...
		foreach ($data as $row) {
			//...is not Lehreinheit
			if (empty($row->lehreinheit_id)) {
				continue;
			}
			//...is Spezialgruppe
			if (!empty($row->gruppe_kurzbz)) {
				continue;
			}

			$gruppenPerLe[$row->lehreinheit_id][] = (object)[
				'kurzbzlang' => $row->kurzbzlang,
				'semester'   => $row->semester,
				'verband'    => $row->verband,
				'gruppe'     => $row->gruppe,
			];
		}

		// Compare across Lehreinheiten
		foreach ($gruppenPerLe as $i => $gruppen1) {
			foreach ($gruppenPerLe as $j => $gruppen2) {
				if ($i === $j) continue;

				foreach ($gruppen1 as $g1) {
					foreach ($gruppen2 as $g2) {
						if (
							$g1->kurzbzlang === $g2->kurzbzlang &&
							$g1->semester === $g2->semester &&
							(
								($g1->verband === $g2->verband && $g1->gruppe === $g2->gruppe) ||   // exact duplicate
								($g1->verband === $g2->verband && !$g1->gruppe && $g2->gruppe) ||  // parent vs subgroup
								($g2->verband === $g1->verband && !$g2->gruppe && $g1->gruppe) ||  // inverse parent vs subgroup
								(!$g1->verband && !$g1->gruppe && $g2->verband) ||                 // faculty vs class
								(!$g2->verband && !$g2->gruppe && $g1->verband)                    // inverse
							)
						) {
							return true;
						}
					}
				}
			}
		}

		return false;
	}

	/**
	 * Group Lektoren uniquely by Mitarbeiter UID, name, and Lehrfunktion within each Lehreinheit group.
	 *
	 * @param $data
	 * @param $lehreinheit_id
	 * @return mixed
	 */
	public function groupLektorenByLe($data, $lehreinheit_id)
	{
		$lektoren = [];

		foreach ($data as $item) {
			if ($item->lehreinheit_id === $lehreinheit_id)
			{
				// Avoid duplicates
				if (!in_array($item->mitarbeiter_uid, array_column($lektoren, 'mitarbeiter_uid'))) {
					$lektoren[] = [
						'mitarbeiter_uid' => $item->mitarbeiter_uid,
						'vorname' => $item->vorname,
						'nachname' => $item->nachname,
						'fullname' => $item->fullname,
						'lehrfunktion_kurzbz' => $item->lehrfunktion_kurzbz,
					];
				}
			}
		}

		return $lektoren;
	}

	/**
	 * Group Lektoren uniquely by Mitarbeiter UID, name, and Lehrfunktion within each LV group.
	 *
	 * @param $data
	 * @return mixed
	 */
	public function groupLektorenByLv($data)
	{
		$lektoren = [];

		foreach ($data as $item) {

			// Avoid duplicates
			if (!in_array($item->mitarbeiter_uid, array_column($lektoren, 'mitarbeiter_uid'))) {
				$lektoren[] = [
					'mitarbeiter_uid' => $item->mitarbeiter_uid,
					'vorname' => $item->vorname,
					'nachname' => $item->nachname,
					'fullname' => $item->fullname,
					'lehrfunktion_kurzbz' => $item->lehrfunktion_kurzbz,
				];
			}
		}

		return $lektoren;
	}

	/**
	 * Group Gruppen uniquely by Kurzbzlang, Semester, Verband, and Gruppe within each Lehreinheit group.
	 *
	 * @param $data
	 * @param $lehreinheit_id
	 * @return mixed
	 */
	public function groupGruppenByLe($data, $lehreinheit_id)
	{
		$gruppen = [];

		foreach ($data as $item) {
			if ($item->lehreinheit_id === $lehreinheit_id) {

				// Skip if is Spezialgruppe with Direktinskription
				if (!empty($item->gruppe_kurzbz) && $item->direktinskription === true) {
					continue;
				}

				// Avoid duplicates
				if (!in_array($item->gruppe_bezeichnung, array_column($gruppen, 'gruppe_bezeichnung'))) {
					$gruppen[] = [
						'gruppe_bezeichnung' => $item->gruppe_bezeichnung
					];
				}
			}
		}
		return $gruppen;
	}

	/**
	 * Group Gruppen uniquely by Kurzbzlang, Semester, Verband, and Gruppe within each LV group.
	 *
	 * @param $data
	 * @return mixed
	 */
	public function groupGruppenByLv($data)
	{
		$gruppen = [];

		foreach ($data as $item) {

			// Skip if is Spezialgruppe with Direktinskription
			if (!empty($item->gruppe_kurzbz) && $item->direktinskription === true) {
				continue;
			}

			// Avoid duplicates
			if (!in_array($item->gruppe_bezeichnung, array_column($gruppen, 'gruppe_bezeichnung'))) {
				$gruppen[] = [
					'gruppe_bezeichnung' => $item->gruppe_bezeichnung
				];
			}
		}
		return array_values($gruppen);
	}
}