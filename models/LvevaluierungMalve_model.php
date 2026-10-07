<?php

class LvevaluierungMalve_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_malve';
		$this->pk = 'malve_id';
	}

	/**
	 * Prüft, ob ein Fachgebiet des Kompetenzfelds bereits vom FK geprüft wurde.
	 *
	 * @param string $kf_oe_kurzbz // OE des Kompetenzfelds
	 * @param string $studiensemester_kurzbz
	 * @return bool
	 */
	public function isApprovedByFK($kf_oe_kurzbz, $studiensemester_kurzbz)
	{
		$this->addJoin('public.tbl_organisationseinheit oe', 'oe_kurzbz');

		$result = $this->loadWhere([
			'oe.oe_parent_kurzbz' => $kf_oe_kurzbz,
			'oe.organisationseinheittyp_kurzbz' => 'Fachgebiet',
			'studiensemester_kurzbz' => $studiensemester_kurzbz
		]);

		return hasData($result);
	}
}
