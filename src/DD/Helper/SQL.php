<?php

namespace DD\Helper;

use DD\Exceptions\ValidationException;
use DD\SystemType;

class SQL
{

	const SQL_VAR_TYPE_INT       = 1;
	const SQL_VAR_TYPE_STRING    = 2;
	const SQL_VAR_TYPE_DECIMAL   = 3;
	const SQL_VAR_TYPE_DATE      = 4;
	const SQL_VAR_TYPE_TIME      = 5;
	const SQL_VAR_TYPE_DATE_TIME = 6;

	/**
	 * @param mixed $value
	 * @param int $type
	 * @return false|int|mixed|string
	 * @throws ValidationException
	 */
	public static function ConvertToSQL ($value, int $type = self::SQL_VAR_TYPE_INT) {

		switch ($type) {

			case self::SQL_VAR_TYPE_DECIMAL:
				if (strpos ($value, ',') !== false) {
					if (strpos ($value, '.') !== false) {
						$value = str_replace ('.', '', $value);
					}
					$value = str_replace (',', '.', $value);
				}
				$returnVariable = $value;
				break;
			case self::SQL_VAR_TYPE_DATE:
				$returnVariable = strpos ($value, '-') ? $value : Date::FormatDateToFormat ($value, Date::DATE_FORMAT_SQL_DATE);
				break;
			case self::SQL_VAR_TYPE_TIME:
				$returnVariable = strpos ($value, '-') ? $value : Date::FormatDateToFormat ($value, Date::DATE_FORMAT_GERMAN_TIME_WITH_SECONDS);
				break;
			case self::SQL_VAR_TYPE_DATE_TIME:
				$returnVariable = strpos ($value, '-') ? $value : Date::FormatDateToFormat ($value, Date::DATE_FORMAT_SQL_DATE).' '.Date::FormatDateToFormat ($value, Date::DATE_FORMAT_GERMAN_TIME_WITH_SECONDS);
				break;
			case self::SQL_VAR_TYPE_STRING:
				$returnVariable = (string)$value;
				break;
			default:
				$returnVariable = (int)$value;
				break;

		} // ENDE switch($type){

		return $returnVariable;

	}

	/**
	 * @param string $SQL
	 * @param bool $showInNonDEV
	 * @param bool $forPostMan
	 * @throws ValidationException
	 */
	public static function ShowSQL (string $SQL, bool $showInNonDEV = false, bool $forPostMan = false) {

		if(!defined("SYSTEMTYPE")){
			throw new ValidationException("global const SYSTEMTYPE is not defined");
		}

		if (SYSTEMTYPE == SystemType::DEV || $showInNonDEV) {

			$result = !$forPostMan ? str_replace ("\r\n", '<br>', $SQL) : $SQL;
			$result = $forPostMan ? str_replace ("\t", ' ', $result) : $result;
			echo $result;
		}

	}

}
