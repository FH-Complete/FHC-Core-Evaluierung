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
	 * Validate Antworten (check if Pflicht, Skip not answered).
	 * @param $antworten
	 * @return void Return Antworten that must be inserted. Return error if validation failed.
	 */
	public function validateAntworten($antworten)
	{
		$this->_ci->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogenFrage_model', 'LvevaluierungFragebogenFrageModel');

		$insertItems = [];

		if ($antworten) {
			foreach ($antworten as $antwort)
			{
				// Get Frage
				$result = $this->_ci->LvevaluierungFragebogenFrageModel->load($antwort['lvevaluierung_frage_id']);
				$frage = hasData($result) ? getData($result)[0] : null;

				// Check if Frage MUST be answered
				if ($frage->verpflichtend)
				{
					//Return if it was not answered
					if ($frage->typ === 'singleresponse')
					{
						if (is_null($antwort['lvevaluierung_frage_antwort_id'])) return error('Pflichtantwort fehlt');
					}

					if ($frage->typ === 'text')
					{
						if (is_null($antwort['antwort'])) return error('Pflichtantwort fehlt');
					}
				}

				// Skip if no Antwort at all
				if (is_null($antwort['lvevaluierung_frage_antwort_id']) && is_null($antwort['antwort'])) {
					continue;
				}

				// Store validated Antworten to be saved
				$insertItems[] = $antwort;
			}

			return success($insertItems);
		}
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