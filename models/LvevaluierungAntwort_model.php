<?php

class LvevaluierungAntwort_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_antwort';
		$this->pk = 'lvevaluierung_antwort_id';
	}
}
