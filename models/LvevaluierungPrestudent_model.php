<?php

class LvevaluierungPrestudent_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_prestudent';
		$this->pk = 'lvevaluierung_prestudent_id';
	}
}
