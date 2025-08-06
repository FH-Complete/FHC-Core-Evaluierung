<?php

class LvevaluierungLehrveranstaltung_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_lehrveranstaltung';
		$this->pk = 'lvevaluierung_lehrveranstaltung_id';

		$this->load->config('extensions/FHC-Core-Evaluierung/initiierung');
	}

	/**
	 * Get Lvs that are scheduled for evaluation in the given Studiensemester, where the logged-in user is assigned
	 * to at least one Lehreinheit as a Lektor.
	 *
	 * @param string	$studiensemester_kurzbz
	 * @param int|null	$lehrveranstaltung_id	Optional: filter for a specific LV
	 * @return mixed
	 */
	public function getLveLvs($studiensemester_kurzbz, $lehrveranstaltung_id = null)
	{
		$uid = getAuthUid();
		$params = [$studiensemester_kurzbz, $uid, $uid];

		$qry = '
			-- Alle LVs eines bestimmten Studiensemesters, wo eingeloggter user Lektor ist
			-- mit Anzeige ob LV-Leitung ist oder Lektor
			WITH lvs_lvLevel AS (
				SELECT
				  *
				FROM (
					SELECT
						DISTINCT ON (lehrveranstaltung_id) 
						lv.lehrveranstaltung_id,
						lv.bezeichnung,
						lv.orgform_kurzbz,
						lv.semester,
						lv.studiengang_kz,
						lema.mitarbeiter_uid,
						lema.lehrfunktion_kurzbz
					FROM
						lehre.tbl_lehrveranstaltung lv
						join lehre.tbl_lehreinheit le using (lehrveranstaltung_id)
						join lehre.tbl_lehreinheitmitarbeiter lema using (lehreinheit_id)
					WHERE
						-- filter studiensemester
						le.studiensemester_kurzbz = ?
						-- filter lvs, wo eingeloggter user unterrichtet (und behalte auch eventuelle andere lektoren dieser LV)
						AND EXISTS (
							SELECT
								1
							FROM
								lehre.tbl_lehreinheit le2
							JOIN lehre.tbl_lehreinheitmitarbeiter lema2 USING (lehreinheit_id)
							WHERE
								le2.lehrveranstaltung_id = lv.lehrveranstaltung_id
								AND lema2.mitarbeiter_uid = ?
					)
					ORDER BY
						lv.lehrveranstaltung_id,
						-- order by uid, damit distinct die row mit dem eingeloggten user behält
						(lema.mitarbeiter_uid = ?) DESC
					) as subQuery
			)
	
			-- Final join
			SELECT
				*
			FROM		  	
				extension.tbl_lvevaluierung_lehrveranstaltung	
				JOIN lvs_lvLevel USING (lehrveranstaltung_id)
			WHERE TRUE
		';

		if (!is_null($lehrveranstaltung_id))
		{
			$qry .= ' AND lehrveranstaltung_id = ?';
			$params[]= $lehrveranstaltung_id;
		}

		$qry.= '
			ORDER BY
			  bezeichnung,
			  orgform_kurzbz
		';

		return $this->execQuery($qry, $params);
	}

	/**
	 * Get Lvs, that are scheduled for evaluation, with their associated Lehreinheiten for the given Studiensemester,
	 * where the logged-in user is assigned to at least one Lehreinheit as a Lektor.
	 *
	 * Optionally excluded Lehrformen based on config value.
	 *
	 * @param string $studiensemester_kurzbz
	 * @param int|null $lehrveranstaltung_id	Optional: filter for a specific LV by ID
	 * @return mixed
	 */
	public function getLveLvsWithLes($studiensemester_kurzbz, $lehrveranstaltung_id = null)
	{
		$excludedLehrformen = $this->config->item('excludedLehrformen');

		$uid = getAuthUid();
		$params = [$studiensemester_kurzbz, $uid];

		$qry = '
			-- Alle LVs eines bestimmten Studiensemesters, wo eingeloggter user Lektor ist
			-- mit Anzeige ob LV-Leitung ist oder Lektor
			WITH lvs_leLevel AS (
				SELECT
					lv.lehrveranstaltung_id,
					le.lehreinheit_id,
					le.lehrform_kurzbz,
					lv.bezeichnung,
					lv.orgform_kurzbz,
					lv.semester,
					lv.studiengang_kz,
					lema.mitarbeiter_uid,
					lema.lehrfunktion_kurzbz,
					concat(p.vorname, \' \', p.nachname) AS fullname
				FROM
					lehre.tbl_lehrveranstaltung lv
					join lehre.tbl_lehreinheit le using (lehrveranstaltung_id)
					join lehre.tbl_lehreinheitmitarbeiter lema using (lehreinheit_id)
					join public.tbl_benutzer b ON b.uid = lema.mitarbeiter_uid
					join public.tbl_person p USING (person_id)
				WHERE
					-- filter studiensemester
					le.studiensemester_kurzbz = ?
					-- filter lvs, wo eingeloggter user unterrichtet (und behalte auch eventuelle andere lektoren dieser LV)
					AND EXISTS (
						SELECT
							1
						FROM
							lehre.tbl_lehreinheit le2
						JOIN lehre.tbl_lehreinheitmitarbeiter lema2 USING (lehreinheit_id)
						WHERE
							le2.lehrveranstaltung_id = lv.lehrveranstaltung_id
							AND lema2.mitarbeiter_uid = ?
				)
			)
		
			-- Final join
			SELECT
				*
			FROM		  	
				extension.tbl_lvevaluierung_lehrveranstaltung	
				JOIN lvs_leLevel USING (lehrveranstaltung_id)
			WHERE TRUE
		';

		if (!is_null($lehrveranstaltung_id))
		{
			$qry .= ' AND lehrveranstaltung_id = ?';
			$params[]= $lehrveranstaltung_id;
		}

		if (is_array($excludedLehrformen) && count($excludedLehrformen) > 0)
		{
			$qry .= ' AND lehrform_kurzbz NOT IN ? ';
			$params[]= $excludedLehrformen;
		}

		$qry.= '
			ORDER BY
				bezeichnung,
				orgform_kurzbz,
				-- order user first
				(mitarbeiter_uid = ?) DESC
		';
		$params[] = $uid;

		return $this->execQuery($qry, $params);
	}

	/**
	 * Get Lv to be evaluated of given ID, including its Lehreinheiten, associated Gruppen and Lektoren, where the
	 * logged-in user is assigned to at least one Lehreinheit as a Lektor.
	 *
	 * @param $lvevaluierung_lehrveranstaltung_id
	 * @return mixed
	 */
	public function getLveLvWithLesAndGruppenById($lvevaluierung_lehrveranstaltung_id)
	{
		$excludedLehrformen = $this->config->item('excludedLehrformen');

		$uid = getAuthUid();
		$params = [$lvevaluierung_lehrveranstaltung_id, $uid];

		$qry = '
			WITH lvevaluierung_lehrveranstaltung AS (
			  	SELECT
					*
			  	FROM
					extension.tbl_lvevaluierung_lehrveranstaltung
			  	WHERE
					lvevaluierung_lehrveranstaltung_id = ?
			)
	
			SELECT DISTINCT
				lvevaluierung_lehrveranstaltung.*, 
				lv.lehrveranstaltung_id,
				le.lehreinheit_id,
				le.lehrform_kurzbz,
				lv.bezeichnung,
				lv.orgform_kurzbz,
				lv.semester,
				lv.studiengang_kz,
				lema.mitarbeiter_uid,
				lema.lehrfunktion_kurzbz,
				concat(p.vorname, \' \', p.nachname) AS fullname,
				legr.semester,
				legr.verband,
				legr.gruppe,
				legr.gruppe_kurzbz,
				lv.kurzbz,
				stg.kurzbzlang,
				(
					SELECT 
						COUNT(*)
					FROM
						campus.vw_student_lehrveranstaltung
					WHERE 
						lehreinheit_id = le.lehreinheit_id
				) as studentcount
			FROM 
				lehre.tbl_lehreinheit le 
				JOIN lehre.tbl_lehrveranstaltung lv USING (lehrveranstaltung_id)
				JOIN lehre.tbl_lehreinheitmitarbeiter lema USING (lehreinheit_id)
				JOIN public.tbl_benutzer b ON b.uid = lema.mitarbeiter_uid
				JOIN public.tbl_person p USING (person_id)
				LEFT JOIN lehre.tbl_lehreinheitgruppe legr USING (lehreinheit_id)
				LEFT JOIN public.tbl_studiengang stg ON (legr.studiengang_kz = stg.studiengang_kz)
				JOIN lvevaluierung_lehrveranstaltung 
					ON le.lehrveranstaltung_id = lvevaluierung_lehrveranstaltung.lehrveranstaltung_id
					AND le.studiensemester_kurzbz = lvevaluierung_lehrveranstaltung.studiensemester_kurzbz
			WHERE
					EXISTS (
						SELECT
							1
						FROM
							lehre.tbl_lehreinheit le2
						JOIN lehre.tbl_lehreinheitmitarbeiter lema2 USING (lehreinheit_id)
						WHERE
							le2.lehrveranstaltung_id = lv.lehrveranstaltung_id
							AND lema2.mitarbeiter_uid = ?
				)';

			if (is_array($excludedLehrformen) && count($excludedLehrformen) > 0)
			{
				$qry .= ' AND le.lehrform_kurzbz NOT IN ? ';
				$params[]= $excludedLehrformen;
			}

			$qry .= '
				ORDER BY
					legr.gruppe_kurzbz;
			';

		return $this->execQuery($qry, $params);
	}
}
