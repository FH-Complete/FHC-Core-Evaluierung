<?php

class Lvevaluierung_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung';
		$this->pk = 'lvevaluierung_id';
	}
}
