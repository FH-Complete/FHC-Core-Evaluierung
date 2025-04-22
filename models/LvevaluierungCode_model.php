<?php

class LvevaluierungCode_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_code';
		$this->pk = 'lvevaluierung_code_id';
	}
}
