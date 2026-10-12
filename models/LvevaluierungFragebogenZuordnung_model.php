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
					stp.bezeichnung,
					oe.bezeichnung || ' (' || oe.organisationseinheittyp_kurzbz || ')'
				) AS label
			FROM extension.tbl_lvevaluierung_fragebogen_zuordnung fbzo
			LEFT JOIN public.tbl_organisationseinheit oe ON oe.oe_kurzbz = fbzo.oe_kurzbz
			LEFT JOIN lehre.tbl_studienplan stp ON stp.studienplan_id = fbzo.studienplan_id
			LEFT JOIN lehre.tbl_lehrveranstaltung lv ON lv.lehrveranstaltung_id = fbzo.lehrveranstaltung_id
			WHERE fbzo.fragebogen_id = ?
		";

		return $this->execQuery($qry, [$fragebogen_id]);
	}
}
