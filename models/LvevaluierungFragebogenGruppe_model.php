<?php

class LvevaluierungFragebogenGruppe_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_fragebogen_gruppe';
		$this->pk = 'lvevaluierung_fragebogen_gruppe_id';
	}

	/**
	 * Get Fragebogengruppe by FragebogenID.
	 *
	 * @param $fragebogen_id
	 * @return mixed
	 */
	public function getFragebogengruppeByFragebogen($fragebogen_id)
	{
		$this->load->library('extensions/FHC-Core-Evaluierung/EvaluierungLib');

		$this->addSelect('
			*, 
			bezeichnung[('. $this->evaluierunglib->getLanguageIndex(). ')] AS bezeichnung_by_language
		');

		return $this->loadWhere([
			'fragebogen_id' => $fragebogen_id
		]);
	}
}
