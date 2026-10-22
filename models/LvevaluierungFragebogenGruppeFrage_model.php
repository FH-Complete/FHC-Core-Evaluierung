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
	 * Fragen einer Gruppe einer neuen Gruppe zuordnen (gleiche Frage-IDs).
	 * Fragen, die im Fragebogen der neuen Gruppe schon vorkommen, werden übersprungen (eine Antwort je Frage und Code).
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
			SELECT
				gr.lvevaluierung_fragebogen_gruppe_id,
				grfr.lvevaluierung_frage_id
			FROM extension.tbl_lvevaluierung_fragebogen_gruppe_frage grfr
			-- neue Gruppe
			JOIN extension.tbl_lvevaluierung_fragebogen_gruppe gr ON gr.lvevaluierung_fragebogen_gruppe_id = ?
			-- Fragen der Vorlage-Gruppe
			WHERE grfr.lvevaluierung_fragebogen_gruppe_id = ?
			-- nur Fragen, die im Fragebogen der neuen Gruppe noch nicht vorkommen
			AND NOT EXISTS (
				SELECT 1
				FROM extension.tbl_lvevaluierung_fragebogen_gruppe_frage grfr2
				JOIN extension.tbl_lvevaluierung_fragebogen_gruppe gr2 USING (lvevaluierung_fragebogen_gruppe_id)
				WHERE grfr2.lvevaluierung_frage_id = grfr.lvevaluierung_frage_id
				AND gr2.fragebogen_id = gr.fragebogen_id
			)
		';

		return $this->execQuery($qry, [$nach_gruppe_id, $von_gruppe_id]);
	}
}
