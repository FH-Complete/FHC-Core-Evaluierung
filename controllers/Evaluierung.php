<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

class Evaluierung extends Auth_Controller
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		/** @noinspection PhpUndefinedClassConstantInspection */
		parent::__construct(array(
			'index'=> self::PERM_ANONYMOUS
			)
		);
	}

	/**
	 * Index Controller
	 * @return void
	 */
	public function index()
	{
		$this->load->view('extensions/FHC-Core-Evaluierung/Evaluierung');
	}
}
