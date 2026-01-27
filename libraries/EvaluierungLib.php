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

		// Add Studiensemester
		$data->studiensemester_kurzbz = $studiensemester_kurzbz;

		return $data;
	}

	/**
	 * Calculates the maximal Endezeit.
	 * Maximal Endezeit = Startzeit + Dauer + Buffer for request retry handling
	 *
	 * @param $lvevaluierung_code_id
	 * @return string
	 * @throws DateMalformedStringException
	 */
	public function getMaxEndezeit($lvevaluierung_code_id)
	{
		// Get Evaluierung Startzeit and Dauer
		$this->_ci->LvevaluierungCodeModel->addSelect('tbl_lvevaluierung_code.startzeit');
		$this->_ci->LvevaluierungCodeModel->addSelect('dauer');
		$this->_ci->LvevaluierungCodeModel->addJoin('extension.tbl_lvevaluierung', 'lvevaluierung_id');
		$result = $this->_ci->LvevaluierungCodeModel->load($lvevaluierung_code_id);
		$startzeit = hasData($result) ? getData($result)[0]->startzeit : null;
		$dauer = hasData($result) ? getData($result)[0]->dauer : null;
		if (is_null($dauer)) return false;

		// Extra time to be added for request retry handling
		$bufferMinutes = 10;

		$dtStartzeit = new DateTime($startzeit);

		// Convert Dauer (HH:MM:SS) to DateInterval
		list($h, $m, $s) = explode(':', $dauer);

		// Add buffer minutes
		$m += $bufferMinutes;

		$interval = new DateInterval("PT{$h}H{$m}M{$s}S");

		// Return maximale Endezeit
		return ($dtStartzeit->add($interval))->format("Y-m-d H:i:s");
	}

	/**
	 * Validate Antworten (check if Pflicht, Skip not answered).
	 * @param $antworten
	 * @return void Return Antworten that must be inserted. Return error if validation failed.
	 */
	public function validateAntworten($antworten)
	{
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
						if (is_null($antwort['lvevaluierung_frage_antwort_id'])) return error($this->_ci->p->t('fragebogen', 'pflichtantwortFehlt'));
					}

					if ($frage->typ === 'text')
					{
						if (is_null($antwort['antwort'])) return error($this->_ci->p->t('fragebogen', 'pflichtantwortFehlt'));
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

	// Validations and Checks
	//------------------------------------------------------------------------------------------------------------------
	/**
	 * Validate and get Evaluierung Code.
	 *
	 * @param $lvevaluierung_code_id
	 * @return mixed
	 */
	public function getValidatedLvevaluierungCode($lvevaluierung_code_id)
	{
		$result = $this->_ci->LvevaluierungCodeModel->loadWhere([
			'lvevaluierung_code_id' => $lvevaluierung_code_id
		]);

		if (!hasData($result))
		{
			return error($this->_ci->p->t('fragebogen', 'evaluierungCodeExistiertNicht'));
		}

		// On success
		return success(getData($result)[0]);
	}

	/**
	 * Validate and get Evaluierung.
	 *
	 * @param $lvevaluierung_id
	 * @return mixed
	 */
	public function getValidatedLvevaluierung($lvevaluierung_id)
	{
		// Check if Evaluierung ID exists
		$result = $this->_ci->LvevaluierungModel->load($lvevaluierung_id);

		if (!hasData($result))
		{
			return error($this->_ci->p->t('fragebogen', 'evaluierungNichtVerfuegbar'));
		}

		// On success
		return success(getData($result)[0]);
	}

	/**
	 * Validate and get Evaluierung-Lehrveranstaltung assignement.
	 *
	 * @param $lvevaluierung_lehrveranstaltung_id
	 * @return mixed
	 */
	public function getValidatedLvevaluierungLehrveranstaltung($lvevaluierung_lehrveranstaltung_id)
	{
		$result = $this->_ci->LvevaluierungLehrveranstaltungModel->load($lvevaluierung_lehrveranstaltung_id);

		if (!hasData($result))
		{
			return error($this->_ci->p->t('fragebogen', 'evaluierungNichtVerfuegbar'));
		}

		// On success
		return success(getData($result)[0]);
	}

	/**
	 * Validate and get Lehrveranstaltung.
	 *
	 * @param $lehrveranstaltung_id
	 * @return mixed
	 */
	public function getValidatedLehrveranstaltung($lehrveranstaltung_id)
	{
		$this->_ci->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');
		$result = $this->_ci->LehrveranstaltungModel->load($lehrveranstaltung_id);

		if (!hasData($result))
		{
			return error($this->_ci->p->t('fragebogen', 'evaluierungNichtVerfuegbar'));
		}

		// On success
		return success(getData($result)[0]);
	}

	/**
	 * Check if Evaluierung was already submitted.
	 *
	 * @param $lvevaluierungCode
	 * @return mixed
	 */
	public function checkIfEvaluierungAlreadySubmitted($lvevaluierungCode)
	{
		// Check if Evaluierung was already submitted
		if (!is_null($lvevaluierungCode->endezeit))
		{
			return error($this->_ci->p->t('fragebogen', 'evaluierungNichtVerfuegbar'));
		}

		return success(true);
	}

	/**
	 * Check if Evaluierung Period is valid (between startzeit and endezeit).
	 *
	 * @param $lvevaluierung
	 * @return mixed
	 */
	public function checkIfEvaluierungPeriodIsValid($lvevaluierung)
	{
		// Check if Evaluierung period is valid
		$now = (new DateTime())->format("Y-m-d H:i:s");

		if ($now < $lvevaluierung->startzeit)
		{
			return error($this->_ci->p->t('fragebogen', 'evaluierungPeriodeStartetErst', [
				'date' => $lvevaluierung->startzeit
			]));
		}

		if ($now > $lvevaluierung->endezeit)
		{
			return error($this->_ci->p->t('fragebogen', 'evaluierungPeriodeBeendet', [
				'date' => $lvevaluierung->endezeit
		]));
		}

		// On success
		return success(true);
	}

	/**
	 * Check if Lehrveranstaltung is evaluable.
	 *
	 * @param $lehrveranstaltung
	 * @return mixed
	 */
	public function checkIfLehrveranstaltungIsEvaluable($lehrveranstaltung)
	{
		// Check if Lehrveranstaltung should be evaluated
		if ($lehrveranstaltung->evaluierung === false)
		{
			return error($this->_ci->p->t('fragebogen', 'evaluierungNichtVerfuegbar'));
		}

		// On success
		return success(true);
	}

	/**
	 * Check the current time has exceeded the maximum allowed evaluation end time.
	 *
	 * @param $lvevaluierung_code_id
	 * @return mixed
	 * @throws DateMalformedStringException
	 */
	public function checkIfEvaluierungTimeExceeded($lvevaluierung_code_id)
	{
		// Get LV Evaluierung Dauer
		$this->_ci->LvevaluierungModel->addJoin('extension.tbl_lvevaluierung_code', 'lvevaluierung_id');
		$result = $this->_ci->LvevaluierungModel->loadWhere(['lvevaluierung_code_id' => $lvevaluierung_code_id]);
		$lve = hasData($result) ? getData($result)[0] : null;

		if (is_null($lve->dauer))
		{
			return success(true);	// No need to check if Evaluierung Time has exceeded
		}

		// Calculate maximale Endezeit (Startzeit + Dauer + Buffer for request retry handling)
		$maxEndezeit = $this->getMaxEndezeit($lvevaluierung_code_id);
		$now = (new DateTime())->format("Y-m-d H:i:s");

		// If Endezeit is valid
		if ($now > $maxEndezeit)
		{
			return error($this->_ci->p->t('fragebogen', 'evaluierungZeitAbgelaufen'));
		}

		return success(true);
	}
}