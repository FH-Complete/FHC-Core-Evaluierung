<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

class Export extends Auth_Controller
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(array(
				'index'=> 'extension/lvevaluierung_export:rw'
			)
		);
	}

	/**
	 * Index Controller
	 * @return void
	 */
	public function index()
	{

		$this->load->view('extensions/FHC-Core-Evaluierung/Export');
		
	}
}
