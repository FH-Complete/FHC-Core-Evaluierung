<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

class Administration extends Auth_Controller
{
	const BERECHTIGUNG_ADMIN = 'extension/lvevaluierung_admin';
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(array(
			'index'=> self::BERECHTIGUNG_ADMIN . ':rw',
			'fragebogen' => self::BERECHTIGUNG_ADMIN . ':rw',
			)
		);
	}

	/**
	 * Index Controller
	 * @return void
	 */
	public function index()
	{
		$this->load->view('extensions/FHC-Core-Evaluierung/Administration');

	}

	public function fragebogen()
	{
		$this->index();
	}
}
