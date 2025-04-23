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

class EvaluierungLib
{
	private $_ci; // Code igniter instance
	public function __construct()
	{
		$this->_ci =& get_instance();
	}

	/**
	 * Get Lehrveranstaltung Infos and its lecturers.
	 *
	 * @param $lehrveranstaltung_id
	 * @param $studiensemester_kurzbz
	 * @return array|mixed
	 */
	public function getLvInfo($lehrveranstaltung_id, $studiensemester_kurzbz)
	{
		$this->_ci->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');
		$this->_ci->load->library('extensions/FHC-Core-Evaluierung/EvaluierungLib');

		// Get LV
		$this->_ci->LehrveranstaltungModel->addSelect('ects, bezeichnung, bezeichnung_english');
		$result = $this->_ci->LehrveranstaltungModel->load($lehrveranstaltung_id);
		$data = hasData($result) ? getData($result)[0] : [];

		// Append bezeichnung by user language to result array
		$userLang = getUserLanguage();
		$data->bezeichnung_by_language = $userLang === 'English'
			? $data->bezeichnung_english
			: $data->bezeichnung;

		// Get Lecturers by LV
		$result = $this->_ci->LehrveranstaltungModel->getLecturersByLv($studiensemester_kurzbz, $lehrveranstaltung_id);

		// Append Lecturers to result array
		$data->lehrende = hasData($result) ? getData($result) : [];

		return $data;
	}

	/**
	 * Get the Users Language Index.
	 *
	 * @return int
	 */
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