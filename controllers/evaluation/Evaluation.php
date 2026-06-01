<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

class Evaluation extends Auth_Controller
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(
			[
				'lehre'=> [
					'extension/lvevaluierung_init:rw',
					'extension/lvevaluierung_stg:rw',
					'extension/lvevaluierung_kf:rw',
					'extension/lvevaluierung_admin:rw',
				],
				'stg'=> [
					'extension/lvevaluierung_stg:rw',
					'extension/lvevaluierung_admin:rw',
				],
				'kf'=> [
					'extension/lvevaluierung_kf:rw',
					'extension/lvevaluierung_admin:rw',
				]
			]
		);
	}
	public function lehre()
	{
		$this->load->view('extensions/FHC-Core-Evaluierung/evaluation/Evaluation');
	}

	public function stg()
	{
		$this->load->view('extensions/FHC-Core-Evaluierung/evaluation/Evaluation');
	}

	public function kf()
	{
		$this->load->view('extensions/FHC-Core-Evaluierung/evaluation/Evaluation');
	}
}
