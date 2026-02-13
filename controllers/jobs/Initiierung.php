<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Initiierung extends JOB_Controller
{
	private $_ci; // Code igniter instance

	/**
	 * Constructor
	 */
	public function __construct()
	{
		/** @noinspection PhpUndefinedClassConstantInspection */
		parent::__construct();

		$this->_ci =& get_instance();
	}

	/**
	 * Job to insert Lehrveranstaltungen for a particular Studiensemester into the tbl_lvevaluierung_lehrveranstaltung.
	 * Only Lehrveranstaltungen that are marked for evaluation and not yet present in target table will be inserted.
	 *
	 * @return void
	 */
	public function initEvaluierungForLehrveranstaltungen($studiensemester_kurzbz = null)
	{
		if (isEmptyString($studiensemester_kurzbz))
		{
			$this->logError('Missing param Studiensemester');
			exit;
		}

		$this->logInfo('Start Job initEvaluierungForLehrveranstaltungen for '. $studiensemester_kurzbz);

		$this->_ci->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungLehrveranstaltung_model', 'LvevaluierungLehrveranstaltungModel');

		// Only for pilotphase
		if (defined('CIS_EVALUIERUNG_ANZEIGEN_STG') && CIS_EVALUIERUNG_ANZEIGEN_STG )
		{
			$stgs = unserialize(CIS_EVALUIERUNG_ANZEIGEN_STG);
			$result = $this->_ci->LvevaluierungLehrveranstaltungModel->insertLehrveranstaltungenFor($studiensemester_kurzbz, $stgs);
		}
		else
		{
			$result = $this->_ci->LvevaluierungLehrveranstaltungModel->insertLehrveranstaltungenFor($studiensemester_kurzbz);
		}

		if (isError($result))
		{
			$this->logError(getError($result));
		}
		else
		{
			$this->logInfo(getData($result));
		}

		$this->logInfo('End Job initEvaluierungForLehrveranstaltungen for '. $studiensemester_kurzbz);
	}


	/**
	 * Job to inform Lecturers or LV-Leitung to set Evaluation Time Range
	 *
	 * @return void
	 */
	public function sendEvaluationStartInfo($studiensemester_kurzbz = null)
	{
		if (isEmptyString($studiensemester_kurzbz))
		{
			$this->logError('Missing param Studiensemester');
			exit;
		}

		$this->logInfo('Start Job sendEvaluationStartInfo for '. $studiensemester_kurzbz);

		$this->_ci->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungLehrveranstaltung_model', 'LvevaluierungLehrveranstaltungModel');
		$this->_ci->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');
		$this->_ci->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungPrestudent_model', 'LvevaluierungPrestudentModel');
		
		$this->load->library('extensions/FHC-Core-Evaluierung/InitiierungLib');

		$result = $this->_ci->LvevaluierungLehrveranstaltungModel->getLveLvsByStSem($studiensemester_kurzbz);
		
		if (isError($result))
		{
			$this->logError(getError($result));
		}
		else
		{
			$maildata = array();

			$data = getData($result);
			foreach($data as $row)
			{
				if($row->lv_aufgeteilt)
				{
					// Bei Gruppen Evaluierung ergeht Info an die jeweiligen LektorInnen
					$resultLES = $this->LvevaluierungLehrveranstaltungModel->getLveLvWithLesAndGruppenById($row->lvevaluierung_lehrveranstaltung_id);
					if(isSuccess($resultLES) && hasData($resultLES))
					{
						$dataLES = getData($resultLES);

						// Group data by LE and add data
						$groupedByLe = $this->initiierunglib->groupByLeAndAddData($dataLES, $row->lvevaluierung_lehrveranstaltung_id);
						
						foreach($groupedByLe as $rowle)
						{
							foreach($rowle->lektoren as $rowlkt)
							{
								if(!isset($maildata[$rowlkt['mitarbeiter_uid']]))
								{
									$maildata[$rowlkt['mitarbeiter_uid']]['lehrendeninfo']['fullname'] = $rowlkt['fullname'];
									
								}

								if(!isset($maildata[$rowlkt['mitarbeiter_uid']]['lv'][$rowle->lehrveranstaltung_id]))
								{
									$maildata[$rowlkt['mitarbeiter_uid']]['lv'][$rowle->lehrveranstaltung_id]['lv_bezeichnung'] = $rowle->bezeichnung;
									$maildata[$rowlkt['mitarbeiter_uid']]['lv'][$rowle->lehrveranstaltung_id]['stg_bezeichnung'] = $row->stg_bezeichnung;
									$maildata[$rowlkt['mitarbeiter_uid']]['lv'][$rowle->lehrveranstaltung_id]['gesamt']=false;
								}
							}
						}
					}
					else
					{
						$this->logError('Laden der Personen einer Evaluierung fehlgeschlagen');
					}
				}
				else
				{
					// Bei Gesamt Evaluierung ergeht Info an die LV Leitung
					$result_lkt = $this->_ci->LehrveranstaltungModel->getLecturersByLv($studiensemester_kurzbz, $row->lehrveranstaltung_id);
					if(isSuccess($result_lkt) && hasData($result_lkt))
					{
						$dataLektor = getData($result_lkt);

						foreach($dataLektor as $rowLektor)
						{
							if($rowLektor->lvleiter)
							{
								if(!isset($maildata[$rowLektor->uid]))
								{
									$maildata[$rowLektor->uid]['lehrendeninfo']['fullname'] = $rowLektor->vorname.' '.$rowLektor->nachname;
								}

								$maildata[$rowLektor->uid]['lv'][$row->lehrveranstaltung_id]['lv_bezeichnung'] = $row->lv_bezeichnung;
								$maildata[$rowLektor->uid]['lv'][$row->lehrveranstaltung_id]['stg_bezeichnung'] = $row->stg_bezeichnung;
								$maildata[$rowLektor->uid]['lv'][$row->lehrveranstaltung_id]['gesamt'] = true;
							}
						}
					}
				}
			}
			
			foreach($maildata as $uid=>$row)
			{
				$coursetable = '
				<table style="width: 100%; border-collapse: collapse; margin-bottom: 25px;">
					<thead>
						<tr style="background-color: #eee; text-align: left;">
							<th style="padding: 10px; border: 1px solid #ddd; font-size: 13px; width: 20%;">Studiengang</th>
							<th style="padding: 10px; border: 1px solid #ddd; font-size: 13px;">Lehrveranstaltung</th>
							<th style="padding: 10px; border: 1px solid #ddd; font-size: 13px;">Art</th>
						</tr>
					</thead>
					<tbody>
				';
				
				foreach($row['lv'] as $lvid=>$coursecontent)
				{
					$coursetable .= '
					<tr>
						<td style="padding: 10px; border: 1px solid #ddd; font-size: 13px; vertical-align: top;">'.$coursecontent['stg_bezeichnung'].'</td>
						<td style="padding: 10px; border: 1px solid #ddd; font-size: 13px;">
							<strong>'.$coursecontent['lv_bezeichnung'].'</strong>
						</td>
						<td style="padding: 10px; border: 1px solid #ddd; font-size: 13px; vertical-align: top;">'.($coursecontent['gesamt']?'Gesamt-LV':'Gruppenbasis').'</td>
					</tr>
					';
				}
				$coursetable .= '</tbody></table>';

				$url  = CIS_ROOT . 'index.ci.php/extensions/FHC-Core-Evaluierung/Initiierung';

				$data = [
					'fullname' => $row['lehrendeninfo']['fullname'],
					'coursetable' => $coursetable,
					'url'=> $url
				];

				$mailSent = sendSanchoMail(
					'LVE_Mail_EvaluationStartInfo',
					$data,
					$uid.'@'.DOMAIN,
					'Evaluierungszeitraum kann jetzt festgelegt werden'
				);

				if ($mailSent)
				{
					$this->logInfo('sendEvaluationStartInfo to '. $uid);
				}
				else
				{
					$this->logError('Failed to sendEvaluationStartInfo to '. $uid);
				}
			}
		}

		$this->logInfo('End Job sendEvaluationStartInfo for '. $studiensemester_kurzbz);
	}
}
