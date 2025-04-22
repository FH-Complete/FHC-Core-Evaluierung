<?php

class LvevaluierungFragebogenZuordnung_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_fragebogen_zuordnung';
		$this->pk = 'lvevaluierung_fragebogen_zuordnung_id';
	}
}
