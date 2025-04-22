<?php

class LvevaluierungFragebogenFrage_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_fragebogen_frage';
		$this->pk = 'lvevaluierung_frage_id';
	}
}
