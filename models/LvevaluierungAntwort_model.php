<?php

class LvevaluierungAntwort_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_antwort';
		$this->pk = 'lvevaluierung_antwort_id';

		$this->load->library('extensions/FHC-Core-Evaluierung/EvaluierungLib');
	}

	/**
	 * Save multiple Antworten.
	 *
	 * @param $antworten
	 * @return mixed
	 */
	public function saveAntworten($antworten)
	{
		if (is_null($this->dbTable)) return error('The given database table name is not valid', EXIT_MODEL);

		$this->db->trans_start();
		$insertedIds = [];

		// Insert data
		foreach ($antworten as $antwort) {

			$result = $this->insert($antwort);

			if (!hasData($result))
			{
				$this->db->trans_rollback();
				return error('Insert failed for item: ' . json_encode($antwort), EXIT_DATABASE);
			}

			// Store inserted ID
			$insertedIds[] = getData($result);
		}

		$this->db->trans_complete();

		if ($this->db->trans_status() === false)
		{
			$this->db->trans_rollback();
			return error($this->db->error(), EXIT_DATABASE);
		}

		return success($insertedIds);
	}

	/**
	 * Get all non-empty Textantworten for a given LVE ID.
	 *
	 * @param $lvevaluierung_id
	 * @return mixed
	 */
	public function getTextantwortenByLve($lvevaluierung_id)
	{
		$langIndex = $this->evaluierunglib->getLanguageIndex();

		$qry = '
			SELECT
				lve.lvevaluierung_id,
				lvec.lvevaluierung_code_id,
				fbfr.lvevaluierung_frage_id,
				fbfr.bezeichnung [('. $langIndex. ')] AS "fbFrageBezeichnung",
				fbfr.sort AS "fbFrageSort",
				lveantw.lvevaluierung_antwort_id,
				lveantw.antwort
			FROM
				extension.tbl_lvevaluierung lve
				JOIN extension.tbl_lvevaluierung_code lvec USING (lvevaluierung_id)
				JOIN extension.tbl_lvevaluierung_antwort lveantw USING (lvevaluierung_code_id)
				JOIN extension.tbl_lvevaluierung_fragebogen_frage fbfr USING (lvevaluierung_frage_id)
			WHERE
				lvec.lvevaluierung_id = ?
				AND lvec.endezeit IS NOT NULL
				AND fbfr.typ = \'text\'
				AND lveantw.antwort IS NOT NULL
			ORDER BY
				lve.lvevaluierung_id,
				fbfr.sort
		';

		return $this->execQuery($qry, [$lvevaluierung_id]);
	}

	/**
	 * Get all non-empty Textantworten for a given LVE-LV ID.
	 * @param $lvevaluierung_lehrveranstaltung_id
	 * @return mixed
	 */
	public function getTextantwortenByLveLv($lvevaluierung_lehrveranstaltung_id)
	{
		$langIndex = $this->evaluierunglib->getLanguageIndex();

		$qry = '
			SELECT
				lve.lvevaluierung_id,
				lvec.lvevaluierung_code_id,
				fbfr.lvevaluierung_frage_id,
				fbfr.bezeichnung [('. $langIndex. ')] AS "fbFrageBezeichnung",
				fbfr.sort AS "fbFrageSort",
				lveantw.lvevaluierung_antwort_id,
				lveantw.antwort
			FROM
				extension.tbl_lvevaluierung lve
				JOIN extension.tbl_lvevaluierung_code lvec USING (lvevaluierung_id)
				JOIN extension.tbl_lvevaluierung_antwort lveantw USING (lvevaluierung_code_id)
				JOIN extension.tbl_lvevaluierung_fragebogen_frage fbfr USING (lvevaluierung_frage_id)
			WHERE
				lvec.lvevaluierung_id IN (
				  	SELECT 
				  		lvevaluierung_id 
				  	FROM 
				  		extension.tbl_lvevaluierung
				  	WHERE
				  		lvevaluierung_lehrveranstaltung_id = ?
			  	)
				AND lvec.endezeit IS NOT NULL
				AND fbfr.typ = \'text\'
				AND lveantw.antwort IS NOT NULL
			ORDER BY
				fbfr.sort
		';

		return $this->execQuery($qry, [$lvevaluierung_lehrveranstaltung_id]);
	}
}
