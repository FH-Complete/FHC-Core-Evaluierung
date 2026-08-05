<?php

class LvevaluierungFragebogenGruppeFrage_model extends DB_Model
{
	public function __construct()
	{
		function __construct()
		{
			parent::__construct();
			$this->dbTable = 'extension.tbl_lvevaluierung_fragebogen_gruppe_frage';
			$this->pk = ['lvevaluierung_fragebogen_gruppe_id', 'lvevaluierung_frage_id'];
			$this->hasSequence = false; // zusammengesetzter PK ohne Sequence
		}
	}
}
