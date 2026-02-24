<?php

class LvevaluierungZeitfenster_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_zeitfenster';
		$this->pk = 'lvevaluierung_zeitfenster_id';
	}

	/**
	 * Checks if different actions are possible now or if the time period is over
	 *
	 * @param $lvevaluierung_lehrveranstaltung_id
	 * @return boolean
	 */
	public function isZeitfensterOffenLve($typ, $lvevaluierung_lehrveranstaltung_id)
	{
		$qry = '
            SELECT 
                CASE 
                    WHEN (now() between COALESCE(startdatum,\'1970-01-01\') AND COALESCE(endedatum,\'2999-01-01\')) 
                    THEN 
                        true 
                    ELSE 
                        false 
                    END as zeitfensteroffen
            FROM
                extension.tbl_lvevaluierung_lehrveranstaltung
                LEFT JOIN extension.tbl_lvevaluierung_zeitfenster USING(studiensemester_kurzbz) 
            WHERE 
                lvevaluierung_lehrveranstaltung_id=?
                AND typ=?
            LIMIT 1
		';

		$result = $this->execQuery($qry, [$lvevaluierung_lehrveranstaltung_id, $typ]);
        if(isSuccess($result))
        {
            if(hasData($result) && getData($result)[0]->zeitfensteroffen==false)
                return false;
        } 
        return true;
	}
}
