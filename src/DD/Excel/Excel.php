<?php

namespace DD\Excel;

use DD\Exceptions\ValidationException;

class Excel {

	/**
	 * @var array|array[]
	 */
	public array  $data = [];
	public bool $useTestData = false;
	private array $testData = [
		[
			"NAME"    => "John Doe",
			"EMAIL"   => "john.doe@gmail.com",
			"GENDER"  => "Male",
			"COUNTRY" => "United States"
		],
		[
			"NAME"    => "Gary Riley",
			"EMAIL"   => "gary@hotmail.com",
			"GENDER"  => "Male",
			"COUNTRY" => "United Kingdom"
		],
		[
			"NAME"    => "Edward Siu",
			"EMAIL"   => "siu.edward@gmail.com",
			"GENDER"  => "Male",
			"COUNTRY" => "Switzerland"
		],
		[
			"NAME"    => "Betty Simons",
			"EMAIL"   => "simons@example.com",
			"GENDER"  => "Female",
			"COUNTRY" => "Australia"
		],
		[
			"NAME"    => "Frances Lieberman",
			"EMAIL"   => "lieberman@gmail.com",
			"GENDER"  => "Female",
			"COUNTRY" => "United Kingdom"
		]
	];

	public function __construct () {

	}

	/**
	 * @throws ValidationException
	 */
	public function ConvertToExcel (){

		if ($this->useTestData) {
			$this->data = $this->testData;
		}

		if(empty($this->data)){
			throw new ValidationException("No data was provided or the data array was empty");
		}

		$firstRow = true;
		foreach ($this->data as $row) {
			if ($firstRow) {
				echo implode ("\t", array_keys ($row))."\n";
				$firstRow = false;
			}
			// filter data
			array_walk ($row, 'filterData');
			echo implode ("\t", array_values ($row))."\n";
		}

	}

	private function filterData (&$str) {

		$str = preg_replace ("/\t/", "\\t", $str);
		$str = preg_replace ("/\r?\n/", "\\n", $str);
		if (strstr ($str, '"'))
			$str = '"'.str_replace ('"', '""', $str).'"';
	}

}
