<?php

class LvevaluierungStundenplan_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'lehre.tbl_stundenplan';
		$this->pk = 'stundenplan_id';
	}

	/**
	 * Get filtered Stundenplantermine for given Lehreinheit.
	 *
	 * @param $lehreinheit_id
	 * @return array|stdClass|null
	 */
	public function getTermineByLe($lehreinheit_id)
	{
		$this->load->config('extensions/FHC-Core-Evaluierung/initiierung');
		$excludedLehrformen = $this->config->item('excludedLehrformen');

		$params = [$lehreinheit_id];

		$qry = '
			SELECT DISTINCT
			    datum
			FROM 
		       	lehre.vw_stundenplan
				JOIN lehre.tbl_lehreinheit le USING (lehreinheit_id)
			WHERE 
				le.lehreinheit_id = ?
		';

		if (is_array($excludedLehrformen) && !empty($excludedLehrformen))
		{
			$qry .= ' AND le.lehrform_kurzbz NOT IN ? ';

			$params[] = $excludedLehrformen;
		}

		$qry .= '
			ORDER BY datum ASC
		';

		return $this->execQuery($qry, $params);
	}


	/**
	 * Get filtered Stundenplantermine for given Lehrveranstaltung of given Studiensemester.
	 *
	 * @param $lehrveranstaltung_id
	 * @param $studiensemester_kurzbz
	 * @return array|stdClass|null
	 */
	public function getTermineByLv($lehrveranstaltung_id, $studiensemester_kurzbz)
	{
		$this->load->config('extensions/FHC-Core-Evaluierung/initiierung');
		$excludedLehrformen = $this->config->item('excludedLehrformen');

		$params = [$lehrveranstaltung_id, $studiensemester_kurzbz];

		$qry = '
		  	SELECT DISTINCT
				datum
	   		FROM
	   		    lehre.vw_stundenplan
	   			JOIN lehre.tbl_lehreinheit le USING (lehreinheit_id)
	  		WHERE 
	  		    lehreinheit_id IN (
					SELECT lehreinheit_id
					FROM lehre.tbl_lehreinheit 
					WHERE lehrveranstaltung_id = ?
					AND studiensemester_kurzbz = ?
				)    
		';

		if (is_array($excludedLehrformen) && !empty($excludedLehrformen))
		{
			$qry .= ' AND le.lehrform_kurzbz NOT IN ? ';

			$params[] = $excludedLehrformen;
		}

		$qry .= '
			ORDER BY datum ASC
		';

		return $this->execQuery($qry, $params);
	}
}
