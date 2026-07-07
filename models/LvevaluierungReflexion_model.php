<?php

class LvevaluierungReflexion_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_reflexion';
		$this->pk = 'lvevaluierung_reflexion_id';
	}

	/**
	 * Check if Reflexionszeitraum is abgeschlossen for given LVE ID.
	 *
	 * @param $lvevaluierung_id
	 * @return mixed
	 */
	public function isReflexionszeitraumAbgeschlossenForLve($lvevaluierung_id)
	{
		$this->load->config('extensions/FHC-Core-Evaluierung/initiierung');

		$qry = "
			SELECT 
			    1
			FROM 
			    extension.tbl_lvevaluierung lve
			WHERE 
			    lve.lvevaluierung_id = ?
				AND lve.codes_gemailt
			    AND lve.endezeit IS NOT NULL
				AND DATE(lve.endezeit) 
					+ INTERVAL '1 day'
					+ COALESCE(CAST(? AS INTERVAL), INTERVAL '0 day') 
					< CURRENT_DATE
		";

		$result = $this->execQuery(
			$qry,
			[
				$lvevaluierung_id,
				$this->config->item('reflexionZeitfensterDauer')
			]
		);

		return hasData($result);
	}

	/**
	 * Check if Reflexionszeitraum is abgeschlossen for all Evalueriungen in given LveLv ID.
	 *
	 * @param $lvevaluierung_lehrveranstaltung_id
	 * @return mixed
	 */
	public function isReflexionszeitraumAbgeschlossenForAllLvesInLveLv($lvevaluierung_lehrveranstaltung_id)
	{
		$this->load->config('extensions/FHC-Core-Evaluierung/initiierung');

		$qry = "
        	SELECT 1
        		-- Nur wenn Evaluierungen existieren...
				WHERE EXISTS (
					SELECT 1 
					FROM extension.tbl_lvevaluierung lve2
					WHERE lve2.lvevaluierung_lehrveranstaltung_id = ?
				)
				-- ...und keine mehr im Reflexionszeitraum sind
				AND NOT EXISTS (
					SELECT 1
						FROM extension.tbl_lvevaluierung lve
						JOIN extension.tbl_lvevaluierung_lehrveranstaltung lvelv USING (lvevaluierung_lehrveranstaltung_id)
					WHERE 
						lvelv.lvevaluierung_lehrveranstaltung_id = ?
						AND lve.codes_gemailt
						AND lve.endezeit IS NOT NULL
						-- Evaluierung ist noch im Reflexionszeitraum
						AND DATE(lve.endezeit) + COALESCE(CAST(? AS INTERVAL), INTERVAL '0 day') >= CURRENT_DATE
				)
			";

		// true wenn bei allen Evaluierungen der Reflexionszeitraum abgeschlossen ist.
		// false wenn bei mindestens einer Evaluierung der Reflexionszeitraum nicht abgeschlossen ist
		$result = $this->execQuery(
			$qry,
			[
				$lvevaluierung_lehrveranstaltung_id,
				$lvevaluierung_lehrveranstaltung_id,
				$this->config->item('reflexionZeitfensterDauer')
			]
		);

		return hasData($result);
	}

	/**
	 * Ermittelt Anzahl Evaluierungen and bisher erstellte Reflexionen, nach LV zusammengefasst.
	 * Berücksichtigt dabei nur verpflichtende Reflexionen, die innerhalb von bis erstellt worden sind.
	 *
	 * @param string $vondatum
	 * @param string $bisdatum
	 * @return void
	 */
	public function getPflichtReflexionenInsertedVonBis($vondatum, $bisdatum)
	{

		$params = [$vondatum, $bisdatum];

		$qry = '
			-- LVs ermitteln, wo mindestens eine neue verpflichtende LV-Reflexion im Berichtszeitraum gespeichert wurde
			WITH newReflexionen_lvelvs AS (
				SELECT DISTINCT
					lvelv.lvevaluierung_lehrveranstaltung_id
				FROM
					extension.tbl_lvevaluierung_reflexion lver
					JOIN extension.tbl_lvevaluierung lve USING (lvevaluierung_id)
					JOIN extension.tbl_lvevaluierung_lehrveranstaltung lvelv USING (lvevaluierung_lehrveranstaltung_id)
				WHERE
					lver.verpflichtend = TRUE
					AND lver.insertamum > ?
					AND lver.insertamum <= ?
			)
			
			-- Für alle LVs mit neuer LV-Reflexion, jeweils:
			---- Anzahl der zugehörigen Evaluierungen ermitteln (count_lves)
			---- Anzahl der insgesamt durchgeführten, zugehörigen verpflichtenden Reflexionen ermitteln
			SELECT
				lvelv.lvevaluierung_lehrveranstaltung_id,
				lvelv.lv_aufgeteilt,
				COUNT(DISTINCT lve.lvevaluierung_id) AS count_lves,
				COUNT(DISTINCT lver.lvevaluierung_reflexion_id) AS "count_pflichtReflexionen",
				lv.bezeichnung AS "lv_bezeichnung",
				stg.studiengang_kz,
				stg.typ as stg_typ,
				UPPER(TRIM(CONCAT(stg.typ, stg.kurzbz))) AS "stgKurzbz",
				stg.bezeichnung AS "stg_bezeichnung",
				lv.oe_kurzbz,
				oe.bezeichnung AS "oe_bezeichnung"
			FROM
				extension.tbl_lvevaluierung_lehrveranstaltung lvelv
				JOIN newReflexionen_lvelvs nrlvelvs USING (lvevaluierung_lehrveranstaltung_id)
				JOIN extension.tbl_lvevaluierung lve USING (lvevaluierung_lehrveranstaltung_id)
				-- nur verpflichtende (Gruppe: alle, bei Gesamt-LV nur die von LV-Leitung relevant)
				LEFT JOIN extension.tbl_lvevaluierung_reflexion lver 
					ON lver.lvevaluierung_id = lve.lvevaluierung_id AND lver.verpflichtend = TRUE
				JOIN lehre.tbl_lehrveranstaltung lv USING (lehrveranstaltung_id)
				JOIN public.tbl_studiengang stg USING (studiengang_kz)
				JOIN public.tbl_organisationseinheit oe ON oe.oe_kurzbz = lv.oe_kurzbz
			GROUP BY
				lvelv.lvevaluierung_lehrveranstaltung_id,
				lvelv.lv_aufgeteilt,
				lv.bezeichnung,
				stg.studiengang_kz,
				stg.typ,
				stg.kurzbz,
				stg.bezeichnung,
				lv.oe_kurzbz,
				oe.bezeichnung
			-- nur die LVs, die vollständige LV-Reflexionen haben
			HAVING 
				COUNT(DISTINCT lve.lvevaluierung_id) <= COUNT(DISTINCT lver.lvevaluierung_reflexion_id)
			';

		return $this->execQuery($qry, $params);

	}

	/**
	 *  Ermittelt unvollständige Abgabe von Reflexionen, nach LV zusammengefasst.
	 *  Berücksichtigt dabei nur verpflichtende Reflexionen, die innerhalb von bis das Reflexionsendedatum überschritten haben.
	 *  Für Gesamt-LV Evaluierung gilt: return row wenn die eine verpflichtende (von LV-Leitung) fehlt.
	 *  Für Gruppen-Evaluierung gilt: return row wenn alle Gruppen-Evaluierungen das Reflexionszeitendedatum überschritten haben und zumindest eine davon keine Reflexion hat.
	 *
	 * @param $vondatum
	 * @param $bisdatum
	 * @return mixed
	 */
	public function getPflichtReflexionenMissedAbgabeVonBis($vondatum, $bisdatum, $studiensemester_kurzbz)
	{
		$params = [$studiensemester_kurzbz, $vondatum, $bisdatum, $bisdatum];
		$qry = '
			-- LVs im Studiensemester ermitteln mit Spalte has_pflichtreflexion. 
			-- True wenn mindestens eine Pflichtreflexion vorhanden, sonst false.
			WITH tmp_lvelvs AS (
				SELECT
					lvelv.lvevaluierung_lehrveranstaltung_id,
					lvelv.lehrveranstaltung_id,
					lvelv.lv_aufgeteilt,
					lve.lvevaluierung_id,
					lve.endezeit,
					EXISTS (
						SELECT 1
						FROM extension.tbl_lvevaluierung_reflexion lver
						WHERE lver.lvevaluierung_id = lve.lvevaluierung_id
						  AND lver.verpflichtend = TRUE
					) AS has_pflichtreflexion
				FROM
					extension.tbl_lvevaluierung_lehrveranstaltung lvelv
					JOIN extension.tbl_lvevaluierung lve USING (lvevaluierung_lehrveranstaltung_id)
					WHERE lvelv.studiensemester_kurzbz = ?
			),
			
			-- LVs, mit jeweils:
			-- Anzahl der zugehörigen Evaluierungen
			-- Anzahl der Evaluierungen, wo Reflexionszeit in den Berichstzeitraum reinfällt
			-- Anzahl der Evaluierungen ohne LV-Reflexion
			lvelvs AS (
				SELECT
					lvevaluierung_lehrveranstaltung_id,
					lehrveranstaltung_id,
					lv_aufgeteilt,
					COUNT(*) AS count_lves,
					-- 15 Tage (1 Tag nach Evaluierungsende + 14 Tage Reflexionszeitraum)
					COUNT(*) FILTER (
						WHERE (endezeit + INTERVAL \'15 days\') > ?
						  AND (endezeit + INTERVAL \'15 days\') <= ?
					) AS count_lves_reflexionszeit_im_zeitraum_beendet,
        			COUNT(*) FILTER (
            			WHERE (endezeit + INTERVAL \'15 days\') <= ?
        			) AS count_lves_reflexionszeit_beendet,
					COUNT(*) FILTER (
						WHERE NOT has_pflichtreflexion
					) AS count_lves_ohne_pflichtreflexion
				FROM tmp_lvelvs
				GROUP BY
					lvevaluierung_lehrveranstaltung_id,
					lehrveranstaltung_id,
					lv_aufgeteilt
			)
			
			SELECT lvelvs.*,
				lv.bezeichnung AS "lv_bezeichnung",
				stg.studiengang_kz,
				stg.typ as stg_typ,
				UPPER(TRIM(CONCAT(stg.typ, stg.kurzbz))) AS "stgKurzbz",
				stg.bezeichnung AS "stg_bezeichnung",
				lv.oe_kurzbz,
				oe.bezeichnung AS "oe_bezeichnung"
			FROM lvelvs
				JOIN lehre.tbl_lehrveranstaltung lv USING (lehrveranstaltung_id)
				JOIN public.tbl_studiengang stg USING (studiengang_kz)
				JOIN public.tbl_organisationseinheit oe ON oe.oe_kurzbz = lv.oe_kurzbz
			WHERE
				(
					-- Gesamt-LV: 
					-- Reflexionszeit endet im Berichtszeitraum und die verpflichtende LV-Reflexion fehlt
					lv_aufgeteilt = FALSE
					AND count_lves_reflexionszeit_im_zeitraum_beendet = 1
					AND count_lves_ohne_pflichtreflexion = 1
				)
				OR
				(
					-- Gruppe: 
				  	-- Mindestens eine Reflexionszeit endet im Berichtszeitraum,
    				-- alle zugehörigen Reflexionszeiten sind bereits beendet,
    				-- und mindestens eine verpflichtende LV-Reflexion fehlt.
					lv_aufgeteilt = TRUE
					AND count_lves_reflexionszeit_im_zeitraum_beendet > 0
					AND count_lves_reflexionszeit_beendet = count_lves
					AND count_lves_ohne_pflichtreflexion > 0
				);
		';

		return $this->execQuery($qry, $params);
	}
}
