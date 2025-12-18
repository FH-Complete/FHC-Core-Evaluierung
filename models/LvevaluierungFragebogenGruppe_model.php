<?php

class LvevaluierungFragebogenGruppe_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_fragebogen_gruppe';
		$this->pk = 'lvevaluierung_fragebogen_gruppe_id';

		$this->load->library('extensions/FHC-Core-Evaluierung/EvaluierungLib');
	}

	/**
	 * Get Fragebogengruppe by FragebogenID.
	 *
	 * @param $fragebogen_id
	 * @return mixed
	 */
	public function getFragebogengruppeByFragebogen($fragebogen_id)
	{
		$this->addSelect('
			*, 
			bezeichnung[('. $this->evaluierunglib->getLanguageIndex(). ')] AS bezeichnung_by_language
		');

		return $this->loadWhere([
			'fragebogen_id' => $fragebogen_id
		]);
	}

	/**
	 * Get single-response evaluation data for a given LVE ID.
	 *
	 * @param $lvevaluierung_id
	 * @return mixed
	 */
	public function getAuswertungDataByLve($lvevaluierung_id)
	{
		$langIndex = $this->evaluierunglib->getLanguageIndex();

		$qry = '
			WITH 
			frequencies AS (
				SELECT
					antwort.lvevaluierung_frage_id,
					antwort.lvevaluierung_frage_antwort_id,
					COUNT(*) AS frequency
				FROM extension.tbl_lvevaluierung_antwort antwort
				JOIN extension.tbl_lvevaluierung_code code
					ON code.lvevaluierung_code_id = antwort.lvevaluierung_code_id
					AND code.endezeit IS NOT NULL
				WHERE code.lvevaluierung_id = ?
					GROUP BY
						antwort.lvevaluierung_frage_id,
						antwort.lvevaluierung_frage_antwort_id,
						antwort.antwort
			)
				
		  	SELECT
		  	  	lve.lvevaluierung_id,
				fragebogen_id,
				fbgr.lvevaluierung_fragebogen_gruppe_id,
				fbgr.typ AS "fbGruppenTyp",
				fbgr.bezeichnung[('. $langIndex. ')] AS "fbGruppenBezeichnung",
				fbgr.sort AS "fbGruppenSort",
				
				fbfr.lvevaluierung_frage_id,
				fbfr.typ AS "fbFrageTyp",
				fbfr.bezeichnung[('. $langIndex. ')] AS "fbFrageBezeichnung",
				fbfr.sort AS "fbFrageSort",
				
				fbfrantw.lvevaluierung_frage_antwort_id,
			  	fbfrantw.bezeichnung[('. $langIndex. ')]AS "fbFrageAntwortBezeichnung",
			  	fbfrantw.sort AS "fbFrageAntwortSort",
			  	fbfrantw.wert,
			  	
			    COALESCE(freq.frequency, 0) AS frequency
		  	FROM
				extension.tbl_lvevaluierung lve
				JOIN extension.tbl_lvevaluierung_fragebogen fb USING (fragebogen_id)
				JOIN extension.tbl_lvevaluierung_fragebogen_gruppe fbgr USING (fragebogen_id)
				JOIN extension.tbl_lvevaluierung_fragebogen_frage fbfr USING (lvevaluierung_fragebogen_gruppe_id)
				LEFT JOIN extension.tbl_lvevaluierung_fragebogen_frage_antwort fbfrantw USING (lvevaluierung_frage_id)
				LEFT JOIN frequencies freq ON freq.lvevaluierung_frage_id = fbfr.lvevaluierung_frage_id AND freq.lvevaluierung_frage_antwort_id = fbfrantw.lvevaluierung_frage_antwort_id
		  	WHERE
				lve.lvevaluierung_id = ?
				-- filter out text or others
				AND fbfr.typ = \'singleresponse\'
				  -- AND fb.gueltig_von... AND fb.gueltig_bis...
		  	ORDER BY
				fbgr.sort,
				fbfr.sort,
			  	fbfrantw.sort
    	';

		return $this->execQuery($qry, [$lvevaluierung_id, $lvevaluierung_id]);
	}

	/**
	 * Get single-response evaluation data for a given LVE-LV ID.
	 *
	 * @param $lvevaluierung_lehrveranstaltung_id
	 * @return mixed
	 */
	public function getAuswertungDataByLveLv($lvevaluierung_lehrveranstaltung_id)
	{
		$langIndex = $this->evaluierunglib->getLanguageIndex();

		$qry = '
			WITH 
			selected_lve AS (
				SELECT lvevaluierung_id, fragebogen_id
				FROM extension.tbl_lvevaluierung
				WHERE lvevaluierung_lehrveranstaltung_id = ?
			),
			
			frequencies AS (
				SELECT
					antwort.lvevaluierung_frage_id,
					antwort.lvevaluierung_frage_antwort_id,
					COUNT(*) AS frequency
				FROM extension.tbl_lvevaluierung_antwort antwort
				JOIN extension.tbl_lvevaluierung_code code
					ON code.lvevaluierung_code_id = antwort.lvevaluierung_code_id
				   AND code.endezeit IS NOT NULL
				WHERE code.lvevaluierung_id IN (SELECT lvevaluierung_id FROM selected_lve)
				GROUP BY
					antwort.lvevaluierung_frage_id,
					antwort.lvevaluierung_frage_antwort_id
			)
			
			SELECT DISTINCT
				fbgr.lvevaluierung_fragebogen_gruppe_id,
				fbgr.typ AS "fbGruppenTyp",
				fbgr.bezeichnung[('. $langIndex. ')] AS "fbGruppenBezeichnung",
				fbgr.sort AS "fbGruppenSort",
			
				fbfr.lvevaluierung_frage_id,
				fbfr.typ AS "fbFrageTyp",
				fbfr.bezeichnung[('. $langIndex. ')] AS "fbFrageBezeichnung",
				fbfr.sort AS "fbFrageSort",
			
				fbfrantw.lvevaluierung_frage_antwort_id,
				fbfrantw.bezeichnung[('. $langIndex. ')] AS "fbFrageAntwortBezeichnung",
				fbfrantw.sort AS "fbFrageAntwortSort",
				fbfrantw.wert,
			
				COALESCE(freq.frequency, 0) AS frequency
			
			FROM extension.tbl_lvevaluierung_fragebogen fb
			JOIN selected_lve sel USING (fragebogen_id)
			JOIN extension.tbl_lvevaluierung_fragebogen_gruppe fbgr USING (fragebogen_id)
			JOIN extension.tbl_lvevaluierung_fragebogen_frage fbfr USING (lvevaluierung_fragebogen_gruppe_id)
			LEFT JOIN extension.tbl_lvevaluierung_fragebogen_frage_antwort fbfrantw USING (lvevaluierung_frage_id)
			LEFT JOIN frequencies freq
				   ON freq.lvevaluierung_frage_id = fbfr.lvevaluierung_frage_id
				  AND freq.lvevaluierung_frage_antwort_id = fbfrantw.lvevaluierung_frage_antwort_id
			
			WHERE fbfr.typ = \'singleresponse\'
			
			ORDER BY
				fbgr.sort,
				fbfr.sort,
				fbfrantw.sort;
    	';

		return $this->execQuery($qry, [$lvevaluierung_lehrveranstaltung_id]);
	}
}
