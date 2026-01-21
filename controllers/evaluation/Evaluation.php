<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

class Evaluation extends Auth_Controller
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		/** @noinspection PhpUndefinedClassConstantInspection */
		parent::__construct(array(
			'index'=> 'extension/lvevaluierung_init:rw'	// TODO!
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
		if ($this->permissionlib->isBerechtigt('extension/lvevaluierung_init:rw'))	// TODO!
		{
			$this->load->view('extensions/FHC-Core-Evaluierung/evaluation/Evaluation.php');
		}
	}
}
