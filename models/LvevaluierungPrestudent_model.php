<?php

class LvevaluierungPrestudent_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_prestudent';
		$this->pk = 'lvevaluierung_prestudent_id';
	}

	/**
	 * Get Lvevaluierung Prestudenten by given Lvevaluierung ID.
	 * Returns all students of given Evaluierung, that were already mailed.
	 *
	 * @param $lvevaluierung_id
	 * @return mixed
	 */
	public function getByLve($lvevaluierung_id)
	{
		$this->addSelect('
			extension.tbl_lvevaluierung_prestudent.prestudent_id,
			p.vorname,
			p.nachname,
			b.uid
		');
		$this->addJoin('public.tbl_prestudent pst', 'prestudent_id');
		$this->addJoin('public.tbl_student stud', 'prestudent_id');
		$this->addJoin('public.tbl_benutzer b', 'stud.student_uid = b.uid');
		$this->addJoin('public.tbl_person p', 'p.person_id = b.person_id');

		return $this->loadWhere(
			[
				'lvevaluierung_id' => $lvevaluierung_id,
			]
		);
	}

	/**
	 * Get Lvevaluierung Prestudenten by given Lvevaluierung Lehrveranstaltung ID.
	 * Returns all students of all Evaluierungen found in Lehrveranstaltung, that were already mailed.
	 *
	 * @param $lvevaluierung_lehrveranstaltung_id
	 * @return mixed
	 */
	public function getByLveLv($lvevaluierung_lehrveranstaltung_id)
	{
		$this->addSelect('
			extension.tbl_lvevaluierung_prestudent.prestudent_id,
			p.vorname,
			p.nachname,
			b.uid
		');
		$this->addJoin('public.tbl_prestudent pst', 'prestudent_id');
		$this->addJoin('public.tbl_student stud', 'prestudent_id');
		$this->addJoin('public.tbl_benutzer b', 'stud.student_uid = b.uid');
		$this->addJoin('public.tbl_person p', 'p.person_id = b.person_id');

		return $this->loadWhere('
			lvevaluierung_id IN (
				SELECT 
					lvevaluierung_id
				FROM
					extension.tbl_lvevaluierung
				WHERE
					lvevaluierung_lehrveranstaltung_id = '. $lvevaluierung_lehrveranstaltung_id. '
			)
		');
	}
}
