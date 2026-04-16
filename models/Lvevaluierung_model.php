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
	 * Insert multiple Lvevaluierungen.
	 *
	 * @param $batch
	 * @return mixed
	 */
	public function insertBatch($batch)
	{
		// Check class properties
		if (is_null($this->dbTable)) return error('The given database table name is not valid', EXIT_MODEL);

		// Insert data
		$insert = $this->db->insert_batch($this->dbTable, $batch);

		if ($insert)
		{
			return success();
		}
		else
		{
			return error($this->db->error(), EXIT_DATABASE);
		}
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
	 * Get Lvevaluilerungen by given Studiensemester.
	 *
	 * @param $studiensemester_kurzbz
	 * @return mixed
	 */
	public function getLvesByStSem($studiensemester_kurzbz)
	{
		$this->addSelect('lvevaluierung_id');
		$this->addSelect('lvevaluierung_lehrveranstaltung_id');
		$this->addSelect('lehreinheit_id');
		$this->addSelect('fragebogen_id');

		$this->addJoin('extension.tbl_lvevaluierung_lehrveranstaltung lvelv', 'lvevaluierung_lehrveranstaltung_id');

		return $this->loadWhere([
			'lvelv.studiensemester_kurzbz' => $studiensemester_kurzbz
		]);
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


}
