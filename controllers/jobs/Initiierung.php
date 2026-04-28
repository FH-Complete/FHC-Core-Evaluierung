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
		$this->_ci->load->helper('hlp_sancho_helper');

		$this->_ci->load->library('extensions/FHC-Core-Evaluierung/InitiierungLib');
		$this->_ci->load->config('extensions/FHC-Core-Evaluierung/initiierung');
		$this->_ci->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungZeitfenster_model', 'LvevaluierungZeitfensterModel');
		$this->_ci->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungFragebogen_model', 'LvevaluierungFragebogenModel');
		$this->_ci->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungLehrveranstaltung_model', 'LvevaluierungLehrveranstaltungModel');
		$this->_ci->load->model('extensions/FHC-Core-Evaluierung/Lvevaluierung_model', 'LvevaluierungModel');
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
	 * Create Evaluierungen entries for  Lehrveranstaltungen of given Studiensemester.
	 *
	 * Job will not run before Zeitfenster for switching Evaluierungsebene is closed.
	 * Job checks, if Evaluierungen already exist to prevent double entries.
	 *
	 * @param $studiensemester_kurzbz
	 * @return void
	 */
	public function createEvaluierungen($studiensemester_kurzbz)
	{
		if (isEmptyString($studiensemester_kurzbz))
		{
			$this->logError('Missing param Studiensemester');
			$this->logInfo('End Job createEvaluierungen for '. $studiensemester_kurzbz);
			return;
		}

		// Get Zeitfenster that allows LV-Leitung to switch Evaluierungsebene
		$result = $this->_ci->LvevaluierungZeitfensterModel->loadWhere([
			'typ' => 'typswitch',
			'studiensemester_kurzbz' => $studiensemester_kurzbz
		]);

		if (!hasData($result))
		{
			$this->logError('Missing Lvevaluierung Zeitfenster for '. $studiensemester_kurzbz);
			$this->logInfo('End Job createEvaluierungen for '. $studiensemester_kurzbz);
			return;
		}

		$zeitfensterEnde = new DateTime(getData($result)[0]->endedatum);
		$now   = new DateTime();

		// Go on only if Zeitfenster is closed
		if ($now > $zeitfensterEnde)
		{
			// Get LveLvs by Studiensemester
			$result = $this->_ci->LvevaluierungLehrveranstaltungModel->getLveLvsByStSem($studiensemester_kurzbz);

			if (isError($result))
			{
				$this->logError(getError($result));
			}

			$lveLvs = getData($result);
			$insertBatch = [];

			foreach ($lveLvs as $lveLv)
			{
				// Get valid Fragebogen
				$result = $this->_ci->LvevaluierungFragebogenModel->getActiveFragebogen(
					$lveLv->lehrveranstaltung_id,
					$lveLv->studiensemester_kurzbz
				);

				if (!hasData($result))
				{
					$this->logError('job createEvaluierungen: No Active Fragebogen for LV-ID '.$lveLv->lehrveranstaltung_id);
				}

				$fragebogenId = getData($result)[0]->fragebogen_id;

				// Get Lehreinheiten and Gruppen for LveLv
				$result = $this->_ci->LvevaluierungLehrveranstaltungModel->getLveLvWithLesAndGruppenById($lveLv->lvevaluierung_lehrveranstaltung_id);
				$data = hasData($result) ? getData($result) : [];

				// If Evaluierungsebene = Gruppe
				if ($lveLv->lv_aufgeteilt)
				{
					// Group data by LE
					$groupedByLe = $this->_ci->initiierunglib->groupByLeAndAddData($data, $lveLv->lvevaluierung_lehrveranstaltung_id);

					if ($this->_ci->config->item('filterLehreinheitenByUniqueLectorAndGruppen'))
					{
						// Keep grouped Lehreinheiten only if LEs have unique Lector and unique Gruppen combinations
						if (!$this->_ci->initiierunglib->hasUniqueLectorPerLehreinheit($data) ||
							$this->_ci->initiierunglib->hasHierarchicalDuplicateGruppen($data))
						{
							$groupedByLe = [];
						}
					}

					foreach ($groupedByLe as $item) {
						$insertBatch[] = [
							'lehreinheit_id' => $item->lehreinheit_id,
							'lvevaluierung_lehrveranstaltung_id' => $lveLv->lvevaluierung_lehrveranstaltung_id,
							'fragebogen_id' => $fragebogenId,
							'insertvon' => 'system'
						];
					}
				}
				// If Evaluierungsebene = Gesamt-LV
				else
				{
					// Group data by LV
					$groupedByLv = $this->_ci->initiierunglib->groupByLvAndAddData(
						$data,
						$lveLv->lvevaluierung_lehrveranstaltung_id,
						$lveLv->lehrveranstaltung_id,
						$lveLv->studiensemester_kurzbz
					);

					foreach ($groupedByLv as $item) {
						$insertBatch[] = [
							'lehreinheit_id' => NULL,
							'lvevaluierung_lehrveranstaltung_id' => $lveLv->lvevaluierung_lehrveranstaltung_id,
							'fragebogen_id' => $fragebogenId,
							'insertvon' => 'system'
						];
					}
				}
			}

			// Get existing LV Evaluierungen by Studiensemester
			$result =  $this->_ci->LvevaluierungModel->getLvesByStSem($studiensemester_kurzbz);

			// If Evaluierungen exist
			if (hasData($result))
			{
				$lves = getData($result);

				// Remove insert entries that already exist in Evaluierungen
				$filteredBatch = [];

				foreach ($insertBatch as $insert)
				{
					$found = false;

					foreach ($lves as $lve)
					{
						if (
							$insert['lehreinheit_id'] === $lve->lehreinheit_id &&
							$insert['lvevaluierung_lehrveranstaltung_id'] === $lve->lvevaluierung_lehrveranstaltung_id
						){
							$found = true;
							break;
						}
					}

					if (!$found)
					{
						$filteredBatch[] = $insert;
					}
				}

				$insertBatch = $filteredBatch;
			}


			// Insert LV Evaluierungen
			if (!empty($insertBatch))
			{
				$result = $this->_ci->LvevaluierungModel->insertBatch($insertBatch);

				if (isError($result))
				{
					$this->logError(getError($result));
					$this->logInfo('End Job createEvaluierungen for '. $studiensemester_kurzbz);
				}
				else
				{
					$this->logInfo('Created ' . count($insertBatch) . ' new Evaluierungen');
				}
			}
			else
			{
				$this->logInfo('No new Evaluierungen needed - all already present.');
			}

			$this->logInfo('End Job createEvaluierungen for '. $studiensemester_kurzbz);
		}
	}

	/**
	 * Sends evaluation codes for finished Lehrveranstaltungen of given Studiensemester if not already mailed.
	 *
	 * Job runs after last Unterrichtseinheit has passed and only processes evaluations without sent codes and if
	 * times are not set or endezeit has passed.
	 *
	 * Automatically sets start- and endzeit and sends mails to all unmailed students.
	 * @param $studiensemester_kurzbz
	 * @return void
	 */
	public function sendUnsentEvaluierungen($studiensemester_kurzbz)
	{
		if (isEmptyString($studiensemester_kurzbz))
		{
			$this->logError('Missing param Studiensemester');
			return;
		}

		// Get Evaluierungen by Studiensemester
		$result =  $this->_ci->LvevaluierungModel->getLvesByStSem($studiensemester_kurzbz);

		// If Evaluierungen exist
		if (hasData($result))
		{
			$data = getData($result);
			$now = new DateTime();
			$this->_ci->load->model('ressource/Stundenplan_model', 'StundenplanModel');

			// Foreach Evaluierung
			foreach ($data as $item)
			{
				// Get Stundenplantermine
				if ($item->lv_aufgeteilt)	// Gruppe
				{
					$result = $this->_ci->StundenplanModel->getTermineByLe($item->lehreinheit_id);
				}
				else	// Gesamt-LV
				{
					$result = $this->_ci->StundenplanModel->getTermineByLv(
						$item->lehrveranstaltung_id,
						$item->studiensemester_kurzbz
					);
				}

				if (isError($result))
				{
					$this->logError(getError($result));
					$this->logInfo('End Job sendUnsentEvaluations for '. $studiensemester_kurzbz);
					return;
				}

				// Stundenplantermine
				$termine = hasData($result) ? getData($result) : [];

				// Wenn keine Studenplantermine vorhanden --> ignorieren
				if (empty($termine)) continue;

				// Letzte Unterrichtseinheit
				$lastTermin = end($termine);
				$lastTerminDate = new DateTime($lastTermin->datum);
				$lastTerminDate->setTime(23, 59, 59); // Tagesende setzen, damit Folgecheck nicht zu früh greift

				// Test values
//				var_dump('LVE ID: '. $item->lvevaluierung_id);
//				var_dump($item->lv_aufgeteilt ? 'Gruppe' : 'Gesamt');
//				var_dump('Letzte Einheit: '. $lastTerminDate->format("Y-m-d"));
//				var_dump('-------------------------');

				// Wenn letzte Unterrichtseinheit vorbei ist und keine codes versendet worden sind
				if ($now > $lastTerminDate && $item->codes_gemailt === false)
				{
					// Wenn Endezeit existiert
					if ($item->endezeit !== null)
					{
						$endezeit = new DateTime($item->endezeit);

						// Wenn Endezeit heute oder in der Zukunft --> ignorieren
						// (Lektor kann noch ändern bzw. selbst Mailversand auslösen)
						if ($endezeit >= $now) continue;
					}

					// Wenn Endezeit abgelaufen ist oder gar keine Zeiten gesetzt sind:
					// Codes generieren & Mail versenden
					//--------------------------------------------------------------------------------------------------
					// Get Students
					if ($item->lv_aufgeteilt)	// Gruppe
					{
						$this->_ci->load->model('education/Lehreinheit_model', 'LehreinheitModel');
						$result = $this->_ci->LehreinheitModel->getStudenten($item->lehreinheit_id);
					}
					else	// Gesamt-LV
					{
						$this->_ci->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');
						$result = $this->_ci->LehrveranstaltungModel->getStudentsByLv(
							$item->studiensemester_kurzbz,
							$item->lehrveranstaltung_id,
							true	// true = only active students
						);
					}

					if (isError($result))
					{
						$this->logError(getError($result));
						$this->logInfo('End Job sendUnsentEvaluations for '. $studiensemester_kurzbz);
						return;
					}

					// Studenten
					$studenten = hasData($result) ? getData($result) : [];

					// Get unmailed studenten
					$result = $this->_ci->initiierunglib->filterUnmailedStudentMailReceivers(
						$item->lvevaluierung_lehrveranstaltung_id,
						$studenten
					);

					if (isError($result))
					{
						$this->logError(getError($result));
						$this->logInfo('End Job sendUnsentEvaluations for '. $studiensemester_kurzbz);
						return;
					}

					$unmailedStudenten = hasData($result) ? getData($result) : [];
					if (empty($unmailedStudenten)) continue;

					// Set new Start- and Endezeit
					$newStartzeit = clone $now;	// Startdatum heute, Zeit jetzt (da mails auch gleich gesendet werden)
					$newEndezeit  =(clone $now)
						->modify('+3 days')
						->setTime(23, 59, 59);	// Endedatum in 3 Tagen, 23:59:59

					$item->startzeit = $newStartzeit->format('Y-m-d H:i:s');
					$item->endezeit  = $newEndezeit->format('Y-m-d H:i:s');

					// Sent mail counter
					$mailCounter = 0;

					// Loop unmailed Studenten
					foreach ($unmailedStudenten as $student)
					{
						// Generate Code and send Mail
						if ($this->initiierunglib->generateAndSendCodeForStudent($item, $student, $item->lehrveranstaltung_id, 'system'))
						{
							// Count up
							$mailCounter++;
						}
						else
						{
							$this->logInfo('Failed sending mail for LVE-ID '. $item->lvevaluierung_id. ' / student'. $student->uid);
						}
					}

					if ($mailCounter > 0)
					{
						$codesAusgegeben = $item->codes_ausgegeben !== null ? $item->codes_ausgegeben : 0;

						// Update Evaluierung
						$this->_ci->LvevaluierungModel->update(
							$item->lvevaluierung_id,
							[
								'startzeit' => $newStartzeit->format('Y-m-d H:i:s'),
								'endezeit'  => $newEndezeit->format('Y-m-d H:i:s'),
								'codes_gemailt' => true,
								'codes_ausgegeben' => $codesAusgegeben + $mailCounter
							]
						);

						$this->logInfo($mailCounter. ' mails sent for LVE-ID '. $item->lvevaluierung_id);
					}
				}
			}
		}

		$this->logInfo('End Job sendUnsentEvaluierung for '. $studiensemester_kurzbz);
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

		$this->_ci->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');
		$this->_ci->load->model('education/Lehreinheitmitarbeiter_model', 'LehreinheitmitarbeiterModel');

		// Get all Evaluierungen that start tomorrow
		$result = $this->_ci->LvevaluierungModel->getLvesStartingIn('+1 day', false);

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
	 * Job to remind Lecturers or LV-Leitung to start LV-Reflexion one day after Evaluierung ends.
	 *
	 * @return void
	 */
	public function sendReflexionStartInfo(){

		$this->logInfo('Start Job sendReflexionStartInfo');

		$this->_ci->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');
		$this->_ci->load->model('education/Lehreinheitmitarbeiter_model', 'LehreinheitmitarbeiterModel');

		$this->load->library('extensions/FHC-Core-Evaluierung/EvaluationLib');

		// Get all Evaluierungen that ended yesterday
		$result = $this->_ci->LvevaluierungModel->getLvesEndingIn('-1 day', true);

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
				// var_dump($zeitfenster['bis']->format("d.m.Y"));

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
								//echo "\nGesamt Mail to ".$lvLeitung->mitarbeiter_uid;
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
					//	var_dump('lektoren');
					//	var_dump($lektoren);

						foreach($lektoren as $lektor)
						{
							if (!in_array($lektor->uid, $gesamt_sent_users))
							{
								$gesamt_sent_users[] = $lektor->uid;
								//echo "\nGesamt Mail to ".$lektor->uid;
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

	/**
	 * Job to remind Lecturers or LV-Leitung to start LV-Reflexion one week after first infomail was sent.
	 *
	 * @return void
	 */
	public function sendReflexionStartReminder(){

		$this->logInfo('Start Job sendReflexionStartReminder');

		$this->_ci->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungReflexion_model', 'LvevaluierungReflexionModel');
		$this->_ci->load->model('education/Lehrveranstaltung_model', 'LehrveranstaltungModel');
		$this->_ci->load->model('education/Lehreinheitmitarbeiter_model', 'LehreinheitmitarbeiterModel');

		$this->load->library('extensions/FHC-Core-Evaluierung/EvaluationLib');

		// Get all Evaluierungen that ended one day before one week
		$result = $this->_ci->LvevaluierungModel->getLvesEndingIn('-8 day', true);

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
				// var_dump($zeitfenster['bis']->format("d.m.Y"));

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
								// Continue if LV-Reflexion already done
								$result = $this->_ci->LvevaluierungReflexionModel->loadWhere([
									'lvevaluierung_id' => $row->lvevaluierung_id,
									'mitarbeiter_uid' => $lektor->mitarbeiter_uid
								]);

								if (hasData($result))
								{
									continue;
								}

								$gruppe_sent_users[] = $lektor->mitarbeiter_uid;
								$uid = $lektor->mitarbeiter_uid;
								//echo "\nGruppe Mail to ".$lektor->mitarbeiter_uid."\n";

								$data = [
									'vorname' => $lektor->vorname,
									'nachname' => $lektor->nachname,
									'lv_bezeichnung' => $row->lv_bezeichnung,
									'stg_bezeichnung' => $row->stg_bezeichnung,
									'reflexion_bis' => $reflexionBis,
									'link'=> $link
								];

								$mailSent = sendSanchoMail(
									'LVE_LEHR_TEXT_4B_Pflicht',
									$data,
									$uid.'@'.DOMAIN,
									'Reminder: LV-Evaluation auf Gruppen-Ebene: Ergebnisse für '. $row->lv_bezeichnung. ' aus '. $row->stg_typ_kurzbz. ' liegen vor - LV-Reflexion bis '. $reflexionBis,
									'sancho_header_lvevaluierung.jpg',
									'sancho_footer_lvevaluierung.jpg'
								);

								if ($mailSent)
								{
									$this->logInfo('LVE_LEHR_TEXT_4B_Pflicht to '. $uid);
								}
								else
								{
									$this->logError('Failed to send LVE_LEHR_TEXT_4B_Pflicht to '. $uid);
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
								// Continue if LV-Reflexion already done
								$result = $this->_ci->LvevaluierungReflexionModel->loadWhere([
									'lvevaluierung_id' => $row->lvevaluierung_id,
									'mitarbeiter_uid' => $lvLeitung->mitarbeiter_uid
								]);

								if (hasData($result))
								{
									continue;
								}

								$gesamt_sent_users[] = $lvLeitung->mitarbeiter_uid;
								//echo "\nGesamt Mail to ".$lvLeitung->mitarbeiter_uid;
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
									'LVE_LVL_TEXT_6',
									$data,
									$uid.'@'.DOMAIN,
									'Reminder: LV-Evaluation auf Gesamt-Ebene: Ergebnisse für '. $row->lv_bezeichnung. ' aus '. $row->stg_typ_kurzbz. ' liegen vor – LV-Reflexion bis '. $reflexionBis,
									'sancho_header_lvevaluierung.jpg',
									'sancho_footer_lvevaluierung.jpg'
								);

								if ($mailSent)
								{
									$this->logInfo('LVE_LVL_TEXT_6 to '. $uid);
								}
								else
								{
									$this->logError('Failed to send LVE_LVL_TEXT_6 to '. $uid);
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
						//	var_dump('lektoren');
						//	var_dump($lektoren);

						foreach($lektoren as $lektor)
						{
							if (!in_array($lektor->uid, $gesamt_sent_users))
							{
								// Continue if LV-Reflexion already done
								$result = $this->_ci->LvevaluierungReflexionModel->loadWhere([
									'lvevaluierung_id' => $row->lvevaluierung_id,
									'mitarbeiter_uid' => $lektor->uid
								]);

								if (hasData($result))
								{
									continue;
								}

								$gesamt_sent_users[] = $lektor->uid;
								//echo "\nGesamt Mail to ".$lektor->uid;
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
									'LVE_LEHR_TEXT_4A_Optional',
									$data,
									$uid.'@'.DOMAIN,
									'Reminder: LV-Evaluation auf Gesamt-Ebene: Ergebnisse für '. $row->lv_bezeichnung. ' aus '. $row->stg_typ_kurzbz. ' liegen vor - optionale LV-Reflexion bis '. $reflexionBis,
									'sancho_header_lvevaluierung.jpg',
									'sancho_footer_lvevaluierung.jpg'
								);

								if ($mailSent)
								{
									$this->logInfo('LVE_LEHR_TEXT_4A_Optional to '. $uid);
								}
								else
								{
									$this->logError('Failed to send LVE_LEHR_TEXT_4A_Optional to '. $uid);
								}
							}
						}
					}
				}
			}
//			var_dump('gesamt sent users:');
//			var_dump($gesamt_sent_users);
		}

		$this->logInfo('End Job sendReflexionStartReminder');
	}
}
