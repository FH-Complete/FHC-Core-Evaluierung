<?php

class LvevaluierungAntwort_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_antwort';
		$this->pk = 'lvevaluierung_antwort_id';
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
}
