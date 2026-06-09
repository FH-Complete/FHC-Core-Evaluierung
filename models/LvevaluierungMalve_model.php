<?php

class LvevaluierungMalve_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_malve';
		$this->pk = 'malve_id';
	}
}
