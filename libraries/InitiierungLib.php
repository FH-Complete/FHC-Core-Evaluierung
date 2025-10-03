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
		$result = $this->_ci->LvevaluierungPrestudentModel->getByLveLv($lvevaluierung_lehrveranstaltung_id);
		$lvePrestudentenByLv = hasData($result) ? getData($result) : [];

		foreach ($data as $item)
		{
			$lehreinheitId = $item->lehreinheit_id;

			if (!isset($grouped[$lehreinheitId])) {
				$grouped[$lehreinheitId] = clone $item;
				$grouped[$lehreinheitId]->lektoren = [];
				$grouped[$lehreinheitId]->gruppen = [];
			}

			// Uniquely group Lektoren
			$grouped = $this->groupLektorenByLe($grouped, $item);

			// Uniquely group Gruppen
			$grouped = $this->groupGruppenByLe($grouped, $item);

			// Cleanup duplicates
			foreach ($grouped as $g)
			{
				unset(
					$g->mitarbeiter_uid,
					$g->fullname,
					$g->lehrfunktion_kurzbz,
					$g->semester,
					$g->verband,
					$g->gruppe
				);

				$g->lektoren = array_values($g->lektoren);
				$g->gruppen  = array_values($g->gruppen);
			}

			// Add Studenten
			$result = $this->_ci->LehreinheitModel->getStudenten($item->lehreinheit_id);
			$g->studenten = hasData($result) ? getData($result) : [];

			// Add Stundenplantermine
			$result = $this->_ci->StundenplanModel->getTermineByLe($item->lehreinheit_id);
			$g->stundenplan = hasData($result) ? getData($result) : [];

			// Add Studierende, that got mail by this or any other LE
			// Checks if Studierende of this LE are in lvePrestudenten table
			$g->sentByAnyEvaluierungOfLv = array_values(array_filter($lvePrestudentenByLv, function ($pre) use ($g) {
				foreach ($g->studenten as $s) {
					if ($s->prestudent_id === $pre->prestudent_id) {
						return $pre;
					}
				}
				return false;
			}));
		}

		return array_values($grouped);
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
		$result = $this->_ci->LvevaluierungPrestudentModel->getByLveLv($lvevaluierung_lehrveranstaltung_id);
		$lvePrestudentenByLv = hasData($result) ? getData($result) : [];

		foreach ($data as $item)
		{
			if (!isset($grouped[$lehrveranstaltung_id])) {
				$clone = clone $item;
				$clone->lehreinheit_id = null;
				$clone->lektoren = [];
				$clone->gruppen  = [];
				$clone->studenten = [];
				$clone->sentByAnyEvaluierungOfLv = [];
				$grouped[$lehrveranstaltung_id] = $clone;
			}

			// Uniquely group Gruppen
			$grouped = $this->groupGruppenByLv($grouped, $item);

			// Uniquely group Lektoren
			$grouped = $this->groupLektorenByLv($grouped, $item);


			// Cleanup duplicates
			foreach ($grouped as $g)
			{
				unset(
					$g->mitarbeiter_uid,
					$g->fullname,
					$g->lehrfunktion_kurzbz,
					$g->semester,
					$g->verband,
					$g->gruppe
				);

				$g->lektoren = array_values($g->lektoren);
				$g->gruppen  = array_values($g->gruppen);
			}
		}

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

//		// Add Studierende, that got mail by this or any other LE
		// Checks if Studierende of this LE are in lvePrestudenten table
		$grouped[$lehrveranstaltung_id]->sentByAnyEvaluierungOfLv = array_values(array_filter($lvePrestudentenByLv, function ($pre) use ($g) {
			foreach ($g->studenten as $s) {
				if ($s->prestudent_id === $pre->prestudent_id) {
					return $pre;
				}
			}
			return false;
		}));

		return array_values($grouped);
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

		foreach ($data as $row) {
			if (empty($row->lehreinheit_id)) {
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
	 * @param array $grouped	Array of data grouped by Lehreinheit ID
	 * @param object $item		Current data item containing lektor information
	 * @return mixed
	 */
	public function groupLektorenByLe($grouped, $item)
	{
		$lehreinheit_id = $item->lehreinheit_id;

		foreach ($grouped[$lehreinheit_id]->lektoren as $lektor) {
			if ($lektor->mitarbeiter_uid === $item->mitarbeiter_uid)
			{
				return $grouped;
			}
		}

		$grouped[$lehreinheit_id]->lektoren[] = (object)[
			'mitarbeiter_uid' => $item->mitarbeiter_uid,
			'vorname' => $item->vorname,
			'nachname' => $item->nachname,
			'fullname' => $item->fullname,
			'lehrfunktion_kurzbz' => $item->lehrfunktion_kurzbz
		];

		return $grouped;
	}

	/**
	 * Group Lektoren uniquely by Mitarbeiter UID, name, and Lehrfunktion within each LV group.
	 *
	 * @param $grouped
	 * @param $item
	 * @return mixed
	 */
	public function groupLektorenByLv($grouped, $item)
	{
		$lehrveranstaltung_id = $item->lehrveranstaltung_id;

		foreach ($grouped[$lehrveranstaltung_id]->lektoren as $lektor) {
			if ($lektor->mitarbeiter_uid === $item->mitarbeiter_uid)
			{
				return $grouped;
			}
		}

		$grouped[$lehrveranstaltung_id]->lektoren[] = (object)[
			'mitarbeiter_uid' => $item->mitarbeiter_uid,
			'vorname' => $item->vorname,
			'nachname' => $item->nachname,
			'fullname' => $item->fullname,
			'lehrfunktion_kurzbz' => $item->lehrfunktion_kurzbz
		];

		return $grouped;
	}

	/**
	 * Group Gruppen uniquely by Kurzbzlang, Semester, Verband, and Gruppe within each Lehreinheit group.
	 *
	 * @param array $grouped	Array of data grouped by Lehreinheit ID
	 * @param object $item		Current data item containing gruppen information
	 * @return mixed
	 */
	public function groupGruppenByLe($grouped, $item)
	{
		$lehreinheit_id = $item->lehreinheit_id;

		foreach ($grouped[$lehreinheit_id]->gruppen as $gruppe) {
			if (
				$gruppe->kurzbzlang === $item->kurzbzlang &&
				$gruppe->semester === $item->semester &&
				$gruppe->verband === $item->verband &&
				$gruppe->gruppe === $item->gruppe
			) {
				return $grouped;
			}
		}

		$grouped[$lehreinheit_id]->gruppen[] = (object)[
			'kurzbzlang' => $item->kurzbzlang,
			'semester' => $item->semester,
			'verband' => $item->verband,
			'gruppe' => $item->gruppe
		];

		return $grouped;
	}

	/**
	 * Group Gruppen uniquely by Kurzbzlang, Semester, Verband, and Gruppe within each LV group.
	 *
	 * @param $grouped
	 * @param $item
	 * @return mixed
	 */
	public function groupGruppenByLv($grouped, $item)
	{
		$lehrveranstaltung_id = $item->lehrveranstaltung_id;

		foreach ($grouped[$lehrveranstaltung_id]->gruppen as $gruppe) {
			if (
				$gruppe->kurzbzlang === $item->kurzbzlang &&
				$gruppe->semester === $item->semester &&
				$gruppe->verband === $item->verband &&
				$gruppe->gruppe === $item->gruppe
			) {
				return $grouped;
			}
		}

		$grouped[$lehrveranstaltung_id]->gruppen[] = (object)[
			'kurzbzlang' => $item->kurzbzlang,
			'semester' => $item->semester,
			'verband' => $item->verband,
			'gruppe' => $item->gruppe
		];

		return $grouped;
	}
}