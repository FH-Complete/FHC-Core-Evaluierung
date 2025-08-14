<?php

class LvevaluierungFragebogen_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_fragebogen';
		$this->pk = 'fragebogen_id';
	}

	/**
	 * Get active Fragebogen by:
	 *  - First Studienplan of Lv and Studiensemester is retrieved by given Lehrveranstaltung-ID and Studiensemester.
	 *  - Second all Fragebogen are retrieved that are assigned to that Studienplan.
	 *  - Get Fragebogen where given Evaluation-Startzeit is within Gültigkeit-period.
	 *
	 * @param $lehrveranstaltung_id
	 * @param $studiensemester_kurzbz
	 * @param $lvevaluierung_startzeit
	 * @return mixed
	 */
	public function getActiveFragebogen($lehrveranstaltung_id, $studiensemester_kurzbz, $lvevaluierung_startzeit)
	{
		$params = [$lehrveranstaltung_id, $lehrveranstaltung_id, $studiensemester_kurzbz, $lvevaluierung_startzeit];

		$qry = "
			-- Get active Studienplan of given Lehrveranstaltung and Studiensemester 
			WITH studienplan AS ( 
				SELECT 
					DISTINCT ON (tbl_studienplan.studienplan_id)
    				tbl_studienplan.studienplan_id
				FROM
					lehre.tbl_studienplan
					JOIN lehre.tbl_studienplan_lehrveranstaltung USING (studienplan_id)
				WHERE
					tbl_studienplan_lehrveranstaltung.lehrveranstaltung_id IN (
						SELECT 
							lv.lehrveranstaltung_id
						FROM
							lehre.tbl_lehrveranstaltung AS lv
							LEFT JOIN lehre.tbl_lehrveranstaltung AS t 
								ON t.lehrveranstaltung_id = lv.lehrveranstaltung_template_id
						WHERE
							lv.lehrtyp_kurzbz <> 'tpl'
							AND (
								lv.lehrveranstaltung_id = ? 
								OR (
									lv.lehrveranstaltung_template_id = ? 
									AND t.lehrtyp_kurzbz = 'tpl'
								)
							)
					)
					AND EXISTS (
						SELECT 1 
						FROM 
							lehre.tbl_studienplan_semester
						WHERE 
							studienplan_id = tbl_studienplan.studienplan_id
							AND studiensemester_kurzbz = ?
							AND semester = tbl_studienplan_lehrveranstaltung.semester
					)
				ORDER BY tbl_studienplan.studienplan_id
			)

		-- Get Fragebogen assigned to active Studienplan and where given Startdatum of Evaulation 
		-- is within evaluation period
		SELECT
		 	fragebogen_id
		FROM 
			studienplan
			JOIN extension.tbl_lvevaluierung_fragebogen_zuordnung USING (studienplan_id)
			JOIN extension.tbl_lvevaluierung_fragebogen fb using (fragebogen_id)
		WHERE 
    		DATE(?) BETWEEN fb.gueltig_von AND fb.gueltig_bis
		";

		return $this->execQuery($qry, $params);
	}
}
