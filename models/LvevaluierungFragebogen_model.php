<?php

class LvevaluierungFragebogen_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_fragebogen';
		$this->pk = 'fragebogen_id';
	}
}
