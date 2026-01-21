<?php

class Lvevaluierung_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung';
		$this->pk = 'lvevaluierung_id';
	}

	/**
	 * Insert new Lvevaluierung.
	 *
	 * @param $lvevaluierung
	 * @return mixed
	 */
	public function insertLvevaluierung($lvevaluierung)
	{
		$lvevaluierung['insertvon'] = getAuthUID();
		$lvevaluierung['codes_gemailt'] = isset($lvevaluierung['codes_gemailt'])
			? $lvevaluierung['codes_gemailt']
			: false;

		$result = $this->insert($lvevaluierung);

		if (isError($result))
		{
			return error($result->msg, EXIT_ERROR);
		}

		$record = $this->load($result->retval);

		return $record;
	}

	/**
	 * Updates Lvevaluierung.
	 *
	 * @param $lvevaluierung
	 * @return mixed
	 */
	public function updateLvevaluierung($lvevaluierung)
	{
		$lvevaluierung['updatevon'] = getAuthUID();
		$lvevaluierung['updateamum'] = $this->escape('NOW()');

		$result = $this->update($lvevaluierung['lvevaluierung_id'], $lvevaluierung);

		if (isError($result))
		{
			return error($result->msg, EXIT_ERROR);
		}

		return $this->load($lvevaluierung['lvevaluierung_id']);
	}
}
