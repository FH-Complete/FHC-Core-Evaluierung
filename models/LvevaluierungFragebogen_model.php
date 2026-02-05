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
	 * Get active valid Fragebogen for a Lehrveranstaltung, resolved by priority (LV → Studienplan → OE → parent OEs)
	 * within the given Studiensemester.
	 * Ad Validation: Fragebogen Gültig-von Startdate is within time period of Studiensemester.
	 * Ad Priority: Fragebogen Zuordnung can be e.g. for OE 'etw', but individual Fragebogen for OE 'fakLifeScience' or
	 * for OE 'bbe' or for Studienplan or for Lehrveranstaltung itself. That way, a LV could be assigned to more
	 * Fragebogen and therefor we prioritize which one to use.
	 *
	 * @param $lehrveranstaltung_id
	 * @param $studiensemester_kurzbz
	 * @return mixed	Returns the Fragebogen with the highest priority.
	 */
	public function getActiveFragebogen($lehrveranstaltung_id, $studiensemester_kurzbz)
	{
		$params = [
			$studiensemester_kurzbz,
			$lehrveranstaltung_id,
			$lehrveranstaltung_id,
			$studiensemester_kurzbz,
			$lehrveranstaltung_id
		];

		$qry = "		
		WITH 
			-- Get parent OEs of LV OE by LV ID
			RECURSIVE alle_oes(oe_kurzbz, oe_parent_kurzbz, prio) AS
			(
				-- Base case: start from the OE of the LV
				SELECT o.oe_kurzbz, o.oe_parent_kurzbz, 500
				FROM public.tbl_organisationseinheit o
				JOIN lv_oe lvoe ON o.oe_kurzbz = lvoe.oe_kurzbz
				WHERE o.aktiv = true
				
				UNION ALL
				
				-- Recursive step: walk up the parent chain
				SELECT o.oe_kurzbz, o.oe_parent_kurzbz, a.prio + 1
				FROM public.tbl_organisationseinheit o
				JOIN alle_oes a ON o.oe_kurzbz = a.oe_parent_kurzbz
				WHERE o.aktiv = true
			),
			-- Get active Studienplan by LV ID and Studiensemester 
			studienplan AS ( 
				SELECT stpl.studienplan_id
				FROM lehre.tbl_studienplan stpl
				JOIN lehre.tbl_studienordnung sto USING (studienordnung_id)
				JOIN lehre.tbl_studienplan_semester stplsem USING (studienplan_id)
				JOIN lehre.tbl_studienplan_lehrveranstaltung stpllv ON (
					stpllv.studienplan_id = stpl.studienplan_id
					AND stpllv.semester = stplsem.semester
				)
				JOIN lehre.tbl_lehrveranstaltung lv USING (lehrveranstaltung_id)
				WHERE
					stplsem.studiensemester_kurzbz = ?
					AND lv.lehrveranstaltung_id = ?
			),
			-- Get LV OE by LV ID 
			lv_oe AS (
				SELECT lv.oe_kurzbz 
				FROM lehre.tbl_lehrveranstaltung lv
				JOIN public.tbl_studiengang USING (studiengang_kz)
				WHERE lehrveranstaltung_id = ?
			),
			-- Get Studiensemester
			studiensemester AS (
				SELECT start, ende
				FROM public.tbl_studiensemester
				WHERE studiensemester_kurzbz = ?
			),
			-- Get prioritized Fragebogen
			fragebogen_matches AS (
				 -- Priority 1: Direct LV match
				SELECT 
					1 AS prio, lfz.fragebogen_id, lfz.lehrveranstaltung_id, lfz.studienplan_id, lfz.oe_kurzbz
				FROM extension.tbl_lvevaluierung_fragebogen_zuordnung lfz
				JOIN extension.tbl_lvevaluierung_fragebogen lf USING (fragebogen_id)
				WHERE lfz.lehrveranstaltung_id = ?
				AND COALESCE(lf.gueltig_bis,'2099-01-01') >= (SELECT start FROM studiensemester) 
				AND lf.gueltig_von <= (SELECT ende FROM studiensemester) 
					
				UNION ALL 
					
				-- Priority 2: Studienplan match
				SELECT 
					2 AS prio, lfz.fragebogen_id, lfz.lehrveranstaltung_id, lfz.studienplan_id, lfz.oe_kurzbz
				FROM extension.tbl_lvevaluierung_fragebogen_zuordnung lfz
				JOIN extension.tbl_lvevaluierung_fragebogen lf USING (fragebogen_id)
				WHERE lfz.studienplan_id = (SELECT studienplan_id FROM studienplan)
				AND COALESCE(lf.gueltig_bis,'2099-01-01') >= (SELECT start FROM studiensemester) 
				AND lf.gueltig_von <= (SELECT ende FROM studiensemester) 
				
				UNION ALL
					
				 -- Priority 3: Direct OE match
				SELECT 
					3 as prio, lfz.fragebogen_id, lfz.lehrveranstaltung_id, lfz.studienplan_id, lfz.oe_kurzbz
				FROM extension.tbl_lvevaluierung_fragebogen_zuordnung lfz
				JOIN extension.tbl_lvevaluierung_fragebogen lf USING (fragebogen_id)
				WHERE lfz.oe_kurzbz = (SELECT oe_kurzbz FROM lv_oe)
				AND COALESCE(lf.gueltig_bis,'2099-01-01') >= (SELECT start FROM studiensemester) 
				AND lf.gueltig_von <= (SELECT ende FROM studiensemester) 
					
				 UNION ALL
				
				-- Priority 4: Parent OEs
				SELECT 
					prio, lfz.fragebogen_id, lfz.lehrveranstaltung_id, lfz.studienplan_id, lfz.oe_kurzbz
				FROM extension.tbl_lvevaluierung_fragebogen_zuordnung lfz
				JOIN extension.tbl_lvevaluierung_fragebogen lf USING (fragebogen_id)
				JOIN alle_oes ON lfz.oe_kurzbz = alle_oes.oe_kurzbz
				WHERE 
				COALESCE(lf.gueltig_bis,'2099-01-01') >= (SELECT start FROM studiensemester) 
				AND lf.gueltig_von <= (SELECT ende FROM studiensemester) 
			)
				
			-- Final selection: Pick best prio, latest fragebogen_id if more with same prio and same/ Gültigkeitsperiode
			SELECT 
				fragebogen_id
			FROM 
				fragebogen_matches
			ORDER BY 
				prio, fragebogen_id DESC
			LIMIT 1;
		";

		return $this->execQuery($qry, $params);
	}
}
