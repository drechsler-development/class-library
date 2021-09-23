<?php

namespace DD\Helper;

use DateTime;
use Exception;
use DD\Exceptions\ValidationException;

class Date {

	//Date Comparision
	const DATE_COMPARE_1_EQ_2 = 1;
	const DATE_COMPARE_1_GT_2 = 2;
	const DATE_COMPARE_1_LT_2 = 3;
	const DATE_COMPARE_ERROR = 0;

	//Date Formats
	const DATE_FORMAT_GERMAN_DATE_SHORT_YEAR = "d.m.y";
	const DATE_FORMAT_GERMAN_DATE_LONG_YEAR = "d.m.Y";
	const DATE_FORMAT_GERMAN_TIME_WITH_SECONDS = "H:i:s";
	const DATE_FORMAT_GERMAN_TIME_WITHOUT_SECONDS = "H:i";
	const DATE_FORMAT_GERMAN_DATETIME = "d.m.Y H:i:s";
	const DATE_FORMAT_SQL_DATE = "Y-m-d";
	const DATE_FORMAT_SQL_DATETIME = "Y-m-d H:i:s";

	private static array $allowedValues = [
		self::DATE_FORMAT_GERMAN_DATE_SHORT_YEAR,
		self::DATE_FORMAT_GERMAN_DATE_LONG_YEAR,
		self::DATE_FORMAT_GERMAN_TIME_WITH_SECONDS,
		self::DATE_FORMAT_GERMAN_TIME_WITHOUT_SECONDS,
		self::DATE_FORMAT_GERMAN_DATETIME,
		self::DATE_FORMAT_SQL_DATE,
		self::DATE_FORMAT_SQL_DATETIME
	];

	/**
	 * @param string|null $date
	 * @param string|null $format
	 * @return bool
	 * @throws ValidationException
	 * @throws Exception
	 */
	public static function ValidateDate(string $date = null, string $format = null) : bool {

		$returnValue = false;

		if(!empty($date)) {

			if ($format == null) {

				foreach (self::$allowedValues as $targetFormat) {
					$d = new DateTime(date ($targetFormat, strtotime (trim ($date))));
					$valid = trim ($d->format ($targetFormat)) == trim ($date);
					if($valid){
						$returnValue = true;
						break;
					}
				}

			} else if (in_array ($format, self::$allowedValues)) {
				$d = new DateTime(date ($format, strtotime (trim ($date))));
				$returnValue = trim ($d->format ($format)) == trim ($date);
			} else {
				throw new ValidationException("Target Format wasn't in the range of allowed formats");
			}

		}

		return $returnValue;

	}

	/**
	 * @param string $time self::DATE_FORMAT_GERMAN_TIME_WITHOUT_SECONDS
	 * @param string $format
	 * @return bool
	 */
	public static function ValidateTime(string $time, string $format = self::DATE_FORMAT_GERMAN_TIME_WITHOUT_SECONDS) : bool {

		try{

			$dateObj = DateTime::createFromFormat('d.m.Y '.$format, "10.10.2010 " .$time);
			return $dateObj !== false;

		}catch (Exception $e){
			return false;
		}

	}

	/**
	 * if $preventZeroValueOutput is set to false (standard) also "00:00" for times and "0000-00-00" for dates will be returned otherwise empty values
	 * @param string $date
	 * @param string $targetFormat
	 * @param bool $preventZeroValueOutput
	 * @return string
	 * @throws ValidationException
	 */
	public static function FormatDateToFormat (string $date, string $targetFormat = Date::DATE_FORMAT_GERMAN_DATE_LONG_YEAR, bool $preventZeroValueOutput = false) : string {

		$returnValue = "";

		if(!in_array($targetFormat,self::$allowedValues)){
			throw new ValidationException("Target Format wasn't in the range of allowed formats");
		}

		if($date != "00.00.0000" && $date != "0000-00-00" && $date != "" && !empty($date)){
			$d = strtotime(trim($date));
			$returnValue = date($targetFormat,$d);
			$returnValue = $returnValue == "00:00" && $preventZeroValueOutput ? "" : $returnValue;
		}

		return $returnValue;

	}

	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//PARAMETERS: Date should be provided as YYYY-MM-DD format
	//RESULT FORMAT:
	// '%y Year %m Month %d Day %h Hours %i Minute %s Seconds'	=>  1 Year 3 Month 14 Day 11 Hours 49 Minute 36 Seconds
	// '%y Year %m Month %d Day'								=>  1 Year 3 Month 14 Days
	// '%m Month %d Day'										=>  3 Month 14 Day
	// '%d Day %h Hours'										=>  14 Day 11 Hours
	// '%d Day'													=>  14 Days
	// '%h Hours %i Minute %s Seconds'							=>  11 Hours 49 Minute 36 Seconds
	// '%i Minute %s Seconds'									=>  49 Minute 36 Seconds
	// '%h Hours												=>  11 Hours
	// '%a Days													=>  468 Days
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/**
	 * @throws Exception
	 */
	public static function DateDifference($bis, $von, $format = '%a' ): string {


		$datevon = new DateTime($von);
		$datetbis = new DateTime($bis);
		$interval = $datevon->diff ($datetbis);

		return $interval->format ($format);

	}

	/**
	 * Compare two dates or datetimes and returns (1 firstDate == second date, 2 firstDate > second date, 3 firstDate < second date, 0 in case of an error)
	 * Returns 1 of firstDate is Bigger than second date
	 * @param string $firstDateTime
	 * @param string $secondDateTime
	 * @param bool $returnDiffValue
	 * @return int
	 * @throws ValidationException
	 */
	public static function CompareDates(string $firstDateTime, string $secondDateTime, bool $returnDiffValue = false): int {

		$diff = false;
		$diffReturnValue = 0;

		$date1 = strtotime($firstDateTime);
		$date2 = strtotime($secondDateTime);

		if($date1 === false) {
			throw new ValidationException("Datum 1 war kein gültiges Datum");
		}

		if($date2 === false) {
			throw new ValidationException("Datum 2 war kein gültiges Datum");
		}

		$diff = $date1 - $date2;

		if(!$diff){
			$diffReturnValue = 0;
		}elseif($diff == 0){
			$diffReturnValue = self::DATE_COMPARE_1_EQ_2;
		}elseif($diff > 0){
			$diffReturnValue = self::DATE_COMPARE_1_GT_2;
		}elseif($diff < 0){
			$diffReturnValue = self::DATE_COMPARE_1_LT_2;
		}

		return $returnDiffValue === true ? $diff : $diffReturnValue;

	}

	/**
	 * @param string $dateToBeChecked
	 * @param string $dateFrom
	 * @param string $dateTo
	 * @return bool
	 */
	public static function IsDateBetweenDates(string $dateFrom, string $dateToBeChecked, string $dateTo) : bool {

		//echo $dateToBeChecked.":".$dateFrom.":".$dateTo."<br>";

		$toBeChecked = strtotime ($dateToBeChecked);
		$from= strtotime($dateFrom);
		$to= strtotime($dateTo);

		return ($toBeChecked >= $from) && ($toBeChecked <= $to);

	}

	/**
	 * @param array $availableArray [['dateFrom' => 'Y-m-d','dateTo' => 'Y-m-d'],...]
	 * @param array $notAvailableArray [['dateFrom' => 'Y-m-d','dateTo' => 'Y-m-d'],...]
	 * @param int $depth
	 * @param array $ignoreNotAvailableArray
	 * @return array [['dateFrom' => 'Y-m-d','dateTo' => 'Y-m-d'],...]
	 * @throws ValidationException
	 */
	public static function SubstractDateRanges(array $availableArray, array $notAvailableArray, int &$depth = 0, array $ignoreNotAvailableArray = []) : array {

		$output = [];
		$format = self::DATE_FORMAT_SQL_DATE;
		$temporaryOutput = [];


		$countAvailable = count($availableArray);
		$countNotAvailable = count($notAvailableArray);

		//echo "Check $depth<br>";

		for($i = 0; $i < $countAvailable; $i++){

			$availableEntry = $availableArray[$i];

			$availableFrom = $availableEntry['dateFrom'] ?? '';
			$availableTo = $availableEntry['dateTo'] ?? '';

			//region Validation
			if(!self::ValidateDate ($availableFrom,$format)){
				throw new ValidationException("source from date $availableFrom invalid");
			}

			if(!self::ValidateDate ($availableTo,$format)){
				throw new ValidationException("source to date $availableTo invalid");
			}
			//endregion

			for($j = 0; $j < $countNotAvailable; $j++){

				$notAvailableEntry = $notAvailableArray[$j];

				if(!in_array($notAvailableEntry,$ignoreNotAvailableArray)){

					$notAvailableFrom = $notAvailableEntry['dateFrom'] ?? '';
					$notAvailableTo = $notAvailableEntry['dateTo'] ?? '';

					if(!self::ValidateDate ($notAvailableFrom,$format)){
						throw new ValidationException("remove from date $notAvailableFrom invalid");
					}

					if(!self::ValidateDate ($notAvailableTo,$format)){
						throw new ValidationException("remove to date $notAvailableTo invalid");
					}

					//Cases

					// [availableFrom [notAvailableFrom notAvailableTo] availableTo]
					if(
						self::IsDateBetweenDates ( $availableFrom,$notAvailableFrom, $notAvailableTo) &&
						self::IsDateBetweenDates ( $availableFrom,$notAvailableTo,$availableTo)
					){

						$depth++;

						$dateTo = self::AddDaysToTimestamp (strtotime($notAvailableFrom), -1);

						$output[] = [
							'dateFrom' => $availableFrom,
							'dateTo' => $dateTo,
						];

						$dateFrom = self::AddDaysToTimestamp (strtotime($notAvailableTo), +1);
						$temporaryOutput[] = [
							'dateFrom' => $dateFrom,
							'dateTo' => $availableTo,
						];

						$ignoreNotAvailableArray[] = $notAvailableEntry;
						$result = self::SubstractDateRanges ($temporaryOutput, $notAvailableArray, $depth, $ignoreNotAvailableArray);

						if(count($result) > 0){
							foreach($result as $entry){
								array_push($output,$entry);
							}
						}else{
							$output[] = [
								'dateFrom' => $dateFrom,
								'dateTo' => $availableTo
							];
						}

						break;

					}else{

						$output[] = [
							'dateFrom' => $availableFrom,
							'dateTo' => $availableTo,
						];

					}

				}

			}

		}

		return $output;

	}

	/**
	 * @param int $timestamp
	 * @param int $days
	 * @param string $outputFormat
	 * @return false|string
	 */
	public static function AddDaysToTimestamp(int $timestamp, int $days, string $outputFormat = self::DATE_FORMAT_SQL_DATE){

		return date($outputFormat, strtotime(date("Y-m-d",$timestamp) . " $days days"));

	}

	/**
	 * @param string $date
	 * @param int $days
	 * @param string $outputFormat
	 * @return false|string
	 */
	public static function AddDaysToDate(string $date, int $days, string $outputFormat = self::DATE_FORMAT_SQL_DATE){

		$timestamp = strtotime ($date);
		return self::AddDaysToTimestamp ($timestamp, $days, $outputFormat);

	}

	/**
	 * @param string $format
	 * @return false|string
	 */
	public static function Now (string $format = Date::DATE_FORMAT_SQL_DATETIME) {

		return date ($format);

	}

	/**
	 * @param string $date
	 * @return bool
	 */
	public static function ContainsTime(string $date) : bool {

		return preg_match ("(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]", $date);

	}

	/**
	 * @param $date
	 * @param $amount
	 * @param string $type
	 * @param bool $add
	 * @param string $returnFormat
	 * @return false|string
	 */
	public static function DateAddOrSubstract ($date, $amount, string $type = "days", bool $add = true, string $returnFormat = Date::DATE_FORMAT_SQL_DATE) {

		$sign = $add ? "+" : "-";
		$date = strtotime($sign.$amount." ".$type, strtotime($date));
		return  date($returnFormat, $date);

	}

	/**
	 * @param string $timeString
	 * @return string
	 */
	public static function GetHoursFromTimeString (string $timeString = "") : string {

		$returnValue = "";

		if (strstr ($timeString, ":")) {
			$array = explode (":", $timeString);
			if (!empty($array)) {
				$returnValue = $array[0];
			}
		}

		return $returnValue;

	}

	/**
	 * @param string $timeString
	 * @return string
	 */
	public static function GetMinutesFromTimeString (string $timeString = ""): string {

		$returnValue = "";

		if (strstr ($timeString, ":")) {
			$array = explode (":", $timeString);
			if (count ($array) > 1) {
				$returnValue = $array[1];
			}
		}

		return $returnValue;

	}

	/**
	 * @param string $time
	 * @param bool $seconds
	 * @return false|int
	 */
	public static function IsTime(string $time, bool $seconds){

		$pattern = $seconds === true ? "#^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$#" : "#^([01]?[0-9]|2[0-3]):[0-5][0-9]?$#";

		return preg_match ($pattern,$time);

	}

}
