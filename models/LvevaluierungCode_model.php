<?php

class LvevaluierungCode_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_code';
		$this->pk = 'lvevaluierung_code_id';
	}

	/**
	 * Returns a unique Code.
	 *
	 * @return code
	 */
	public function getUniqueCode()
	{
		$found = false;

		while(!$found)
		{
			$possibleChars = "123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ";
			$code = '';

			for($i = 0; $i < 11; $i++)
			{
				$rand = rand(0, strlen($possibleChars) - 1);
				$code .= substr($possibleChars, $rand, 1);
			}

			if ($this->exists($code) === false)
			{
				$found = true;
			}
		}

		return $code;
	}

	/**
	 * Checks if code already exists.
	 *
	 * @param $code Code
	 * @return code|false False if not found, Code if found.
	 */
	public function exists($code)
	{
		$result = $this->loadWhere([
			'code' => $code
		]);

		return hasData($result) ? getData($result)[0] : false;
	}

	/**
	 * Get abgeschlossene Evaluierungen by LVE ID.
	 *
	 * @param $lvevaluierung_id
	 * @return mixed
	 */
	public function getAbgeschlosseneEvaluierungenByLve($lvevaluierung_id)
	{
		$qry = "
			SELECT 
			    *
			FROM 
			    extension.tbl_lvevaluierung_code lvec
			WHERE 
			    lvec.lvevaluierung_id = ?
				AND lvec.endezeit IS NOT NULL	
		";

		return $this->execQuery($qry, [$lvevaluierung_id]);
	}

	/**
	 * Get abgeschlossene Evaluierungen by LVE-LV ID.
	 *
	 * @param $lvevaluierung_lehrveranstaltung_id
	 * @return mixed
	 */
	public function getAbgeschlosseneEvaluierungenByLveLv($lvevaluierung_lehrveranstaltung_id)
	{
		$qry = "
			SELECT 
			    *
			FROM 
			    extension.tbl_lvevaluierung_code lvec
			WHERE 
			     lvec.lvevaluierung_id IN (
					SELECT lvevaluierung_id
					FROM extension.tbl_lvevaluierung
					JOIN extension.tbl_lvevaluierung_lehrveranstaltung lvelv USING (lvevaluierung_lehrveranstaltung_id)
					WHERE lvelv.lvevaluierung_lehrveranstaltung_id = ?
				) 
				AND lvec.endezeit IS NOT NULL	
		";

		return $this->execQuery($qry, [$lvevaluierung_lehrveranstaltung_id]);
	}

	/**
	 * Get aggregated Ruecklauf statistics (ausgegebene codes, beendete fragebögen, ruecklaufquote) for one or more LV evaluations.
	 * count_submitted_lvevaluierung: 	ausgegebene codes
	 * sum_codes_ausgegeben:			tatsächlich verschickte und beendete Fragebögen
	 * ruecklaufquote:					count_submitted_lvevaluierung / sum_codes_ausgegeben
	 * @param $lvevaluierung_lehrveranstaltung_ids
	 * @return mixed
	 */
	public function getAggregatedRuecklaufDataByLveLv($lvevaluierung_lehrveranstaltung_ids)
	{
		if (empty($lvevaluierung_lehrveranstaltung_ids)) return;

		$qry = "
			WITH codes_ausgegeben AS (
				SELECT
					lvevaluierung_lehrveranstaltung_id,
					SUM(codes_ausgegeben) AS sum_codes_ausgegeben
				FROM 
					extension.tbl_lvevaluierung
				GROUP BY 
					lvevaluierung_lehrveranstaltung_id
			),
			submitted_codes AS (
				SELECT
					lve.lvevaluierung_lehrveranstaltung_id,
					COUNT(lvec.lvevaluierung_code_id) AS count_submitted_codes
				FROM 
					extension.tbl_lvevaluierung lve
				LEFT JOIN extension.tbl_lvevaluierung_code lvec 
					ON lvec.lvevaluierung_id = lve.lvevaluierung_id
					AND lvec.endezeit IS NOT NULL
				GROUP BY 
					lve.lvevaluierung_lehrveranstaltung_id
			)
			
			SELECT
				lvelv.lvevaluierung_lehrveranstaltung_id,
				COALESCE(sc.count_submitted_codes, 0) AS count_submitted_codes,
				COALESCE(ca.sum_codes_ausgegeben, 0) AS sum_codes_ausgegeben,
				ROUND(
					COALESCE(sc.count_submitted_codes, 0)::numeric
					/ NULLIF(COALESCE(ca.sum_codes_ausgegeben, 0), 0) * 100,
					2
				) AS ruecklaufquote
			FROM 
				extension.tbl_lvevaluierung_lehrveranstaltung lvelv
				LEFT JOIN codes_ausgegeben ca USING (lvevaluierung_lehrveranstaltung_id)
				LEFT JOIN submitted_codes sc USING (lvevaluierung_lehrveranstaltung_id)
			WHERE 
				lvelv.lvevaluierung_lehrveranstaltung_id IN ?;
		";

		return $this->execQuery($qry, array($lvevaluierung_lehrveranstaltung_ids));
	}

	/**
	 *  Get aggregated Ruecklauf statistics (ausgegebene codes, beendete fragebögen, ruecklaufquote) for one or more Quellkurs evaluations.
	 *  count_submitted_lvevaluierung:    ausgegebene codes
	 *  sum_codes_ausgegeben:            tatsächlich verschickte und beendete Fragebögen
	 *  ruecklaufquote:                    count_submitted_lvevaluierung / sum_codes_ausgegeben
	 *
	 * @param $lehrveranstaltung_template_ids
	 * @param $studiensemester_kurzbz
	 * @return mixed
	 */
	public function getAggregatedRuecklaufDataByLvTemplateIds($lehrveranstaltung_template_ids, $studiensemester_kurzbz)
	{
		$qry = "
			WITH codes_ausgegeben AS (
				SELECT
					lv.lehrveranstaltung_template_id,
					SUM(lve.codes_ausgegeben) AS sum_codes_ausgegeben
				FROM
					lehre.tbl_lehrveranstaltung lv
					JOIN extension.tbl_lvevaluierung_lehrveranstaltung lvelv USING (lehrveranstaltung_id)
					JOIN extension.tbl_lvevaluierung lve USING (lvevaluierung_lehrveranstaltung_id)
				WHERE
					lv.lehrveranstaltung_template_id IN ?
					AND lvelv.studiensemester_kurzbz = ?
				GROUP BY
					lv.lehrveranstaltung_template_id
			),
			submitted_codes AS (
				SELECT
					lv.lehrveranstaltung_template_id,
					COUNT(lvec.lvevaluierung_code_id) AS count_submitted_codes
				FROM
					lehre.tbl_lehrveranstaltung lv
					JOIN extension.tbl_lvevaluierung_lehrveranstaltung lvelv USING (lehrveranstaltung_id)
					JOIN extension.tbl_lvevaluierung lve USING (lvevaluierung_lehrveranstaltung_id)
					LEFT JOIN extension.tbl_lvevaluierung_code lvec 
						ON lvec.lvevaluierung_id = lve.lvevaluierung_id
						AND lvec.endezeit IS NOT NULL
				WHERE
					lv.lehrveranstaltung_template_id IN ?
					AND lvelv.studiensemester_kurzbz = ?
				GROUP BY
					lv.lehrveranstaltung_template_id
			)
			SELECT
				ca.lehrveranstaltung_template_id,
				COALESCE(sc.count_submitted_codes, 0) AS count_submitted_codes,
				COALESCE(ca.sum_codes_ausgegeben, 0) AS sum_codes_ausgegeben,
				ROUND(
					COALESCE(sc.count_submitted_codes, 0)::numeric
					/ NULLIF(COALESCE(ca.sum_codes_ausgegeben, 0), 0) * 100,
					2
				) AS ruecklaufquote
			FROM 
				codes_ausgegeben ca
				LEFT JOIN submitted_codes sc USING (lehrveranstaltung_template_id);
		";

		return $this->execQuery($qry, array($lehrveranstaltung_template_ids, $studiensemester_kurzbz, $lehrveranstaltung_template_ids, $studiensemester_kurzbz));
	}
}
