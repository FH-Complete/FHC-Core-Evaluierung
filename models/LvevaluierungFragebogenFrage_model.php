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
	 * Alle Fragen für den Aufbau, aktiv = in der Gruppe. Aktive zuerst.
	 * andereGruppe: Gruppe dieses Fragebogens, in der die Frage schon ist (eine Frage nur einmal je Fragebogen).
	 * inAnderenFrageboegen: Frage wird auch in anderen Fragebögen verwendet.
	 * inVerwendetemFragebogen: Frage ist in einem anderen Fragebogen, der bereits in einer Evaluierung verwendet wird.
	 *
	 * @param $gruppe_id
	 * @param $fragebogen_id
	 * @return mixed
	 */
	public function getAlleFragen($gruppe_id, $fragebogen_id)
	{
		$qry = '
			SELECT
				fr.lvevaluierung_frage_id,
				fr.typ,
				fr.bezeichnung[(1)] AS bezeichnung_de,
				fr.bezeichnung[(2)] AS bezeichnung_en,
				fr.verpflichtend,
				CASE WHEN grfr.lvevaluierung_frage_id IS NOT NULL THEN fr.sort END AS sort,
				grfr.lvevaluierung_frage_id IS NOT NULL AS aktiv,
				(
					SELECT gr2.bezeichnung[(1)]
					FROM extension.tbl_lvevaluierung_fragebogen_gruppe_frage grfr2
					JOIN extension.tbl_lvevaluierung_fragebogen_gruppe gr2 USING (lvevaluierung_fragebogen_gruppe_id)
					WHERE grfr2.lvevaluierung_frage_id = fr.lvevaluierung_frage_id
					AND gr2.fragebogen_id = ?
					AND gr2.lvevaluierung_fragebogen_gruppe_id != ?
					LIMIT 1
				) AS "andereGruppe",
				EXISTS (
					SELECT 1
					FROM extension.tbl_lvevaluierung_fragebogen_gruppe_frage grfr3
					JOIN extension.tbl_lvevaluierung_fragebogen_gruppe gr3 USING (lvevaluierung_fragebogen_gruppe_id)
					WHERE grfr3.lvevaluierung_frage_id = fr.lvevaluierung_frage_id
					AND gr3.fragebogen_id != ?
				) AS "inAnderenFrageboegen",
				EXISTS (
					SELECT 1
					FROM extension.tbl_lvevaluierung_fragebogen_gruppe_frage grfr4
					JOIN extension.tbl_lvevaluierung_fragebogen_gruppe gr4 USING (lvevaluierung_fragebogen_gruppe_id)
					JOIN extension.tbl_lvevaluierung lve ON lve.fragebogen_id = gr4.fragebogen_id
					WHERE grfr4.lvevaluierung_frage_id = fr.lvevaluierung_frage_id
					AND gr4.fragebogen_id != ?
				) AS "inVerwendetemFragebogen"
			FROM 
			    extension.tbl_lvevaluierung_fragebogen_frage fr
			LEFT JOIN extension.tbl_lvevaluierung_fragebogen_gruppe_frage grfr
				ON grfr.lvevaluierung_frage_id = fr.lvevaluierung_frage_id
				AND grfr.lvevaluierung_fragebogen_gruppe_id = ?
			ORDER BY
				grfr.lvevaluierung_frage_id IS NULL,
				CASE WHEN grfr.lvevaluierung_frage_id IS NOT NULL THEN fr.sort END,
				fr.bezeichnung[(1)]
		';

		return $this->execQuery($qry, [$fragebogen_id, $gruppe_id, $fragebogen_id, $fragebogen_id, $gruppe_id]);
	}

	/**
	 * Frage für das Modal (Bezeichnung und Placeholder: 1 = DE, 2 = EN).
	 *
	 * @param $frage_id
	 * @return mixed
	 */
	public function getFrage($frage_id)
	{
		$qry = '
			SELECT
				lvevaluierung_frage_id,
				typ,
				verpflichtend,
				bezeichnung[(1)] AS bezeichnung_de,
				bezeichnung[(2)] AS bezeichnung_en,
				placeholder[(1)] AS placeholder_de,
				placeholder[(2)] AS placeholder_en
			FROM extension.tbl_lvevaluierung_fragebogen_frage
			WHERE lvevaluierung_frage_id = ?
		';

		return $this->execQuery($qry, [$frage_id]);
	}

	/**
	 * Prüft, ob die Frage auch in einem anderen Fragebogen verwendet wird.
	 *
	 * @param $frage_id
	 * @param $fragebogen_id
	 * @return mixed
	 */
	public function isInAnderenFrageboegen($frage_id, $fragebogen_id)
	{
		$qry = '
			SELECT EXISTS (
				SELECT 1
				FROM extension.tbl_lvevaluierung_fragebogen_gruppe_frage grfr
				JOIN extension.tbl_lvevaluierung_fragebogen_gruppe gr USING (lvevaluierung_fragebogen_gruppe_id)
				WHERE grfr.lvevaluierung_frage_id = ?
				AND gr.fragebogen_id != ?
			) AS "inAnderenFrageboegen"
		';

		return $this->execQuery($qry, [$frage_id, $fragebogen_id]);
	}

	/**
	 * Prüft, ob die Frage schon in einer Gruppe des Fragebogens ist.
	 *
	 * @param $frage_id
	 * @param $fragebogen_id
	 * @return mixed
	 */
	public function isImFragebogen($frage_id, $fragebogen_id)
	{
		$qry = '
			SELECT EXISTS (
				SELECT 1
				FROM extension.tbl_lvevaluierung_fragebogen_gruppe_frage grfr
				JOIN extension.tbl_lvevaluierung_fragebogen_gruppe gr USING (lvevaluierung_fragebogen_gruppe_id)
				WHERE grfr.lvevaluierung_frage_id = ?
				AND gr.fragebogen_id = ?
			) AS "imFragebogen"
		';

		return $this->execQuery($qry, [$frage_id, $fragebogen_id]);
	}

	/**
	 * Insert Frage. Bezeichnung und Placeholder als text[] (1 = DE, 2 = EN), Placeholder nur bei Freitext.
	 *
	 * @return mixed
	 */
	public function insertFrage($typ, $bezeichnung_de, $bezeichnung_en, $placeholder_de, $placeholder_en, $verpflichtend, $sort)
	{
		$qry = "
			INSERT INTO extension.tbl_lvevaluierung_fragebogen_frage (
		  		typ, 
			  	bezeichnung, 
			  	placeholder, 
			  	verpflichtend, 
			  	sort
		  	)
			VALUES (?, ARRAY[?, ?], CASE WHEN ? = 'text' THEN ARRAY[?, ?] END, ?, ?)
			RETURNING lvevaluierung_frage_id
		";

		// trim: CI erkennt RETURNING nur ohne führende Leerzeichen
		return $this->execQuery(trim($qry), [
			$typ, $bezeichnung_de, $bezeichnung_en, $typ, $placeholder_de, $placeholder_en, $verpflichtend, $sort
		]);
	}

	/**
	 * Update Frage. Bezeichnung und Placeholder als text[] (1 = DE, 2 = EN), Placeholder nur bei Freitext.
	 *
	 * @return mixed
	 */
	public function updateFrage($frage_id, $typ, $bezeichnung_de, $bezeichnung_en, $placeholder_de, $placeholder_en, $verpflichtend, $sort)
	{
		$qry = "
			UPDATE extension.tbl_lvevaluierung_fragebogen_frage
			SET
				typ = ?,
				bezeichnung = ARRAY[?, ?],
				placeholder = CASE WHEN ? = 'text' THEN ARRAY[?, ?] END,
				verpflichtend = ?,
				sort = ?
			WHERE 
				lvevaluierung_frage_id = ?
		";

		return $this->execQuery($qry, [
			$typ, $bezeichnung_de, $bezeichnung_en, $typ, $placeholder_de, $placeholder_en, $verpflichtend, $sort, $frage_id
		]);
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
