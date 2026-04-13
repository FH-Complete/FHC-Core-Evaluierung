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

	public function isLvLeitung($uid, $lehrveranstaltung_id, $studiensemester_kurzbz)
	{
		// Check for LV-Leitung
		$this->_ci->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');
		$result = $this->_ci->LehrveranstaltungModel->getLvLeitung($lehrveranstaltung_id, $studiensemester_kurzbz);

		// If LV-Leitung exist
		if (hasData($result))
		{
			// check if user is LV-Leitung
			return getData($result)[0]->mitarbeiter_uid === $uid;
		}
		else
			return false;
	}

	public function isKFL($uid, $lehrveranstaltung_id)
	{
		$this->_ci->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');
		$result = $this->_ci->LehrveranstaltungModel->load($lehrveranstaltung_id);
		$lv = hasData($result) ? getData($result)[0] : null;

		$this->_ci->load->model('person/Benutzerfunktion_model', 'BenutzerfunktionModel');
		$result = $this->_ci->BenutzerfunktionModel->getKFLByUID($uid);

		if (hasData($result))
		{
			$leitungen = getData($result);

			return in_array($lv->oe_kurzbz, array_column($leitungen, 'oe_kurzbz'));
		}

		return false;
	}

	public function isSTGL($uid, $lehrveranstaltung_id)
	{
		$this->_ci->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');
		$result = $this->_ci->LehrveranstaltungModel->load($lehrveranstaltung_id);
		$lv = hasData($result) ? getData($result)[0] : null;

		$this->_ci->load->model('organisation/Studiengang_model', 'StudiengangModel');
		$result = $this->_ci->StudiengangModel->getLeitung($lv->studiengang_kz);

		if (hasData($result))
		{
			$leitungen = getData($result);

			return in_array($uid, array_column($leitungen, 'uid'));
		}

		return false;
	}

	/**
	 * Get Lehrende depending on Gesamt or GruppenEvaluierung.
	 * Add optionale Lehrende for reflexion.
	 *
	 * @param $lve
	 * @param $lveLv
	 * @param $lvLeitung
	 * @param $addOptionale
	 * @return array
	 */
	public function getLehrendeByLve($lve, $lveLv, $lvLeitung = null, $addOptionale = false)
	{
		$this->_ci->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');
		$this->_ci->load->model('education/Lehreinheitmitarbeiter_model', 'LehreinheitmitarbeiterModel');
		if (is_null($lvLeitung))
		{
			$result = $this->_ci->LehrveranstaltungModel->getLvLeitung(
				$lveLv->lehrveranstaltung_id,
				$lveLv->studiensemester_kurzbz
			);
			$lvLeitung = hasData($result) ? getData($result)[0] : null;
		}

		if ($lveLv->lv_aufgeteilt && is_int($lve->lehreinheit_id)) // Gruppen Evaluierung
		{
			// Aufgrund Gruppen Logik sollte hier nur ein Lektor zurückgegeben werden
			$result = $this->_ci->LehreinheitmitarbeiterModel->getLektorenByLe($lve->lehreinheit_id);	// Must be only one because of Gruppen logic
			$lektoren = hasData($result) ? array(getData($result)[0]) : [];	// todo Fallback erster im array noch ändern
		}
		else // Gesamt-LV
		{
			if ($addOptionale === true)
			{
				// Alle Lektoren (LV-Leitung Pflicht, andere optional)
				$result = $this->_ci->LehrveranstaltungModel->getLecturersByLv(
					$lveLv->studiensemester_kurzbz,
					$lveLv->lehrveranstaltung_id
				);

				$lektoren = hasData($result) ? getData($result) : [];

				// LV-Leitung ergänzen, falls nicht Lehrender ist
				if (!in_array($lvLeitung->mitarbeiter_uid, array_column($lektoren, 'uid')))
				{
					$lektoren[]= $lvLeitung;
				}
			}
			else
			{
				// Reflexion nur für LV-Leitung verpflichtend
				$lektoren = array($lvLeitung);
			}
		}

		// Result data vereinheitlichen
		$result = [];
		foreach ($lektoren as $lektor)
		{
			$isLvLeitung = null;
			if(isset($lektor->lehrfunktion_kurzbz))
			{
				$isLvLeitung = $lektor->lehrfunktion_kurzbz === 'LV-Leitung' ? true : false;
			}
			elseif (isset($lektor->lvleiter))
			{
				$isLvLeitung = $lektor->lvleiter;
			}
			$result[]= (object) [
				'vorname' => $lektor->vorname,
				'nachname' => $lektor->nachname,
				'uid' => isset($lektor->mitarbeiter_uid) ? $lektor->mitarbeiter_uid : $lektor->uid,
				'isLvLeitung' => $isLvLeitung
			];
		}

		return $result;
	}

	public function isZeitfensterOffen($startDate, $endDate)
	{
		// Start ab Mitternacht
		$start = is_string($startDate) ? new DateTime($startDate) : $startDate;
		$start->setTime(0, 0, 0);

		// Ende bis Mitternacht
		$ende = is_string($endDate) ? new DateTime($endDate) : $endDate;
		$ende->setTime(23, 59, 59);

		$now = new DateTime();

		return $now >= $start && $now <= $ende;
	}

	public function calculateReflexionZeitfenster($lveEndezeit)
	{
		$this->_ci->load->config('extensions/FHC-Core-Evaluierung/initiierung');

		$endedatum = new DateTime($lveEndezeit);

		$zeitfensterVon = clone $endedatum;
		$zeitfensterVon->modify('+1 day');	// Endedatum noch für Evaluierung offen, deshalb +1

		$zeitfensterBis = clone ($zeitfensterVon);
		$zeitfensterBis->modify($this->_ci->config->item('reflexionZeitfensterDauer'));

		return [
			'von' => $zeitfensterVon,
			'bis' => $zeitfensterBis
		];
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
			tbl_lehrveranstaltung.sprache,
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

	// TODO iMedian formula only for testing. NEEDS TO BE DESCRIBED AND VERIFIED BY QM!!!
	 /**
	  * Calculate interpolated Median from ratings and frequencies
	  * @param array $werte
	  * @param array $frequencies
	  * @return float|null
	  */
	public function getInterpolMedian($werte, $frequencies)
	{
		if (!is_array($werte) || !is_array($frequencies)) return null;
		if (count($werte) !== count($frequencies)) return null;

		$total = array_sum($frequencies);
		if ($total === 0) return 0;

		$cumFreq = 0;
		$medianIndex = 0;
		$medianPos = $total / 2;

		for ($i = 0; $i < count($frequencies); $i++) {
			$cumFreq += $frequencies[$i];
			if ($cumFreq >= $medianPos) {
				$medianIndex = $i;
				break;
			}
		}

		$F = array_sum(array_slice($frequencies, 0, $medianIndex));
		$f = $frequencies[$medianIndex];
		$L = $werte[$medianIndex] -0.5; // lower bound
		$w = 1;

		return round($L + (($medianPos - $F) / $f) * $w, 2);
	}

	// TODO iMedian formula only for testing. NEEDS TO BE DESCRIBED AND VERIFIED BY QM!!!
	/**
	 * Calculate Hodges-Lehmann estimator (HLE) for a single question
	 *
	 * @param array $werte         	Antwort-Werte (1-5)
	 * @param array $frequencies	Frequencies of selected Antwort-Werte
	 * @return float|null          	Hodges–Lehmann estimator (rounded), or null
	 */
	public function getHodgesLehmannEstimator($werte, $frequencies)
	{
		$n = count($werte);
		if ($n !== count($frequencies)) return null;

		$pairs = [];

		// Generate all pairs (z_i + z_j)/2 with i ≤ j
		for ($i = 0; $i < $n; $i++) {
			for ($j = $i; $j < $n; $j++) {
				$mean = ($werte[$i] + $werte[$j]) / 2;

				// Weight = number of times this pair occurs
				$weight = ($i === $j)
					? ($frequencies[$i] * ($frequencies[$i] + 1)) / 2	// self-pairs
					: $frequencies[$i] * $frequencies[$j];				// cross-pairs

				if ($weight > 0) {
					$pairs[] = ['value' => $mean, 'weight' => $weight];
				}
			}
		}

		if (empty($pairs)) return null;

		// Sort
		usort($pairs, function($a, $b) {
			if ($a['value'] == $b['value']) return 0;
			return ($a['value'] < $b['value']) ? -1 : 1;
		});

		// Weighted median
		$totalWeight = array_sum(array_column($pairs, 'weight'));
		$medianPos   = $totalWeight / 2;

		$cumWeight = 0;
		foreach ($pairs as $pair) {
			$cumWeight += $pair['weight'];
			if ($cumWeight >= $medianPos) {
				return round($pair['value'], 2);
			}
		}

		return null;
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