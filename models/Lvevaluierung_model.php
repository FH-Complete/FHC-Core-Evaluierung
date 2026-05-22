<?php

class Lvevaluierung_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung';
		$this->pk = 'lvevaluierung_id';
	}

	/**
	 * Insert new Lvevaluierung.
	 *
	 * @param $lvevaluierung
	 * @return mixed
	 */
	public function insertLvevaluierung($lvevaluierung)
	{
		unset($lvevaluierung['lvevaluierung_id']);

		$lvevaluierung['insertvon'] = getAuthUID();
		$lvevaluierung['codes_gemailt'] = isset($lvevaluierung['codes_gemailt'])
			? $lvevaluierung['codes_gemailt']
			: false;

		$result = $this->insert($lvevaluierung);

		if (isError($result))
		{
			return error($result->msg, EXIT_ERROR);
		}

		$record = $this->load($result->retval);

		return $record;
	}

	/**
	 * Updates Lvevaluierung.
	 *
	 * @param $lvevaluierung
	 * @return mixed
	 */
	public function updateLvevaluierung($lvevaluierung)
	{
		$lvevaluierung['updatevon'] = getAuthUID();
		$lvevaluierung['updateamum'] = $this->escape('NOW()');

		$result = $this->update($lvevaluierung['lvevaluierung_id'], $lvevaluierung);

		if (isError($result))
		{
			return error($result->msg, EXIT_ERROR);
		}

		return $this->load($lvevaluierung['lvevaluierung_id']);
	}

	/**
	 * Get Evaluierungen, that were sent and whose start date matches the given day offset, related to the current date.
	 *
	 * @param int|string $offsetDays	e.g.: 0|0 day = today. -1|-1 day = yesterday, +14|+14 day = in two weeks
	 * @return mixed
	 */
	public function getLvesStartingIn($offsetDays = 0, $codesGemailt = true){

		if (is_int($offsetDays))
		{
			$offsetDays = $offsetDays. ' day';
		}

		$qry = '
			SELECT
			    lve.*,
				lvelv.*,
				tbl_lehrveranstaltung.bezeichnung as lv_bezeichnung,
				tbl_lehrveranstaltung.bezeichnung_english as lv_bezeichnung_english,
				tbl_lehrveranstaltung.semester as lv_semester,
				tbl_lehrveranstaltung.orgform_kurzbz as lv_orgform_kurzbz,
				tbl_studiengang.bezeichnung as stg_bezeichnung,
				UPPER(tbl_studiengang.typ || tbl_studiengang.kurzbz) AS stg_typ_kurzbz	
			FROM		  	
				extension.tbl_lvevaluierung lve
				JOIN extension.tbl_lvevaluierung_lehrveranstaltung lvelv USING (lvevaluierung_lehrveranstaltung_id)
				JOIN lehre.tbl_lehrveranstaltung USING(lehrveranstaltung_id)
				JOIN public.tbl_studiengang USING(studiengang_kz)
			WHERE
			    -- nur wenn codes versendet wurden
				lve.codes_gemailt = ?
				-- startzeit eingeschränkt auf das gewünschte Tagesdatum
				AND lve.startzeit >= CURRENT_DATE + INTERVAL ?
				AND lve.startzeit < CURRENT_DATE + INTERVAL ? + INTERVAL \'1 day\'
			ORDER BY
			  stg_bezeichnung,
			  lv_orgform_kurzbz
		';

		return $this->execQuery($qry, [$codesGemailt, $offsetDays, $offsetDays]);
	}

	/**
	 * Get Evaluierungen, that were sent and whose end date matches the given day offset, related to the current date.
	 *
	 * @param int|string $offsetDays	e.g.: 0|0 day = today. -1|-1 day = yesterday, +14|+14 day = in two weeks
	 * @return mixed
	 */
	public function getLvesEndingIn($offsetDays = 0, $codesGemailt = true){

		if (is_int($offsetDays))
		{
			$offsetDays = $offsetDays. ' day';
		}

		$qry = '
			SELECT
			    lve.*,
				lvelv.*,
				tbl_lehrveranstaltung.bezeichnung as lv_bezeichnung,
				tbl_lehrveranstaltung.bezeichnung_english as lv_bezeichnung_english,
				tbl_lehrveranstaltung.semester as lv_semester,
				tbl_lehrveranstaltung.orgform_kurzbz as lv_orgform_kurzbz,
				tbl_studiengang.bezeichnung as stg_bezeichnung,
				UPPER(tbl_studiengang.typ || tbl_studiengang.kurzbz) AS stg_typ_kurzbz	
			FROM		  	
				extension.tbl_lvevaluierung lve
				JOIN extension.tbl_lvevaluierung_lehrveranstaltung lvelv USING (lvevaluierung_lehrveranstaltung_id)
				JOIN lehre.tbl_lehrveranstaltung USING(lehrveranstaltung_id)
				JOIN public.tbl_studiengang USING(studiengang_kz)
			WHERE
			    -- nur wenn codes versendet wurden
				lve.codes_gemailt = ?
				-- endezeit eingeschränkt auf das gewünschte Tagesdatum
				AND lve.endezeit >= CURRENT_DATE + INTERVAL ?
				AND lve.endezeit < CURRENT_DATE + INTERVAL ? + INTERVAL \'1 day\'
			ORDER BY
			  stg_bezeichnung,
			  lv_orgform_kurzbz
		';

		return $this->execQuery($qry, [$codesGemailt, $offsetDays, $offsetDays]);
	}

	public function getExportData($studiensemester = null, $von = null, $bis = null)
	{
		$where  = [];
		$params = [];

		if (!empty($studiensemester)) {
			$where[]  = 'lvelv.studiensemester_kurzbz = ?';
			$params[] = $studiensemester;
		}
		if (!empty($von)) {
			$where[]  = 'lve.insertamum >= ?';
			$params[] = $von . ' 00:00:00';
		}
		if (!empty($bis)) {
			$where[]  = 'lve.insertamum <= ?';
			$params[] = $bis . ' 23:59:59';
		}

		$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

		$qry = "
        WITH answers_pivoted AS (
		SELECT
			lvec.lvevaluierung_code_id,
	
			-- Pflichtfragen (gruppe sort=1): 2x singleresponse, verpflichtend
			MAX(CASE WHEN lvefg.sort = 1 AND lvef.sort = 1
				THEN lvefa.wert::text END)                      AS pflichtfrage_1,
			MAX(CASE WHEN lvefg.sort = 1 AND lvef.sort = 2
				THEN lvefa.wert::text END)                      AS pflichtfrage_2,
	
			-- Optionale Bereiche angeklickt? (any answer under gruppe typ='group')
			BOOL_OR(lvefg.typ = 'group')                        AS optionale_bereiche_angeklickt,
	
			-- Organisation (gruppe sort=3): 2x singleresponse
			MAX(CASE WHEN lvefg.sort = 3 AND lvef.sort = 1
				THEN lvefa.wert::text END)                      AS organisation_frage_1,
			MAX(CASE WHEN lvefg.sort = 3 AND lvef.sort = 2
				THEN lvefa.wert::text END)                      AS organisation_frage_2,
	
			-- Moodle Kurs (gruppe sort=4): 3x singleresponse
			MAX(CASE WHEN lvefg.sort = 4 AND lvef.sort = 1
				THEN lvefa.wert::text END)                      AS moodle_frage_1,
			MAX(CASE WHEN lvefg.sort = 4 AND lvef.sort = 2
				THEN lvefa.wert::text END)                      AS moodle_frage_2,
			MAX(CASE WHEN lvefg.sort = 4 AND lvef.sort = 3
				THEN lvefa.wert::text END)                      AS moodle_frage_3,
	
			-- Durchführung der LV (gruppe sort=5): 3x singleresponse
			MAX(CASE WHEN lvefg.sort = 5 AND lvef.sort = 1
				THEN lvefa.wert::text END)                      AS durchfuehrung_frage_1,
			MAX(CASE WHEN lvefg.sort = 5 AND lvef.sort = 2
				THEN lvefa.wert::text END)                      AS durchfuehrung_frage_2,
			MAX(CASE WHEN lvefg.sort = 5 AND lvef.sort = 3
				THEN lvefa.wert::text END)                      AS durchfuehrung_frage_3,
	
			-- Infrastruktur (gruppe sort=6): 3x singleresponse
			MAX(CASE WHEN lvefg.sort = 6 AND lvef.sort = 1
				THEN lvefa.wert::text END)                      AS infrastruktur_frage_1,
			MAX(CASE WHEN lvefg.sort = 6 AND lvef.sort = 2
				THEN lvefa.wert::text END)                      AS infrastruktur_frage_2,
			MAX(CASE WHEN lvefg.sort = 6 AND lvef.sort = 3
				THEN lvefa.wert::text END)                      AS infrastruktur_frage_3,
	
			-- Freitextfragen (gruppe sort=7): 2x text — ja/nein only
			MAX(CASE WHEN lvefg.sort = 7 AND lvef.sort = 1
				THEN CASE WHEN lvea.antwort IS NOT NULL AND lvea.antwort <> ''
					THEN lvea.antwort ELSE 'nein' END END)              AS freitext_1,
			MAX(CASE WHEN lvefg.sort = 7 AND lvef.sort = 2
				THEN CASE WHEN lvea.antwort IS NOT NULL AND lvea.antwort <> ''
					THEN lvea.antwort ELSE 'nein' END END)              AS freitext_2
	
		FROM extension.tbl_lvevaluierung_code lvec
		LEFT JOIN extension.tbl_lvevaluierung_antwort lvea
			ON lvea.lvevaluierung_code_id = lvec.lvevaluierung_code_id
		LEFT JOIN extension.tbl_lvevaluierung_fragebogen_frage lvef
			ON lvef.lvevaluierung_frage_id = lvea.lvevaluierung_frage_id
		LEFT JOIN extension.tbl_lvevaluierung_fragebogen_gruppe lvefg
			ON lvefg.lvevaluierung_fragebogen_gruppe_id = lvef.lvevaluierung_fragebogen_gruppe_id
		LEFT JOIN extension.tbl_lvevaluierung_fragebogen_frage_antwort lvefa
			ON lvefa.lvevaluierung_frage_antwort_id = lvea.lvevaluierung_frage_antwort_id
		GROUP BY lvec.lvevaluierung_code_id
	),
	sender AS (
		SELECT
			lvevaluierung_id,
			MIN(insertamum) AS linkversand_datum,
			MIN(insertvon)  AS linkversand_von
		FROM extension.tbl_lvevaluierung_prestudent
		GROUP BY lvevaluierung_id
	),
	gruppe AS (
		SELECT DISTINCT ON (lehreinheit_id)
			lehreinheit_id,
			verband, gruppe, gruppe_kurzbz, semester
		FROM lehre.tbl_lehreinheitgruppe
		ORDER BY lehreinheit_id, gruppe_kurzbz
	)
	SELECT
		lv.lehrveranstaltung_id,
		lv.bezeichnung                              AS lv_titel,
		lv.bezeichnung_english                      AS lv_titel_english,
		lv.semester                                 AS lv_semester,
		lv.orgform_kurzbz                           AS lv_orgform,
		stg.bezeichnung                             AS studiengang,
		stg.typ                                     AS studiengang_typ,
		lvelv.verpflichtend                         AS zur_eval_ausgewaehlt,
		CASE
			WHEN lvelv.lv_aufgeteilt THEN
				COALESCE(
					CASE WHEN lvg.verband IS NOT NULL
						THEN CONCAT(lvg.semester, ' - ', lvg.verband, ' - ', lvg.gruppe)
					END,
					lvg.gruppe_kurzbz
				)
			ELSE 'Gesamt'
		END                                         AS gruppen_info,
		lve.lvevaluierung_id IS NOT NULL            AS lv_leitung_hat_datum_eingetragen,
		lve.startzeit,
		lve.endezeit,
		sender.linkversand_datum,
		sender.linkversand_von,
		lvec.startzeit IS NOT NULL                  AS code_verwendet,
		lvec.endezeit IS NOT NULL                   AS lv_eval_abgeschlossen,
		lvec.startzeit::date                        AS durchfuehrungsdatum,
		lvec.startzeit::time                        AS code_startzeit,
		lvec.endezeit::time                         AS code_endzeit,
	
		-- Pflichtfragen
		COALESCE(ap.pflichtfrage_1, '99')           AS pflichtfrage_1,
		COALESCE(ap.pflichtfrage_2, '99')           AS pflichtfrage_2,
	
		-- Optionale Bereiche
		CASE WHEN ap.optionale_bereiche_angeklickt  THEN 'ja' ELSE 'nein' END
													AS optionale_bereiche_angeklickt,
		COALESCE(ap.organisation_frage_1, '99')     AS organisation_frage_1,
		COALESCE(ap.organisation_frage_2, '99')     AS organisation_frage_2,
		COALESCE(ap.moodle_frage_1, '99')           AS moodle_frage_1,
		COALESCE(ap.moodle_frage_2, '99')           AS moodle_frage_2,
		COALESCE(ap.moodle_frage_3, '99')           AS moodle_frage_3,
		COALESCE(ap.durchfuehrung_frage_1, '99')    AS durchfuehrung_frage_1,
		COALESCE(ap.durchfuehrung_frage_2, '99')    AS durchfuehrung_frage_2,
		COALESCE(ap.durchfuehrung_frage_3, '99')    AS durchfuehrung_frage_3,
		COALESCE(ap.infrastruktur_frage_1, '99')    AS infrastruktur_frage_1,
		COALESCE(ap.infrastruktur_frage_2, '99')    AS infrastruktur_frage_2,
		COALESCE(ap.infrastruktur_frage_3, '99')    AS infrastruktur_frage_3,
	
		-- Freitextfragen
		COALESCE(ap.freitext_1, 'nein')             AS freitext_1,
		COALESCE(ap.freitext_2, 'nein')             AS freitext_2
	
	FROM extension.tbl_lvevaluierung_lehrveranstaltung lvelv
		JOIN  lehre.tbl_lehrveranstaltung lv    USING (lehrveranstaltung_id)
		JOIN  public.tbl_studiengang      stg   USING (studiengang_kz)
		LEFT JOIN extension.tbl_lvevaluierung lve
			USING (lvevaluierung_lehrveranstaltung_id)
		LEFT JOIN extension.tbl_lvevaluierung_code lvec
			ON lvec.lvevaluierung_id = lve.lvevaluierung_id
		LEFT JOIN answers_pivoted ap
			ON ap.lvevaluierung_code_id = lvec.lvevaluierung_code_id
		LEFT JOIN sender
			ON sender.lvevaluierung_id = lve.lvevaluierung_id
		LEFT JOIN gruppe lvg
			ON lvg.lehreinheit_id = lve.lehreinheit_id
			$whereClause
	ORDER BY
		lvelv.studiensemester_kurzbz,
		lv.lehrveranstaltung_id,
		lve.lvevaluierung_id,
		lvec.lvevaluierung_code_id
		";
		
		return $this->execReadOnlyQuery($qry, $params);
	}


}
