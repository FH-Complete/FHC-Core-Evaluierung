<?php

class LvevaluierungLehreinheit_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'lehre.tbl_lehreinheit';
		$this->pk = 'lehreinheit_id';
	}

	/**
	 * Gets Studenten mehrerer Lehreinheiten in einem Query
	 *
	 * @param array $lehreinheitIds
	 * @return array
	 */
	public function getStudentsByLes($lehreinheitIds)
	{
		$qry = '
			SELECT 
				lehreinheit_id, uid, vorname, nachname, prestudent_id
			FROM 
			    campus.vw_student_lehrveranstaltung
				JOIN campus.vw_student USING (uid)
			WHERE 
			    lehreinheit_id IN ?
			ORDER BY 
			    nachname
		';

		return $this->execQuery($qry, [$lehreinheitIds]);
	}

}
