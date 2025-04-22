<?php

class LvevaluierungFragebogenFrageAntwort_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_fragebogen_frage_antwort';
		$this->pk = 'lvevaluierung_frage_antwort_id';
	}
}
