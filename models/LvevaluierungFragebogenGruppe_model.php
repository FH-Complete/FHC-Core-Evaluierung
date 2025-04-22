<?php

class LvevaluierungFragebogenGruppe_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_fragebogen_gruppe';
		$this->pk = 'lvevaluierung_fragebogen_gruppe_id';
	}
}
