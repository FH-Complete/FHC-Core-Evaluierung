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

	public function getAntwortenByCodeIds(array $codeIds)
	{
		if (empty($codeIds))
			return success([]);

		$placeholders = implode(',', array_fill(0, count($codeIds), '?'));

		$qry = "
        SELECT
            lvea.lvevaluierung_code_id,
            lvea.lvevaluierung_frage_id,
            lvea.antwort,
            lvefa.wert
        FROM extension.tbl_lvevaluierung_antwort lvea
        LEFT JOIN extension.tbl_lvevaluierung_fragebogen_frage_antwort lvefa
            ON lvefa.lvevaluierung_frage_antwort_id = lvea.lvevaluierung_frage_antwort_id
        WHERE lvea.lvevaluierung_code_id IN ($placeholders)
    ";

		return $this->execReadOnlyQuery($qry, $codeIds);
	}
	
	public function getFragebogenStruktur(array $fragebogenIds)
	{
		if (empty($fragebogenIds))
			return success([]);

		$placeholders = implode(',', array_fill(0, count($fragebogenIds), '?'));

		$qry = "
        SELECT
            lvefg.lvevaluierung_fragebogen_gruppe_id,
            lvefg.sort         AS gruppe_sort,
            lvefg.typ          AS gruppe_typ,
            lvefg.bezeichnung  AS gruppe_bezeichnung,
            lvef.lvevaluierung_frage_id,
            lvef.sort          AS frage_sort,
            lvef.typ           AS frage_typ,
            lvef.verpflichtend,
            lvef.bezeichnung   AS frage_bezeichnung
        FROM extension.tbl_lvevaluierung_fragebogen_gruppe lvefg
        JOIN extension.tbl_lvevaluierung_fragebogen_frage lvef
            ON lvef.lvevaluierung_fragebogen_gruppe_id = lvefg.lvevaluierung_fragebogen_gruppe_id
        WHERE lvefg.fragebogen_id IN ($placeholders)
        ORDER BY lvefg.sort, lvef.sort
    ";

		return $this->execReadOnlyQuery($qry, $fragebogenIds);
	}
	
	public function getExportBaseRows($studiensemester, $von, $bis)
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
		$whereClause = !empty($where) ? "\nWHERE " . implode(' AND ', $where) : '';

		$qry = "
        WITH sender AS (
            SELECT lvevaluierung_id,
                   MIN(insertamum) AS linkversand_datum,
                   MIN(insertvon)  AS linkversand_von
            FROM extension.tbl_lvevaluierung_prestudent
            GROUP BY lvevaluierung_id
        ),
        gruppe AS (
            SELECT DISTINCT ON (lehreinheit_id)
                lehreinheit_id, verband, gruppe, gruppe_kurzbz, semester
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
                            THEN CONCAT(lvg.semester, lvg.verband, lvg.gruppe)
                        END,
                        lvg.gruppe_kurzbz
                    )
                ELSE 'Gesamt'
            END                                         AS gruppen_info,
            lve.lvevaluierung_id IS NOT NULL            AS lv_leitung_hat_datum_eingetragen,
            lve.startzeit, lve.endezeit,
            sender.linkversand_datum, sender.linkversand_von,
            lve.fragebogen_id,
            lvec.lvevaluierung_code_id,
            lvec.startzeit IS NOT NULL                  AS code_verwendet,
            lvec.endezeit IS NOT NULL                   AS lv_eval_abgeschlossen,
            lvec.startzeit::date                        AS durchfuehrungsdatum,
            lvec.startzeit::time                        AS code_startzeit,
            lvec.endezeit::time                         AS code_endzeit
        FROM extension.tbl_lvevaluierung_lehrveranstaltung lvelv
            JOIN  lehre.tbl_lehrveranstaltung lv  USING (lehrveranstaltung_id)
            JOIN  public.tbl_studiengang      stg USING (studiengang_kz)
            LEFT JOIN extension.tbl_lvevaluierung lve
                USING (lvevaluierung_lehrveranstaltung_id)
            LEFT JOIN extension.tbl_lvevaluierung_code lvec
                ON lvec.lvevaluierung_id = lve.lvevaluierung_id
            LEFT JOIN sender ON sender.lvevaluierung_id = lve.lvevaluierung_id
            LEFT JOIN gruppe lvg ON lvg.lehreinheit_id = lve.lehreinheit_id
            ".$whereClause."
        ORDER BY
            lvelv.studiensemester_kurzbz, lv.lehrveranstaltung_id,
            lve.lvevaluierung_id, lvec.lvevaluierung_code_id
    ";

		return $this->execReadOnlyQuery($qry, $params);
	}

	// =================== EXPORT WITH CURSOR ===================
	

	public function getDistinctFragebogenIds($studiensemester, $von, $bis)
	{
		list($whereClause, $params) = $this->buildBaseWhereAndParams($studiensemester, $von, $bis);		
		$qry = "
        SELECT DISTINCT lve.fragebogen_id
        FROM extension.tbl_lvevaluierung_lehrveranstaltung lvelv
        LEFT JOIN extension.tbl_lvevaluierung lve USING (lvevaluierung_lehrveranstaltung_id)
        ".$whereClause;
		
		return $this->execReadOnlyQuery($qry, $params);
	}

	public function getExportRowCount($studiensemester, $von, $bis)
	{
		list($whereClause, $params) = $this->buildBaseWhereAndParams($studiensemester, $von, $bis);		
		$qry = "
        SELECT COUNT(*) AS cnt
        FROM extension.tbl_lvevaluierung_lehrveranstaltung lvelv
            JOIN  lehre.tbl_lehrveranstaltung lv  USING (lehrveranstaltung_id)
            JOIN  public.tbl_studiengang      stg USING (studiengang_kz)
            LEFT JOIN extension.tbl_lvevaluierung lve USING (lvevaluierung_lehrveranstaltung_id)
            LEFT JOIN extension.tbl_lvevaluierung_code lvec ON lvec.lvevaluierung_id = lve.lvevaluierung_id
            ".$whereClause;
		
		return $this->execReadOnlyQuery($qry, $params);
	}

	public function buildBaseWhereAndParams($studiensemester, $von, $bis)
	{
		$where = [];
		$params = [];
		if (!empty($studiensemester)) { $where[] = 'lvelv.studiensemester_kurzbz = ?'; $params[] = $studiensemester; }
		if (!empty($von)) { $where[] = 'lve.insertamum >= ?'; $params[] = $von . ' 00:00:00'; }
		if (!empty($bis)) { $where[] = 'lve.insertamum <= ?'; $params[] = $bis . ' 23:59:59'; }
		$whereClause = !empty($where) ? "\nWHERE " . implode(' AND ', $where) : '';
		return [$whereClause, $params];
	}

	public function buildBaseSql($whereClause)
	{
		return "
        WITH sender AS (
            SELECT lvevaluierung_id, MIN(insertamum) AS linkversand_datum, MIN(insertvon) AS linkversand_von
            FROM extension.tbl_lvevaluierung_prestudent GROUP BY lvevaluierung_id
        ),
        gruppe AS (
            SELECT DISTINCT ON (lehreinheit_id) lehreinheit_id, verband, gruppe, gruppe_kurzbz, semester
            FROM lehre.tbl_lehreinheitgruppe ORDER BY lehreinheit_id, gruppe_kurzbz
        )
        SELECT
            lv.lehrveranstaltung_id, lv.bezeichnung AS lv_titel, lv.bezeichnung_english AS lv_titel_english,
            lv.semester AS lv_semester, lv.orgform_kurzbz AS lv_orgform,
            stg.bezeichnung AS studiengang, stg.typ AS studiengang_typ,
            lvelv.verpflichtend AS zur_eval_ausgewaehlt,
            CASE WHEN lvelv.lv_aufgeteilt THEN
                COALESCE(
                    CASE WHEN lvg.verband IS NOT NULL
                        THEN CONCAT(lvg.semester, lvg.verband, lvg.gruppe) END,
                    lvg.gruppe_kurzbz
                )
                ELSE 'Gesamt'
            END AS gruppen_info,
            lve.lvevaluierung_id IS NOT NULL AS lv_leitung_hat_datum_eingetragen,
            lve.startzeit, lve.endezeit,
            sender.linkversand_datum, sender.linkversand_von,
            lve.fragebogen_id,
            lvec.lvevaluierung_code_id,
            lvec.startzeit IS NOT NULL AS code_verwendet,
            lvec.endezeit IS NOT NULL AS lv_eval_abgeschlossen,
            lvec.startzeit::date AS durchfuehrungsdatum,
            lvec.startzeit::time AS code_startzeit,
            lvec.endezeit::time AS code_endzeit
        FROM extension.tbl_lvevaluierung_lehrveranstaltung lvelv
            JOIN  lehre.tbl_lehrveranstaltung lv  USING (lehrveranstaltung_id)
            JOIN  public.tbl_studiengang      stg USING (studiengang_kz)
            LEFT JOIN extension.tbl_lvevaluierung lve USING (lvevaluierung_lehrveranstaltung_id)
            LEFT JOIN extension.tbl_lvevaluierung_code lvec ON lvec.lvevaluierung_id = lve.lvevaluierung_id
            LEFT JOIN sender ON sender.lvevaluierung_id = lve.lvevaluierung_id
            LEFT JOIN gruppe lvg ON lvg.lehreinheit_id = lve.lehreinheit_id
            ".$whereClause."
        ORDER BY lvelv.studiensemester_kurzbz, lv.lehrveranstaltung_id, lve.lvevaluierung_id, lvec.lvevaluierung_code_id
    ";
	}

}
