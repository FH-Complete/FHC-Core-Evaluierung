<?php

class LvevaluierungLehrveranstaltung_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_lehrveranstaltung';
		$this->pk = 'lvevaluierung_lehrveranstaltung_id';
	}
}
