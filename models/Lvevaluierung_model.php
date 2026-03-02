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
	 * Get Evaluierungen whose start date matches the given day offset, related to the current date.
	 *
	 * @param int|string $offsetDays	e.g.: 0|0 day = today. -1|-1 day = yesterday, +14|+14 day = in two weeks
	 * @return mixed
	 */
	public function getLvesWithStartzeitIn($offsetDays = 0){

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
				tbl_studiengang.typ as stg_typ
			FROM		  	
				extension.tbl_lvevaluierung lve
				JOIN extension.tbl_lvevaluierung_lehrveranstaltung lvelv USING (lvevaluierung_lehrveranstaltung_id)
				JOIN lehre.tbl_lehrveranstaltung USING(lehrveranstaltung_id)
				JOIN public.tbl_studiengang USING(studiengang_kz)
			WHERE
			-- startzeit eingeschränkt auf das gewünschte Tagesdatum
			lve.startzeit >= CURRENT_DATE + INTERVAL ?
				AND lve.startzeit < CURRENT_DATE + INTERVAL ? + INTERVAL \'1 day\'
			ORDER BY
			  stg_bezeichnung,
			  lv_orgform_kurzbz
		';

		return $this->execQuery($qry, [$offsetDays, $offsetDays]);
	}

	/**
	 * Get Evaluierungen whose end date matches the given day offset, related to the current date.
	 *
	 * @param int|string $offsetDays	e.g.: 0|0 day = today. -1|-1 day = yesterday, +14|+14 day = in two weeks
	 * @return mixed
	 */
	public function getLvesWithEndezeitIn($offsetDays = 0){

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
				tbl_studiengang.typ as stg_typ
			FROM		  	
				extension.tbl_lvevaluierung lve
				JOIN extension.tbl_lvevaluierung_lehrveranstaltung lvelv USING (lvevaluierung_lehrveranstaltung_id)
				JOIN lehre.tbl_lehrveranstaltung USING(lehrveranstaltung_id)
				JOIN public.tbl_studiengang USING(studiengang_kz)
			WHERE
			-- endezeit eingeschränkt auf das gewünschte Tagesdatum
			lve.endezeit >= CURRENT_DATE + INTERVAL ?
				AND lve.endezeit < CURRENT_DATE + INTERVAL ? + INTERVAL \'1 day\'
			ORDER BY
			  stg_bezeichnung,
			  lv_orgform_kurzbz
		';

		return $this->execQuery($qry, [$offsetDays, $offsetDays]);
	}


}
