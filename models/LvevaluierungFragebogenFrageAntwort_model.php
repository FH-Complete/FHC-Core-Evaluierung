<?php

class LvevaluierungFragebogenFrageAntwort_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_fragebogen_frage_antwort';
		$this->pk = 'lvevaluierung_frage_antwort_id';
	}

	/**
	 * Get all possible Antworten of Frage by FragenID.
	 *
	 * @param $lvevaluierung_frage_id
	 * @return mixed
	 */
	public function getAntwortenByFrage($lvevaluierung_frage_id)
	{
		$this->load->library('extensions/FHC-Core-Evaluierung/EvaluierungLib');

		$this->addSelect('
			*, 
			bezeichnung[('. $this->evaluierunglib->getLanguageIndex(). ')] AS bezeichnung_by_language
		');

		$this->addOrder('lvevaluierung_frage_id, sort');

		return $this->LvevaluierungFragebogenFrageAntwortModel->loadWhere([
			'lvevaluierung_frage_id' => $lvevaluierung_frage_id
		]);
	}

	/**
	 * Antworten mehrerer Fragen (Bezeichnung: 1 = DE, 2 = EN).
	 */
	public function getAntwortenByFragen($frageIds)
	{
		$qry = '
			SELECT
				lvevaluierung_frage_antwort_id,
				lvevaluierung_frage_id,
				wert,
				sort,
				bezeichnung[(1)] AS bezeichnung_de,
				bezeichnung[(2)] AS bezeichnung_en
			FROM extension.tbl_lvevaluierung_fragebogen_frage_antwort
			WHERE lvevaluierung_frage_id IN ?
			ORDER BY lvevaluierung_frage_id, sort
		';

		return $this->execQuery($qry, [$frageIds]);
	}

	/**
	 * Insert Antwort. Bezeichnung als text[] (1 = DE, 2 = EN).
	 *
	 * @return mixed
	 */
	public function insertAntwort($frage_id, $bezeichnung_de, $bezeichnung_en, $sort, $wert)
	{
		$qry = '
			INSERT INTO extension.tbl_lvevaluierung_fragebogen_frage_antwort (
	  			lvevaluierung_frage_id, 
	  			bezeichnung, 
	  			sort, 
	  			wert
	  		)
			VALUES (?, ARRAY[?, ?], ?, ?)
		';

		return $this->execQuery($qry, [$frage_id, $bezeichnung_de, $bezeichnung_en, $sort, $wert]);
	}

	/**
	 * Update Antwort. Bezeichnung als text[] (1 = DE, 2 = EN).
	 *
	 * @param $antwort_id
	 * @param $bezeichnung_de
	 * @param $bezeichnung_en
	 * @param $sort
	 * @param $wert
	 * @return mixed
	 */
	public function updateAntwort($antwort_id, $bezeichnung_de, $bezeichnung_en, $sort, $wert)
	{
		$qry = '
			UPDATE 
			    extension.tbl_lvevaluierung_fragebogen_frage_antwort
			SET 
			    bezeichnung = ARRAY[?, ?], 
			    sort = ?, 
			    wert = ?
			WHERE 
			    lvevaluierung_frage_antwort_id = ?
		';

		return $this->execQuery($qry, [$bezeichnung_de, $bezeichnung_en, $sort, $wert, $antwort_id]);
	}
}
