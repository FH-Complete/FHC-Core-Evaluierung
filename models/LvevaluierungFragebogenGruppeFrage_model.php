<?php

class LvevaluierungFragebogenGruppeFrage_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_fragebogen_gruppe_frage';
		$this->pk = ['lvevaluierung_fragebogen_gruppe_id', 'lvevaluierung_frage_id'];
		$this->hasSequence = false; // zusammengesetzter PK ohne Sequence
	}

	/**
	 * Fragen einer Gruppe auch der neuen Gruppe zuordnen.
	 * Fragen und Antworten behalten ihre ID (geteilt, n:m).
	 *
	 * @param $von_gruppe_id
	 * @param $nach_gruppe_id
	 * @return mixed
	 */
	public function copyFragen($von_gruppe_id, $nach_gruppe_id)
	{
		$qry = '
			INSERT INTO extension.tbl_lvevaluierung_fragebogen_gruppe_frage (
				lvevaluierung_fragebogen_gruppe_id,
				lvevaluierung_frage_id
			)
			SELECT ?, lvevaluierung_frage_id
			FROM extension.tbl_lvevaluierung_fragebogen_gruppe_frage
			WHERE lvevaluierung_fragebogen_gruppe_id = ?
		';

		return $this->execQuery($qry, [$nach_gruppe_id, $von_gruppe_id]);
	}
}
