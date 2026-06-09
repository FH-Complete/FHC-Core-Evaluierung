<?php

class LvevaluierungReflexionAntwortPraesenz_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_reflexion_antwort_praesenz';
		$this->pk = 'praesenz_kurzbz';
	}

	public function loadByUserLang()
	{
		$this->load->library('extensions/FHC-Core-Evaluierung/EvaluierungLib');

		$this->addSelect('
			praesenz_kurzbz,
			bezeichnung_mehrsprachig[('. $this->evaluierunglib->getLanguageIndex(). ')] AS bezeichnung,
		');

		return $this->load();
	}
}
