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
			*, 
			bezeichnung[('. $this->evaluierunglib->getLanguageIndex(). ')] AS bezeichnung_by_language
		');

		$this->addOrder('lvevaluierung_fragebogen_gruppe_id, sort');

		return $this->loadWhere([
			'lvevaluierung_fragebogen_gruppe_id' => $lvevaluierung_fragebogen_gruppe_id
		]);
	}
}
