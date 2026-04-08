<?php

class LvevaluierungReflexion_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_reflexion';
		$this->pk = 'lvevaluierung_reflexion_id';
	}

	/**
	 * Check if Reflexionszeitraum is abgeschlossen for given LVE ID.
	 *
	 * @param $lvevaluierung_id
	 * @return mixed
	 */
	public function isReflexionszeitraumAbgeschlossenForLve($lvevaluierung_id)
	{
		$this->load->config('extensions/FHC-Core-Evaluierung/initiierung');

		$qry = "
			SELECT 
			    1
			FROM 
			    extension.tbl_lvevaluierung lve
			WHERE 
			    lve.lvevaluierung_id = ?
				AND lve.codes_gemailt
			    AND lve.endezeit IS NOT NULL
				AND DATE(lve.endezeit) + COALESCE(CAST(? AS INTERVAL), INTERVAL '0 day') < CURRENT_DATE
		";

		$result = $this->execQuery(
			$qry,
			[
				$lvevaluierung_id,
				$this->config->item('reflexionZeitfensterDauer')
			]
		);

		return hasData($result);
	}

	/**
	 * Check if Reflexionszeitraum is abgeschlossen for all Evalueriungen in given LveLv ID.
	 *
	 * @param $lvevaluierung_lehrveranstaltung_id
	 * @return mixed
	 */
	public function isReflexionszeitraumAbgeschlossenForAllLvesInLveLv($lvevaluierung_lehrveranstaltung_id)
	{
		$this->load->config('extensions/FHC-Core-Evaluierung/initiierung');

		$qry = "
        	SELECT 1
        		-- Nur wenn Evaluierungen existieren...
				WHERE EXISTS (
					SELECT 1 
					FROM extension.tbl_lvevaluierung lve2
					WHERE lve2.lvevaluierung_lehrveranstaltung_id = ?
				)
				-- ...und keine mehr im Reflexionszeitraum sind
				AND NOT EXISTS (
					SELECT 1
						FROM extension.tbl_lvevaluierung lve
						JOIN extension.tbl_lvevaluierung_lehrveranstaltung lvelv USING (lvevaluierung_lehrveranstaltung_id)
					WHERE 
						lvelv.lvevaluierung_lehrveranstaltung_id = ?
						AND lve.codes_gemailt
						AND lve.endezeit IS NOT NULL
						-- Evaluierung ist noch im Reflexionszeitraum
						AND DATE(lve.endezeit) + COALESCE(CAST(? AS INTERVAL), INTERVAL '0 day') >= CURRENT_DATE
				)
			";

		// true wenn bei allen Evaluierungen der Reflexionszeitraum abgeschlossen ist.
		// false wenn bei mindestens einer Evaluierung der Reflexionszeitraum nicht abgeschlossen ist
		$result = $this->execQuery(
			$qry,
			[
				$lvevaluierung_lehrveranstaltung_id,
				$lvevaluierung_lehrveranstaltung_id,
				$this->config->item('reflexionZeitfensterDauer')
			]
		);

		return hasData($result);
	}
}
