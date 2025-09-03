<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

class Initiierung extends Auth_Controller
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		/** @noinspection PhpUndefinedClassConstantInspection */
		parent::__construct(array(
			'index'=> 'extension/lvevaluierung_init:r'
			)
		);

		$this->load->library('PermissionLib');
	}

	/**
	 * Index Controller
	 * @return void
	 */
	public function index()
	{
		if ($this->permissionlib->isBerechtigt('extension/lvevaluierung_init:r'))
		{
			$this->load->view('extensions/FHC-Core-Evaluierung/Initiierung');
		}
	}
}
