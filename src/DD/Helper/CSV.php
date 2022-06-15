<?php

namespace DD\Helper;

use DD\Exceptions\ValidationException;
use Exception;

class CSV {

	/**
	 * @param string $filename
	 * @param string $delimiter
	 * @param string $enclosedBy
	 * @param string $escapedBy
	 * @param bool $ignoreFirstLine
	 * @return array
	 */
	public static function CSV2Array(string $filename, string $delimiter, string $enclosedBy = '"', string $escapedBy = "\\", bool $ignoreFirstLine = true) : array {

		$data = [];

		if($delimiter == '' || $delimiter == null) {
			$delimiter = ',';
		}

		$handle = fopen ($filename, 'r');
		if(file_exists($filename) && is_readable($filename) && $handle !== false) {


			while (($row = fgetcsv ($handle, 1000, $delimiter, $enclosedBy, $escapedBy)) !== false) {

				if ($ignoreFirstLine) {
					$ignoreFirstLine = false;
				} else {
					$data[] = $row;
				}
			}
			fclose ($handle);

		}

		return $data;

	} // END public static function CSV2Array($filename='', $delimiter=',', $ignoreFirstLine = true) {

	/**
	 * Improved via https://stackoverflow.com/questions/50869961/csv-data-to-array-map-and-str-getcsv-using-delimiter
	 * @param string $filename
	 * @param string $delimiter
	 * @param string $enclosedBy
	 * @param string $escapedBy
	 * @return array
	 * @throws ValidationException
	 */
	public static function GetCSVHeaderFields(string $filename, string $delimiter, string $enclosedBy = '"', string $escapedBy = "\\") : array {

		if(!file_exists($filename) || is_dir($filename) || !is_readable($filename)) {
			throw new ValidationException("File does either not exist, is a folder or is not readable");
		}

		$rows = array_map (function($v) use ($delimiter, $enclosedBy, $escapedBy) {

			return str_getcsv ($v, $delimiter, $enclosedBy, $escapedBy);
		}, file ($filename));

		return array_shift ($rows);

	}

	/**
	 * @param string $filename
	 * @param array $data
	 * @param string $delimiter
	 * @param string $enclosedBy
	 * @return int
	 * @throws ValidationException
	 */
	public static function Array2CSV(string $filename, array $data,  string $delimiter = ";", string $enclosedBy = "") : int {

		$length = 0;
		$csvString = '';

		$fp = fopen($filename, 'w');

		foreach ($data as $fields) {

			$csvString .= implode($delimiter, $fields ) . PHP_EOL;

		}

		$length = fwrite($fp,$csvString);
		if($length == null) {
			throw new ValidationException("FEHLER");
		}

		fclose($fp);

		return $length;

	}

}
