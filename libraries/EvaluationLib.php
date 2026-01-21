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