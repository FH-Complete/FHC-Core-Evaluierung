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
}