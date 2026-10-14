<?php

class LvevaluierungFragebogenZuordnung_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_fragebogen_zuordnung';
		$this->pk = 'lvevaluierung_fragebogen_zuordnung_id';
	}

	/**
	 * Zuordnungen inkl. Ebene und Anzeigetext.
	 */
	public function getByFragebogen($fragebogen_id)
	{
		$qry = "
			SELECT
				fbzo.lvevaluierung_fragebogen_zuordnung_id,
				fbzo.oe_kurzbz,
				fbzo.studienplan_id,
				fbzo.lehrveranstaltung_id,
				CASE
					WHEN fbzo.lehrveranstaltung_id IS NOT NULL THEN 'lv'
					WHEN fbzo.studienplan_id IS NOT NULL THEN 'stp'
					ELSE 'oe'
				END AS ebene,
				COALESCE(
					lv.kurzbz || ' – ' || lv.bezeichnung,
					stpl.bezeichnung,
					oe.organisationseinheittyp_kurzbz || ' ' || oe.bezeichnung
				) AS label
			FROM extension.tbl_lvevaluierung_fragebogen_zuordnung fbzo
			LEFT JOIN public.tbl_organisationseinheit oe ON oe.oe_kurzbz = fbzo.oe_kurzbz
			LEFT JOIN lehre.tbl_studienplan stpl ON stpl.studienplan_id = fbzo.studienplan_id
			LEFT JOIN lehre.tbl_lehrveranstaltung lv ON lv.lehrveranstaltung_id = fbzo.lehrveranstaltung_id
			WHERE fbzo.fragebogen_id = ?
		";

		return $this->execQuery($qry, [$fragebogen_id]);
	}

	/**
	 * LV-Suche nach Bezeichnung, Kurzbz oder LV-ID.
	 * Nur LVs, die im Studiensemester über einen Studienplan laufen und evaluiert werden.
	 */
	public function searchLehrveranstaltungen($query, $studiensemester_kurzbz)
	{
		$qry = '
			SELECT DISTINCT
				lv.lehrveranstaltung_id,
				lv.kurzbz,
				lv.bezeichnung,
				lv.semester,
				lv.orgform_kurzbz,
				stg.kurzbzlang
			FROM lehre.tbl_lehrveranstaltung lv
			JOIN public.tbl_studiengang stg USING (studiengang_kz)
			JOIN lehre.tbl_studienplan_lehrveranstaltung stpllv USING (lehrveranstaltung_id)
			JOIN lehre.tbl_studienplan stpl ON stpl.studienplan_id = stpllv.studienplan_id
			JOIN lehre.tbl_studienplan_semester stplsem ON (
				stplsem.studienplan_id = stpl.studienplan_id
				AND stplsem.semester = stpllv.semester
			)
			WHERE 
				stplsem.studiensemester_kurzbz = ?
				AND lv.lehrtyp_kurzbz = \'lv\'
				AND lv.lehre = TRUE
				AND lv.evaluierung = TRUE
				AND (
					lv.bezeichnung ILIKE ?
					OR lv.kurzbz ILIKE ?
					OR stg.kurzbzlang ILIKE ?
					OR CAST(lv.lehrveranstaltung_id AS TEXT) = ?
				)
			ORDER BY lv.bezeichnung
		';

		$like = '%' . $query . '%';

		return $this->execQuery($qry, [$studiensemester_kurzbz, $like, $like, $like, $query]);
	}
}
