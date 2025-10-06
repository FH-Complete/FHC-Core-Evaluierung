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
	public function initEvaluierungForLehrveranstaltungen()
	{
		$this->_ci->load->model('extensions/FHC-Core-Evaluierung/LvevaluierungLehrveranstaltung_model', 'LvevaluierungLehrveranstaltungModel');

		$studiensemester_kurzbz = 'WS2025'; // todo adapt when clearly defined

		$this->logInfo('Start Job initEvaluierungForLehrveranstaltungen for '. $studiensemester_kurzbz);

		$result = $this->_ci->LvevaluierungLehrveranstaltungModel->insertLehrveranstaltungenFor($studiensemester_kurzbz);
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
}
