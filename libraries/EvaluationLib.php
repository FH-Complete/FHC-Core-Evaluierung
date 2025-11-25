<?php

/**
 * FH-Complete
 *
 * @package             FHC-Helper
 * @author              FHC-Team
 * @copyright           Copyright (c) 2022 fhcomplete.net
 * @license             GPLv3
 */

if (! defined('BASEPATH')) exit('No direct script access allowed');

class EvaluationLib
{
	private $_ci; // Code igniter instance
	public function __construct()
	{
		$this->_ci =& get_instance();
	}

	/**
	 * Get Lehrveranstaltung Infos.
	 *
	 * @param $lehrveranstaltung_id
	 * @param $studiensemester_kurzbz
	 * @return array|mixed
	 */
	public function getLvData($lehrveranstaltung_id, $studiensemester_kurzbz)
	{
		// LV data
		$this->_ci->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');
		$this->_ci->LehrveranstaltungModel->addJoin('public.tbl_studiengang stg', 'studiengang_kz');
		$this->_ci->LehrveranstaltungModel->addSelect('
			tbl_lehrveranstaltung.lehrveranstaltung_id,
			tbl_lehrveranstaltung.bezeichnung,
			tbl_lehrveranstaltung.bezeichnung_english,
			tbl_lehrveranstaltung.studiengang_kz,
			tbl_lehrveranstaltung.semester,
			tbl_lehrveranstaltung.orgform_kurzbz,
			tbl_lehrveranstaltung.lehrveranstaltung_template_id,
			UPPER(TRIM(CONCAT(stg.typ, stg.kurzbz))) AS "stgKurzbz",
			stg.kurzbzlang AS "stgKurzbzlang",
		');
		$result = $this->_ci->LehrveranstaltungModel->load($lehrveranstaltung_id);
		$data = hasData($result) ? getData($result)[0] : [];

		// LV bezeichnung
		$data->bezeichnung = getUserLanguage() === 'English'
			? $data->bezeichnung_english
			: $data->bezeichnung;

		return $data;
	}
	public function getLanguageIndex()
	{
		$this->_ci->load->model('system/Sprache_model', 'SpracheModel');

		$defaultIdx = 1;

		$userLang = getUserLanguage();
		$this->_ci->SpracheModel->addSelect('index');
		$result = $this->_ci->SpracheModel->loadWhere(array('sprache' => $userLang));

		return hasData($result) ? getData($result)[0]->index : $defaultIdx;
	}
}