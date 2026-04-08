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
			$gruppe_sent_users = array();
			$gesamt_sent_users = array();

			$link  = CIS_ROOT . 'index.ci.php/extensions/FHC-Core-Evaluierung/Initiierung';

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
								if(!in_array($rowlkt['mitarbeiter_uid'], $gruppe_sent_users))
								{
									$gruppe_sent_users[] = $rowlkt['mitarbeiter_uid'];
									$uid = $rowlkt['mitarbeiter_uid'];
									//echo "\nGruppe Mail to ".$rowlkt['mitarbeiter_uid'];
								
									$data = [
										'vorname' => $rowlkt['vorname'],
										'nachname' => $rowlkt['nachname'],
										'link'=> $link
									];

									$mailSent = sendSanchoMail(
										'LVE_LEHR_TEXT_1',
										$data,
										$uid.'@'.DOMAIN,
										'LV-Evaluation auf Gruppen-Ebene – Evaluierungszeitfenster festlegen',
										'sancho_header_lvevaluierung.jpg',
										'sancho_footer_lvevaluierung.jpg'
									);

									if ($mailSent)
									{
										$this->logInfo('LVE_LEHR_TEXT_1 to '. $uid);
									}
									else
									{
										$this->logError('Failed to send LVE_LEHR_TEXT_1 to '. $uid);
									}
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
								if(!in_array($rowLektor->uid, $gesamt_sent_users))
								{
									$gesamt_sent_users[] = $rowLektor->uid;
									//echo "\nGesamt Mail to ".$rowLektor->uid;
									$uid = $rowLektor->uid;

									$data = [
										'vorname' => $rowLektor->vorname,
										'nachname' => $rowLektor->nachname,
										'link'=> $link
									];

									$mailSent = sendSanchoMail(
										'LVE_LVL_TEXT_3',
										$data,
										$uid.'@'.DOMAIN,
										'LV-Evaluation auf Gesamt-Ebene – Evaluierungszeitfenster festlegen',
										'sancho_header_lvevaluierung.jpg',
										'sancho_footer_lvevaluierung.jpg'
									);

									if ($mailSent)
									{
										$this->logInfo('LVE_LVL_TEXT_3 to '. $uid);
									}
									else
									{
										$this->logError('Failed to send LVE_LVL_TEXT_3 to '. $uid);
									}
								}
							}
						}
					}
				}
			}
		}

		$this->logInfo('End Job sendEvaluationStartInfo for '. $studiensemester_kurzbz);
	}

	/**
	 * Job to remind Lecturers or LV-Leitung one day before Evaluierung starts.
	 *
	 * @return void
	 */
	public function sendEvaluierungStartReminder(){

		$this->logInfo('Start Job sendEvaluierungStartReminder');

		$this->_ci->load->model('extensions/FHC-Core-Evaluierung/Lvevaluierung_model', 'LvevaluierungModel');
		$this->_ci->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');
		$this->_ci->load->model('education/Lehreinheitmitarbeiter_model', 'LehreinheitmitarbeiterModel');

		$this->load->library('extensions/FHC-Core-Evaluierung/InitiierungLib');

		// Get all Evaluierungen that start tomorrow
		$result = $this->_ci->LvevaluierungModel->getLvesStartingIn('+1 day');

		if (isError($result))
		{
			$this->logError(getError($result));
		}
		else
		{
			$gruppe_sent_users = array();
			$gesamt_sent_users = array();

			$link  = CIS_ROOT . 'index.ci.php/extensions/FHC-Core-Evaluierung/Initiierung';

			$data = hasData($result) ? getData($result) : [];

			foreach($data as $row)
			{
//				var_dump($row->lvevaluierung_id);
//				var_dump($row->lvevaluierung_lehrveranstaltung_id);
//				var_dump($row->lv_bezeichnung);
//				var_dump($row->lv_aufgeteilt ? 'Gruppe; Mail an Lektor' : 'Gesamt-LV; Mail an LVLeitungen');

				// Gruppen Evaluierung
				if($row->lv_aufgeteilt)
				{
					// Bei Gruppen Evaluierung ergeht Info an die jeweiligen LektorInnen
					$result = $this->_ci->LehreinheitmitarbeiterModel->getLektorenByLe($row->lehreinheit_id);
					if (hasData($result))
					{
						$lektoren = getData($result);

						foreach($lektoren as $lektor)
						{
							if (!in_array($lektor->mitarbeiter_uid, $gruppe_sent_users))
							{
								$gruppe_sent_users[] = $lektor->mitarbeiter_uid;
								$uid = $lektor->mitarbeiter_uid;
								//echo "\nGruppe Mail to ".$lektor->mitarbeiter_uid."\n";

								$data = [
									'vorname' => $lektor->vorname,
									'nachname' => $lektor->nachname,
									'lv_bezeichnung' => $row->lv_bezeichnung,
									'startzeit' => (new DateTime($row->startzeit))->format("d.m.Y"),
									'endezeit' => (new DateTime($row->endezeit))->format("d.m.Y"),
									'link'=> $link
								];

								$mailSent = sendSanchoMail(
									'LVE_LEHR_TEXT_2',
									$data,
									$uid.'@'.DOMAIN,
									'LV-Evaluation auf Gruppen-Ebene – Evaluierungszeitfenster startet bald',
									'sancho_header_lvevaluierung.jpg',
									'sancho_footer_lvevaluierung.jpg'
								);

								if ($mailSent)
								{
									$this->logInfo('LVE_LEHR_TEXT_2 to '. $uid);
								}
								else
								{
									$this->logError('Failed to send LVE_LEHR_TEXT_2 to '. $uid);
								}
							}
						}
					}
					else
					{
						$this->logError('Laden der Lektoren der Evaluierungs-Lehreinheit '. $row->lehreinheit_id. ' fehlgeschlagen');
					}
				}
				// Gesamt-LV Evaluierung
				else
				{
					// Bei Gesamt Evaluierung ergeht Info an die LV Leitung
					$result = $this->_ci->LehrveranstaltungModel->getLvLeitung($row->lehrveranstaltung_id, $row->studiensemester_kurzbz);
					if(hasData($result))
					{
						$lvLeitungen = getData($result);

						foreach($lvLeitungen as $lvLeitung)
						{
							if (!in_array($lvLeitung->mitarbeiter_uid, $gesamt_sent_users))
							{
								$gesamt_sent_users[] = $lvLeitung->mitarbeiter_uid;
								echo "\nGesamt Mail to ".$lvLeitung->mitarbeiter_uid;
								$uid = $lvLeitung->mitarbeiter_uid;

								$data = [
									'vorname' => $lvLeitung->vorname,
									'nachname' => $lvLeitung->nachname,
									'lv_bezeichnung' => $row->lv_bezeichnung,
									'startzeit' => (new DateTime($row->startzeit))->format("d.m.Y"),
									'endezeit' => (new DateTime($row->endezeit))->format("d.m.Y"),
									'link'=> $link
								];

								$mailSent = sendSanchoMail(
									'LVE_LVL_TEXT_4',
									$data,
									$uid.'@'.DOMAIN,
									'LV-Evaluation auf Gesamt-Ebene – Evaluierungszeitfenster startet bald',
									'sancho_header_lvevaluierung.jpg',
									'sancho_footer_lvevaluierung.jpg'
								);

								if ($mailSent)
								{
									$this->logInfo('LVE_LVL_TEXT_4 to '. $uid);
								}
								else
								{
									$this->logError('Failed to send LVE_LVL_TEXT_4 to '. $uid);
								}

						}
						}
					}
				}
			}
		}

		$this->logInfo('End Job sendEvaluierungStartReminder');
	}

	/**
	 * Job to remind Lecturers or LV-Leitung one day before Evaluierung starts.
	 *
	 * @return void
	 */
	public function sendReflexionStartInfo(){

		$this->logInfo('Start Job sendReflexionStartInfo');

		$this->_ci->load->model('extensions/FHC-Core-Evaluierung/Lvevaluierung_model', 'LvevaluierungModel');
		$this->_ci->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');
		$this->_ci->load->model('education/Lehreinheitmitarbeiter_model', 'LehreinheitmitarbeiterModel');

		$this->load->library('extensions/FHC-Core-Evaluierung/InitiierungLib');
		$this->load->library('extensions/FHC-Core-Evaluierung/EvaluationLib');

		// Get all Evaluierungen that ended yesterday
		$result = $this->_ci->LvevaluierungModel->getLvesEndingIn('-1 day');

		if (isError($result))
		{
			$this->logError(getError($result));
		}
		else
		{
			$gruppe_sent_users = array();
			$gesamt_sent_users = array();

			$data = hasData($result) ? getData($result) : [];

			foreach($data as $row)
			{
				//var_dump($row); // check studiengangsbezeichnung
//				var_dump($row->lvevaluierung_id);
//				var_dump($row->lvevaluierung_lehrveranstaltung_id);
//				var_dump($row->lv_bezeichnung);
//				var_dump($row->startzeit);
//				var_dump($row->endezeit);
//				var_dump($row->lv_aufgeteilt ? 'Gruppe; Mail an Lektor' : 'Gesamt-LV; Mail an LVLeitungen');


				// Get Start- and Endedatum of Reflexionszeitraum
				$zeitfenster = $this->_ci->evaluationlib->calculateReflexionZeitfenster($row->endezeit);
				$reflexionBis = $zeitfenster['bis']->format("d.m.Y");
				var_dump($zeitfenster['bis']->format("d.m.Y"));

				// Link zu Ergebnissen der LV
				$link  = CIS_ROOT . 'index.ci.php/extensions/FHC-Core-Evaluierung/evaluation/Evaluation/?lvevaluierung_id='. $row->lvevaluierung_id;

				// Gruppen Evaluierung
				if($row->lv_aufgeteilt)
				{
					// Bei Gruppen Evaluierung ergeht Info an die jeweiligen LektorInnen
					$result = $this->_ci->LehreinheitmitarbeiterModel->getLektorenByLe($row->lehreinheit_id);
					if (hasData($result))
					{
						$lektoren = getData($result);

						foreach($lektoren as $lektor)
						{
							if (!in_array($lektor->mitarbeiter_uid, $gruppe_sent_users))
							{
								$gruppe_sent_users[] = $lektor->mitarbeiter_uid;
								$uid = $lektor->mitarbeiter_uid;
//								echo "\nGruppe Mail to ".$lektor->mitarbeiter_uid."\n";

								$data = [
									'vorname' => $lektor->vorname,
									'nachname' => $lektor->nachname,
									'lv_bezeichnung' => $row->lv_bezeichnung,
									'stg_bezeichnung' => $row->stg_bezeichnung,
									'reflexion_bis' => $reflexionBis,
									'link'=> $link
								];

								$mailSent = sendSanchoMail(
									'LVE_LEHR_TEXT_3B_Pflicht',
									$data,
									$uid.'@'.DOMAIN,
									'LV-Evaluation auf Gruppen-Ebene: Ergebnisse für '. $row->lv_bezeichnung. ' aus '. $row->stg_typ_kurzbz. ' liegen vor - LV-Reflexion bis '. $reflexionBis,
									'sancho_header_lvevaluierung.jpg',
									'sancho_footer_lvevaluierung.jpg'
								);

								if ($mailSent)
								{
									$this->logInfo('LVE_LEHR_TEXT_3B_Pflicht to '. $uid);
								}
								else
								{
									$this->logError('Failed to send LVE_LEHR_TEXT_3B_Pflicht to '. $uid);
								}
							}
						}
					}
					else
					{
						$this->logError('Laden der Lektoren der Evaluierungs-Lehreinheit '. $row->lehreinheit_id. ' fehlgeschlagen');
					}
				}
				// Gesamt-LV Evaluierung
				else
				{
					// Bei Gesamt Evaluierung ergeht Info an die LV Leitung für die verpflichtend durchzuführende LV-Reflexion
					$result = $this->_ci->LehrveranstaltungModel->getLvLeitung($row->lehrveranstaltung_id, $row->studiensemester_kurzbz);
					if(hasData($result))
					{
						$lvLeitungen = getData($result);

						foreach($lvLeitungen as $lvLeitung)
						{
							if (!in_array($lvLeitung->mitarbeiter_uid, $gesamt_sent_users))
							{
								$gesamt_sent_users[] = $lvLeitung->mitarbeiter_uid;
								echo "\nGesamt Mail to ".$lvLeitung->mitarbeiter_uid;
								$uid = $lvLeitung->mitarbeiter_uid;

								$data = [
									'vorname' => $lvLeitung->vorname,
									'nachname' => $lvLeitung->nachname,
									'lv_bezeichnung' => $row->lv_bezeichnung,
									'stg_bezeichnung' => $row->stg_bezeichnung,
									'reflexion_bis' => $reflexionBis,
									'link'=> $link
								];

								$mailSent = sendSanchoMail(
									'LVE_LVL_TEXT_5',
									$data,
									$uid.'@'.DOMAIN,
									'LV-Evaluation auf Gesamt-Ebene: Ergebnisse für '. $row->lv_bezeichnung. ' aus '. $row->stg_typ_kurzbz. ' liegen vor – LV-Reflexion bis '. $reflexionBis,
									'sancho_header_lvevaluierung.jpg',
									'sancho_footer_lvevaluierung.jpg'
								);

								if ($mailSent)
								{
									$this->logInfo('LVE_LVL_TEXT_5 to '. $uid);
								}
								else
								{
									$this->logError('Failed to send LVE_LVL_TEXT_5 to '. $uid);
								}

							}
						}
					}

					// Bei Gesamt Evaluierung ergeht Info an alle Lehrenden der LV für die optionale durchzuführende LV-Reflexion
					$result = $this->_ci->LehrveranstaltungModel->getLecturersByLv(
						$row->studiensemester_kurzbz,
						$row->lehrveranstaltung_id
					);

					if(hasData($result))
					{
						$lektoren = getData($result);
						var_dump('lektoren');
						var_dump($lektoren);

						foreach($lektoren as $lektor)
						{
							if (!in_array($lektor->uid, $gesamt_sent_users))
							{
								$gesamt_sent_users[] = $lektor->uid;
								echo "\nGesamt Mail to ".$lektor->uid;
								$uid = $lektor->uid;

								$data = [
									'vorname' => $lektor->vorname,
									'nachname' => $lektor->nachname,
									'lv_bezeichnung' => $row->lv_bezeichnung,
									'stg_bezeichnung' => $row->stg_bezeichnung,
									'reflexion_bis' => $reflexionBis,
									'link'=> $link
								];

								$mailSent = sendSanchoMail(
									'LVE_LEHR_TEXT_3A_Optional',
									$data,
									$uid.'@'.DOMAIN,
									'LV-Evaluation auf Gesamt-Ebene: Ergebnisse für '. $row->lv_bezeichnung. ' aus '. $row->stg_typ_kurzbz. ' liegen vor - optionale LV-Reflexion bis '. $reflexionBis,
									'sancho_header_lvevaluierung.jpg',
									'sancho_footer_lvevaluierung.jpg'
								);

								if ($mailSent)
								{
									$this->logInfo('LVE_LEHR_TEXT_3A_Optional to '. $uid);
								}
								else
								{
									$this->logError('Failed to send LVE_LEHR_TEXT_3A_Optional to '. $uid);
								}
							}
						}
					}
				}
			}
//			var_dump('gesamt sent users:');
//			var_dump($gesamt_sent_users);
		}

		$this->logInfo('End Job sendReflexionStartInfo');
	}
}
