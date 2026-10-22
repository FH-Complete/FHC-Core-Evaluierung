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
	public function getByFragebogen($fragebogen_id)
	{
		$this->addSelect('
			lvevaluierung_fragebogen_gruppe_id,
			typ,
			style,
			sort,
			bezeichnung[(1)] AS bezeichnung_de,
			bezeichnung[(2)] AS bezeichnung_en,
			bezeichnung[('. $this->evaluierunglib->getLanguageIndex(). ')] AS bezeichnung_by_language
		');

		$this->addOrder('sort');

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
				fbfr.verpflichtend AS "fbFrageVerpflichtend",
				
				fbfrantw.lvevaluierung_frage_antwort_id,
			  	fbfrantw.bezeichnung[('. $langIndex. ')]AS "fbFrageAntwortBezeichnung",
			  	fbfrantw.sort AS "fbFrageAntwortSort",
			  	fbfrantw.wert,
			  	
			    COALESCE(freq.frequency, 0) AS frequency
		  	FROM
				extension.tbl_lvevaluierung lve
				JOIN extension.tbl_lvevaluierung_fragebogen fb USING (fragebogen_id)
				JOIN extension.tbl_lvevaluierung_fragebogen_gruppe fbgr USING (fragebogen_id)
				JOIN extension.tbl_lvevaluierung_fragebogen_gruppe_frage fbgrfr USING (lvevaluierung_fragebogen_gruppe_id)
				JOIN extension.tbl_lvevaluierung_fragebogen_frage fbfr USING (lvevaluierung_frage_id)
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
				fbfr.verpflichtend AS "fbFrageVerpflichtend",
			
				fbfrantw.lvevaluierung_frage_antwort_id,
				fbfrantw.bezeichnung[('. $langIndex. ')] AS "fbFrageAntwortBezeichnung",
				fbfrantw.sort AS "fbFrageAntwortSort",
				fbfrantw.wert,
			
				COALESCE(freq.frequency, 0) AS frequency
			
			FROM extension.tbl_lvevaluierung_fragebogen fb
			JOIN selected_lve sel USING (fragebogen_id)
			JOIN extension.tbl_lvevaluierung_fragebogen_gruppe fbgr USING (fragebogen_id)
			JOIN extension.tbl_lvevaluierung_fragebogen_gruppe_frage fbgrfr USING (lvevaluierung_fragebogen_gruppe_id)
			JOIN extension.tbl_lvevaluierung_fragebogen_frage fbfr USING (lvevaluierung_frage_id)
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

	/**
	 * Get single-response evaluation data for all Evaluierungen of a given Quellkurs.
	 *
	 * @param $lehrveranstaltung_template_id
	 * @param $studiensemester_kurzbz
	 * @return mixed
	 */
	public function getAuswertungDataByLvTemplateId($lehrveranstaltung_template_id, $studiensemester_kurzbz)
	{
		$langIndex = $this->evaluierunglib->getLanguageIndex();

		$qry = '
			WITH
			selected_lve AS (
				SELECT
					lve.lvevaluierung_id,
					lve.fragebogen_id
				FROM 
					lehre.tbl_lehrveranstaltung lv
					JOIN extension.tbl_lvevaluierung_lehrveranstaltung lvelv USING (lehrveranstaltung_id)
					JOIN extension.tbl_lvevaluierung lve USING (lvevaluierung_lehrveranstaltung_id)
				WHERE
					lv.lehrveranstaltung_template_id = ?
					AND lvelv.studiensemester_kurzbz = ?
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
				WHERE code.lvevaluierung_id IN (
					SELECT lvevaluierung_id
					FROM selected_lve
				)
				GROUP BY
					antwort.lvevaluierung_frage_id,
					antwort.lvevaluierung_frage_antwort_id
			)
	
			SELECT DISTINCT
				fbgr.lvevaluierung_fragebogen_gruppe_id,
				fbgr.typ AS "fbGruppenTyp",
				fbgr.bezeichnung[(' . $langIndex . ')] AS "fbGruppenBezeichnung",
				fbgr.sort AS "fbGruppenSort",
	
				fbfr.lvevaluierung_frage_id,
				fbfr.typ AS "fbFrageTyp",
				fbfr.bezeichnung[(' . $langIndex . ')] AS "fbFrageBezeichnung",
				fbfr.sort AS "fbFrageSort",
				fbfr.verpflichtend AS "fbFrageVerpflichtend",
	
				fbfrantw.lvevaluierung_frage_antwort_id,
				fbfrantw.bezeichnung[(' . $langIndex . ')] AS "fbFrageAntwortBezeichnung",
				fbfrantw.sort AS "fbFrageAntwortSort",
				fbfrantw.wert,
	
				COALESCE(freq.frequency, 0) AS frequency
	
			FROM extension.tbl_lvevaluierung_fragebogen fb
			JOIN (
				SELECT DISTINCT fragebogen_id
				FROM selected_lve
			) sel USING (fragebogen_id)
	
			JOIN extension.tbl_lvevaluierung_fragebogen_gruppe fbgr
				USING (fragebogen_id)
				
			JOIN extension.tbl_lvevaluierung_fragebogen_gruppe_frage fbgrfr
				USING (lvevaluierung_fragebogen_gruppe_id)
	
			JOIN extension.tbl_lvevaluierung_fragebogen_frage fbfr
				USING (lvevaluierung_frage_id)
	
			LEFT JOIN extension.tbl_lvevaluierung_fragebogen_frage_antwort fbfrantw
				USING (lvevaluierung_frage_id)
	
			LEFT JOIN frequencies freq
				ON freq.lvevaluierung_frage_id = fbfr.lvevaluierung_frage_id
				AND freq.lvevaluierung_frage_antwort_id = fbfrantw.lvevaluierung_frage_antwort_id
	
			WHERE fbfr.typ = \'singleresponse\'
	
			ORDER BY
				fbgr.sort,
				fbfr.sort,
				fbfrantw.sort
		';

		return $this->execQuery(
			$qry,
			[$lehrveranstaltung_template_id, $studiensemester_kurzbz]
		);
	}

	/**
	 *  Get single-response comparison data for the same Studiensemester, STG and Semester of the given LVE-LV ID.
	 *
	 * @param $lvevaluierung_lehrveranstaltung_id
	 * @return mixed
	 */
	public function getLvImVergleichDataByLveLv($lvevaluierung_lehrveranstaltung_id)
	{
		$langIndex = $this->evaluierunglib->getLanguageIndex();

		$params = [$lvevaluierung_lehrveranstaltung_id, $lvevaluierung_lehrveranstaltung_id, $lvevaluierung_lehrveranstaltung_id];

		$qry = '
			WITH 
			selected_lve AS (
				SELECT 
					lvevaluierung_id, fragebogen_id
				FROM 
					extension.tbl_lvevaluierung
				 	JOIN extension.tbl_lvevaluierung_lehrveranstaltung lvelv USING (lvevaluierung_lehrveranstaltung_id)
    				JOIN lehre.tbl_lehrveranstaltung lv USING (lehrveranstaltung_id)
    				JOIN public.tbl_studiengang stg USING (studiengang_kz)
				WHERE 
					lvelv.studiensemester_kurzbz = (
						SELECT studiensemester_kurzbz 
						FROM extension.tbl_lvevaluierung_lehrveranstaltung
						WHERE lvevaluierung_lehrveranstaltung_id = ?
					) 
					AND semester = (
						SELECT lv3.semester 
						FROM lehre.tbl_lehrveranstaltung lv3
						JOIN extension.tbl_lvevaluierung_lehrveranstaltung lvelv3 USING (lehrveranstaltung_id)
						WHERE lvelv3.lvevaluierung_lehrveranstaltung_id = ?
					)
					AND studiengang_kz = (
						SELECT stg4.studiengang_kz 
						FROM lehre.tbl_lehrveranstaltung lv4
						JOIN extension.tbl_lvevaluierung_lehrveranstaltung lvelv4 USING (lehrveranstaltung_id)
						JOIN public.tbl_studiengang stg4 USING (studiengang_kz)
						WHERE lvelv4.lvevaluierung_lehrveranstaltung_id = ?
					)
			),
			
			frequencies AS (
				SELECT
					antwort.lvevaluierung_frage_id,
					antwort.lvevaluierung_frage_antwort_id,
					COUNT(*) AS frequency
				FROM 
					extension.tbl_lvevaluierung_antwort antwort
					JOIN extension.tbl_lvevaluierung_code code 
						ON code.lvevaluierung_code_id = antwort.lvevaluierung_code_id AND code.endezeit IS NOT NULL
				WHERE 
					code.lvevaluierung_id IN (SELECT lvevaluierung_id FROM selected_lve)
				GROUP BY
					antwort.lvevaluierung_frage_id,
					antwort.lvevaluierung_frage_antwort_id
			)
			
			SELECT DISTINCT
				fbgr.lvevaluierung_fragebogen_gruppe_id,
				fbgr.typ AS "fbGruppenTyp",
				fbgr.bezeichnung[(' . $langIndex . ')] AS "fbGruppenBezeichnung",
				fbgr.sort AS "fbGruppenSort",
				
				fbfr.lvevaluierung_frage_id,
				fbfr.typ AS "fbFrageTyp",
				fbfr.bezeichnung[(' . $langIndex . ')] AS "fbFrageBezeichnung",
				fbfr.sort AS "fbFrageSort",
				fbfr.verpflichtend AS "fbFrageVerpflichtend",
			
				fbfrantw.lvevaluierung_frage_antwort_id,
				fbfrantw.bezeichnung[(' . $langIndex . ')] AS "fbFrageAntwortBezeichnung",
				fbfrantw.sort AS "fbFrageAntwortSort",
				fbfrantw.wert,
				
				COALESCE(freq.frequency, 0) AS frequency	
			FROM 
				extension.tbl_lvevaluierung_fragebogen fb
				JOIN selected_lve sel USING (fragebogen_id)
				JOIN extension.tbl_lvevaluierung_fragebogen_gruppe fbgr USING (fragebogen_id)
				JOIN extension.tbl_lvevaluierung_fragebogen_gruppe_frage fbgrfr USING (lvevaluierung_fragebogen_gruppe_id)
				JOIN extension.tbl_lvevaluierung_fragebogen_frage fbfr USING (lvevaluierung_frage_id)
				LEFT JOIN extension.tbl_lvevaluierung_fragebogen_frage_antwort fbfrantw USING (lvevaluierung_frage_id)
				LEFT JOIN frequencies freq
			   		ON freq.lvevaluierung_frage_id = fbfr.lvevaluierung_frage_id
					AND freq.lvevaluierung_frage_antwort_id = fbfrantw.lvevaluierung_frage_antwort_id
			WHERE 
				fbfr.typ = \'singleresponse\'
			ORDER BY
				fbgr.sort,
				fbfr.sort,
				fbfrantw.sort;
    	';

		return $this->execQuery($qry, $params);
	}

	/**
	 * Alle Gruppen für den Aufbau, je Bezeichnung DE und Typ eine Zeile: die eigene (aktiv), sonst die neueste.
	 * inAnderenFrageboegen: gleiche Gruppe gibt es auch in einem anderen Fragebogen.
	 *
	 * @param $fragebogen_id
	 * @return mixed
	 */
	public function getAlleGruppen($fragebogen_id)
	{
		$qry = '
			SELECT 
			    *
			FROM (
				SELECT DISTINCT ON (gr.bezeichnung[(1)], gr.typ)
					gr.lvevaluierung_fragebogen_gruppe_id,
					gr.typ,
					gr.bezeichnung[(1)] AS bezeichnung_de,
					gr.bezeichnung[(2)] AS bezeichnung_en,
					CASE WHEN gr.fragebogen_id = ? THEN gr.sort END AS sort,
					gr.fragebogen_id = ? AS aktiv,
					COUNT(*) FILTER (WHERE gr.fragebogen_id <> ?) OVER (PARTITION BY gr.bezeichnung[(1)], gr.typ) > 0 AS "inAnderenFrageboegen",
					(
						SELECT COUNT(*)
						FROM extension.tbl_lvevaluierung_fragebogen_gruppe_frage grfr
						WHERE grfr.lvevaluierung_fragebogen_gruppe_id = gr.lvevaluierung_fragebogen_gruppe_id
					) AS "anzahlFragen"
				FROM 
				    extension.tbl_lvevaluierung_fragebogen_gruppe gr
				ORDER BY 
				    gr.bezeichnung[(1)], 
				    gr.typ, gr.fragebogen_id = ? DESC, 
				    gr.lvevaluierung_fragebogen_gruppe_id DESC
			) g
			ORDER BY 
			    g.aktiv DESC, 
			    g.sort, 
			    g.bezeichnung_de
		';

		return $this->execQuery($qry, [$fragebogen_id, $fragebogen_id, $fragebogen_id, $fragebogen_id]);
	}

	/**
	 * Prüft, ob es die Gruppe (gleiche Bezeichnung DE und Typ) auch in einem anderen Fragebogen gibt.
	 *
	 * @param $gruppe_id
	 * @return mixed
	 */
	public function isInAnderenFrageboegen($gruppe_id)
	{
		$qry = '
			SELECT EXISTS (
				SELECT 1
				FROM extension.tbl_lvevaluierung_fragebogen_gruppe gr
				JOIN extension.tbl_lvevaluierung_fragebogen_gruppe andere
					ON andere.bezeichnung[(1)] = gr.bezeichnung[(1)]
					AND andere.typ = gr.typ
					AND andere.fragebogen_id <> gr.fragebogen_id
				WHERE gr.lvevaluierung_fragebogen_gruppe_id = ?
			) AS "inAnderenFrageboegen"
		';

		return $this->execQuery($qry, [$gruppe_id]);
	}

	/**
	 * Insert Fragebogengruppe. Bezeichnung als text[] (1 = DE, 2 = EN).
	 *
	 * @return mixed
	 */
	public function insertGruppe($fragebogen_id, $typ, $bezeichnung_de, $bezeichnung_en, $style, $sort)
	{
		$qry = '
			INSERT INTO extension.tbl_lvevaluierung_fragebogen_gruppe (
				fragebogen_id,
			   	typ, 
			   	bezeichnung, 
			   	style, 
		   		sort
			)
			VALUES (?, ?, ARRAY[?, ?], ?, ?)
			RETURNING lvevaluierung_fragebogen_gruppe_id
		';

		// trim: CI erkennt RETURNING nur ohne führende Leerzeichen
		return $this->execQuery(trim($qry), [$fragebogen_id, $typ, $bezeichnung_de, $bezeichnung_en, $style, $sort]);
	}

	/**
	 * Update Fragebogengruppe. Bezeichnung als text[] (1 = DE, 2 = EN).
	 *
	 * @param $gruppe_id
	 * @param $typ
	 * @param $bezeichnung_de
	 * @param $bezeichnung_en
	 * @param $style
	 * @param $sort
	 * @return mixed
	 */
	public function updateGruppe($gruppe_id, $typ, $bezeichnung_de, $bezeichnung_en, $style, $sort)
	{
		$qry = '
			UPDATE 
			    extension.tbl_lvevaluierung_fragebogen_gruppe
			SET 
			    typ = ?, 
			    bezeichnung = ARRAY[?, ?], 
			    style = ?, 
			    sort = ?
			WHERE 
			    lvevaluierung_fragebogen_gruppe_id = ?
		';

		return $this->execQuery($qry, [$typ, $bezeichnung_de, $bezeichnung_en, $style, $sort, $gruppe_id]);
	}
}
