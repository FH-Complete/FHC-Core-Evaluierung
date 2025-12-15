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
	public function getLveLvsByUser($studiensemester_kurzbz, $lehrveranstaltung_id = null)
	{
		$uid = getAuthUid();
		$params = [$studiensemester_kurzbz, $uid, $uid];

		$qry = '
			-- Alle LVs eines bestimmten Studiensemesters, wo eingeloggter user Lektor ist
			WITH lvs AS (
				SELECT
					DISTINCT ON (lv.lehrveranstaltung_id) 
					lv.lehrveranstaltung_id,
					lv.bezeichnung,
					lv.orgform_kurzbz,
					lv.semester,
					lv.studiengang_kz,
					lema.mitarbeiter_uid,
					lema.lehrfunktion_kurzbz,
					lema.lehreinheit_id,
					stg.kurzbzlang,
					le.studiensemester_kurzbz
				FROM
					lehre.tbl_lehrveranstaltung lv
					JOIN lehre.tbl_lehreinheit le USING (lehrveranstaltung_id)
					JOIN lehre.tbl_lehreinheitmitarbeiter lema USING (lehreinheit_id)
					JOIN public.tbl_studiengang stg USING (studiengang_kz)
				WHERE
					le.studiensemester_kurzbz = ?
					AND lema.mitarbeiter_uid = ?
				ORDER BY
					lv.lehrveranstaltung_id,
					(lema.mitarbeiter_uid = ?) DESC
			)
	
			-- Final join
			SELECT
				*
			FROM		  	
				lvs	
				JOIN extension.tbl_lvevaluierung_lehrveranstaltung lvelv 
					ON lvelv.lehrveranstaltung_id = lvs.lehrveranstaltung_id 
					AND lvelv.studiensemester_kurzbz = lvs.studiensemester_kurzbz
			WHERE TRUE
		';

		if (!is_null($lehrveranstaltung_id))
		{
			$qry .= ' AND lvelv.lehrveranstaltung_id = ?';
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
	 * Get Lvs that are scheduled for evaluation in the given Studiensemester and Studiengang (can be number or array).
	 *
	 * @param $studiensemester_kurzbz
	 * @param $studiengang_kz
	 * @return mixed
	 */
	public function getLveLvsByStg($studiensemester_kurzbz, $studiengang_kz, $orgform_kurzbz)
	{
		if (is_numeric($studiengang_kz) && !is_array($studiengang_kz))
		{
			$studiengang_kz = [$studiengang_kz];
		}
		$params = [$studiensemester_kurzbz, $studiengang_kz, $orgform_kurzbz];

		$qry = '
			-- Alle LVs eines bestimmten Studiensemesters, wo eingeloggter user Lektor ist
			WITH lvs AS (
				SELECT
					DISTINCT ON (lv.lehrveranstaltung_id) 
					lv.lehrveranstaltung_id,
					lv.bezeichnung,
					lv.orgform_kurzbz,
					lv.semester,
					lv.studiengang_kz,
					lv.lehrveranstaltung_template_id,
					stg.kurzbzlang,
					le.studiensemester_kurzbz
				FROM
					lehre.tbl_lehrveranstaltung lv
					JOIN lehre.tbl_lehreinheit le USING (lehrveranstaltung_id)
					JOIN public.tbl_studiengang stg USING (studiengang_kz)
				WHERE
					le.studiensemester_kurzbz = ?
					AND stg.studiengang_kz IN ?
					AND lv.orgform_kurzbz = ?
				ORDER BY
					lv.lehrveranstaltung_id
			)
	
			-- Final join
			SELECT
				*
			FROM		  	
				lvs	
			JOIN extension.tbl_lvevaluierung_lehrveranstaltung lvelv 
				ON lvelv.lehrveranstaltung_id = lvs.lehrveranstaltung_id 
				AND lvelv.studiensemester_kurzbz = lvs.studiensemester_kurzbz
			ORDER BY
			  bezeichnung,
			  orgform_kurzbz
		';

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
			),
			lve AS (
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
					p.vorname,
					p.nachname,
					legr.semester,
					legr.verband,
					legr.gruppe,
					legr.gruppe_kurzbz,
					gr.direktinskription,
					CASE
						-- normale Gruppe
						WHEN legr.gruppe_kurzbz IS NULL THEN
							COALESCE(
								CONCAT(
									UPPER(CONCAT(stg.typ, stg.kurzbz, \'-\')),
									COALESCE(legr.semester::varchar, \'\'),
									COALESCE(legr.verband::varchar, \'\'),
									COALESCE(legr.gruppe, \'\')
								),
							 \'\'
						)
						-- Spezialgruppe
						ELSE legr.gruppe_kurzbz
					END AS gruppe_bezeichnung,
					lv.kurzbz,
					stg.kurzbzlang
				FROM 
					lehre.tbl_lehreinheit le 
					JOIN lehre.tbl_lehrveranstaltung lv USING (lehrveranstaltung_id)
					JOIN lehre.tbl_lehreinheitmitarbeiter lema USING (lehreinheit_id)
					JOIN public.tbl_benutzer b ON b.uid = lema.mitarbeiter_uid
					JOIN public.tbl_person p USING (person_id)
					LEFT JOIN lehre.tbl_lehreinheitgruppe legr USING (lehreinheit_id)
					LEFT JOIN public.tbl_gruppe gr USING (gruppe_kurzbz)
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

			$params[]= $uid;

			$qry .= '
			-- End CTE (lve)
			)
			
			SELECT 
				* 
			FROM 
				lve
			ORDER BY
			 	CASE WHEN mitarbeiter_uid = ? THEN 0 ELSE 1 END,
				nachname
			';

		return $this->execQuery($qry, $params);
	}

	/**
	 *  Insert Lehrveranstaltungen for a particular Studiensemester into the tbl_lvevaluierung_lehrveranstaltung.
	 *  Only Lehrveranstaltungen that are marked for evaluation and not yet present in target table will be inserted.
	 *
	 * @param $studiensemester_kurzbz
	 * @return mixed
	 */
	public function insertLehrveranstaltungenFor($studiensemester_kurzbz)
	{
		$qry = "
			SELECT
				DISTINCT 
			    	lv.lehrveranstaltung_id, 
					le.studiensemester_kurzbz,
					TRUE AS verpflichtend,
					FALSE AS lv_aufgeteilt
			FROM
				lehre.tbl_lehrveranstaltung lv
				join lehre.tbl_lehreinheit le using (lehrveranstaltung_id)
			WHERE
				-- filter by Studiensemester
				le.studiensemester_kurzbz = ?
			  	-- filter only to be evaluated
				AND lv.evaluierung = TRUE
			  	-- filter only not already inserted
				AND lv.lehrveranstaltung_id NOT IN (
				  SELECT
					lehrveranstaltung_id
				  FROM
					extension.tbl_lvevaluierung_lehrveranstaltung
				  WHERE
					studiensemester_kurzbz = ?
				)
		  	ORDER BY
				lehrveranstaltung_id
		";
		$result = $this->execQuery($qry, [$studiensemester_kurzbz, $studiensemester_kurzbz]);
		if (isError($result)) return (getError($result));

		$insertBatch = hasData($result) ? getData($result) : [];

		if (empty($insertBatch))
		{
			return success('No new Lehrveranstaltungen to add for this Studiensemester');
		}

		return $this->insertBatch($insertBatch);
	}

	public function insertBatch($batch)
	{
		// Check class properties
		if (is_null($this->dbTable)) return error('The given database table name is not valid', EXIT_MODEL);

		// Insert data
		$insert = $this->db->insert_batch($this->dbTable, $batch);

		if ($insert)
		{
			return success('Lehrveranstaltungen inserted successfully');
		}
		else
		{
			return error($this->db->error(), EXIT_DATABASE);
		}
	}
}
