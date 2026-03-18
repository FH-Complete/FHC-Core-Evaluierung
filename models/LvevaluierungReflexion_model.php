<?php

class LvevaluierungReflexion_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_reflexion';
		$this->pk = 'lvevaluierung_reflexion_id';
	}
}
