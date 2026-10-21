<?php

class LvevaluierungFragebogenFrage_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_fragebogen_frage';
		$this->pk = 'lvevaluierung_frage_id';
	}

	/**
	 * Get Fragen by FragebogengruppenID.
	 *
	 * @param $lvevaluierung_fragebogen_gruppe_id
	 * @return mixed
	 */
	public function getFragenByFragebogengruppe($lvevaluierung_fragebogen_gruppe_id)
	{
		$this->load->library('extensions/FHC-Core-Evaluierung/EvaluierungLib');

		$this->addSelect('
			tbl_lvevaluierung_fragebogen_frage.*, 
			bezeichnung[('. $this->evaluierunglib->getLanguageIndex(). ')] AS bezeichnung_by_language,
			placeholder[('. $this->evaluierunglib->getLanguageIndex(). ')] AS placeholder_by_language
		');

		$this->addJoin(
			'extension.tbl_lvevaluierung_fragebogen_gruppe_frage lvefgf',
			'lvefgf.lvevaluierung_frage_id = tbl_lvevaluierung_fragebogen_frage.lvevaluierung_frage_id'
		);

		$this->addOrder('sort');

		return $this->loadWhere([
			'lvefgf.lvevaluierung_fragebogen_gruppe_id' => $lvevaluierung_fragebogen_gruppe_id
		]);
	}

	/**
	 * Fragen eines Fragebogens, inkl. Sperre (beantwortet) und
	 * Verwendung (in wie vielen unterschiedlichen Fragebögen eine bestimmte Frage beantwortet wird).
	 */
	public function getFragenForEditor($fragebogen_id)
	{
		$qry = '
			SELECT
				grfr.lvevaluierung_fragebogen_gruppe_id,
				fr.lvevaluierung_frage_id,
				fr.typ,
				fr.verpflichtend,
				fr.sort,
				fr.bezeichnung[(1)] AS bezeichnung_de,
				fr.bezeichnung[(2)] AS bezeichnung_en,
				fr.placeholder[(1)] AS placeholder_de,
				fr.placeholder[(2)] AS placeholder_en,
				EXISTS (
					SELECT 1
					FROM extension.tbl_lvevaluierung_antwort antw
					WHERE antw.lvevaluierung_frage_id = fr.lvevaluierung_frage_id
				) AS gesperrt,
				(
					SELECT COUNT(DISTINCT gr2.fragebogen_id)
					FROM extension.tbl_lvevaluierung_fragebogen_gruppe_frage grfr2
					JOIN extension.tbl_lvevaluierung_fragebogen_gruppe gr2 USING (lvevaluierung_fragebogen_gruppe_id)
					WHERE grfr2.lvevaluierung_frage_id = fr.lvevaluierung_frage_id
				) AS "anzahlFrageboegen"
			FROM extension.tbl_lvevaluierung_fragebogen_gruppe gr
			JOIN extension.tbl_lvevaluierung_fragebogen_gruppe_frage grfr USING (lvevaluierung_fragebogen_gruppe_id)
			JOIN extension.tbl_lvevaluierung_fragebogen_frage fr ON fr.lvevaluierung_frage_id = grfr.lvevaluierung_frage_id
			WHERE gr.fragebogen_id = ?
			ORDER BY gr.sort, fr.sort
		';

		return $this->execQuery($qry, [$fragebogen_id]);
	}

	/**
	 * Fragen löschen, die keiner (anderen) Gruppe mehr zugeordnet und nie beantwortet wurden.
	 *
	 * @param $frageIds
	 * @return mixed
	 */
	public function deleteUnbenutzteFragen($frageIds)
	{
		$qry = '
			DELETE FROM 
				   extension.tbl_lvevaluierung_fragebogen_frage fr
			WHERE 
			    fr.lvevaluierung_frage_id IN ?
			AND NOT EXISTS (
				SELECT 1
				FROM extension.tbl_lvevaluierung_fragebogen_gruppe_frage grfr
				WHERE grfr.lvevaluierung_frage_id = fr.lvevaluierung_frage_id
			)
			AND NOT EXISTS (
				SELECT 1
				FROM extension.tbl_lvevaluierung_antwort antw
				WHERE antw.lvevaluierung_frage_id = fr.lvevaluierung_frage_id
			)
		';

		return $this->execQuery($qry, [$frageIds]);
	}
}
