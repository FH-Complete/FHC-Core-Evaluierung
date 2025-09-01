<?php

class LvevaluierungCode_model extends DB_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_lvevaluierung_code';
		$this->pk = 'lvevaluierung_code_id';
	}

	/**
	 * Returns a unique Code.
	 *
	 * @return code
	 */
	public function getUniqueCode()
	{
		$found = false;

		while(!$found)
		{
			$possibleChars = "123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ";
			$code = '';

			for($i = 0; $i < 11; $i++)
			{
				$rand = rand(0, strlen($possibleChars) - 1);
				$code .= substr($possibleChars, $rand, 1);
			}

			if ($this->exists($code) === false)
			{
				$found = true;
			}
		}

		return $code;
	}

	/**
	 * Checks if code already exists.
	 *
	 * @param $code Code
	 * @return code|false False if not found, Code if found.
	 */
	public function exists($code)
	{
		$result = $this->loadWhere([
			'code' => $code
		]);

		return hasData($result) ? getData($result)[0] : false;
	}
}
