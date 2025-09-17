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
	 * Group Lektoren uniquely by Mitarbeiter UID, name, and Lehrfunktion within each Lehreinheit group.
	 *
	 * @param array $grouped	Array of data grouped by Lehreinheit ID
	 * @param object $item		Current data item containing lektor information
	 * @return mixed
	 */
	public function groupLektoren($grouped, $item)
	{
		$lehreinheit_id = $item->lehreinheit_id;

		foreach ($grouped[$lehreinheit_id]->lektoren as $lektor) {
			if (
				$lektor->mitarbeiter_uid === $item->mitarbeiter_uid &&
				$lektor->fullname === $item->fullname &&
				$lektor->lehrfunktion_kurzbz === $item->lehrfunktion_kurzbz
			) {
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
	 * Group Gruppen uniquely by Kurzbzlang, Semester, Verband, and Gruppe within each Lehreinheit group.
	 *
	 * @param array $grouped	Array of data grouped by Lehreinheit ID
	 * @param object $item		Current data item containing gruppen information
	 * @return mixed
	 */
	public function groupGruppen($grouped, $item)
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
	public function generateAndSendCodeForStudent($lvevaluierung_id, $student)
	{
		$this->_ci->db->trans_begin();

		$code = $this->_ci->LvevaluierungCodeModel->getUniqueCode();
		$url  = APP_ROOT . 'index.ci.php/extensions/FHC-Core-Evaluierung/Evaluierung?code=' . urlencode($code);

		$mailData = [
			'vorname'         => $student->vorname,
			'nachname'        => $student->nachname,
			'evaluierunglink' => $url,
		];

		$mailSent = sendSanchoMail(
			'Lvevaluierung_Mail_Codeversand',
			$mailData,
			$student->uid . '@' . DOMAIN,
			'Evaluieren Sie jetzt Ihre Lehrveranstaltung'
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
}