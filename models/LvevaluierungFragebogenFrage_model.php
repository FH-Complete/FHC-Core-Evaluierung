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
}
