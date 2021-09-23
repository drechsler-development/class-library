<?php

namespace DD\Helper;

use DD\Exceptions\ValidationException;
use Exception;

class Strings {

	const PHP_VAR_TYPE_INT       = 1;
	const PHP_VAR_TYPE_STRING    = 2;
	const PHP_VAR_TYPE_NUMERIC   = 3;
	const PHP_VAR_TYPE_DATE      = 4;
	const SQL_VAR_TYPE_DATE_TIME = 5;
	const PHP_VAR_TYPE_TIME      = 6;
	const PHP_VAR_TYPE_DATE_TIME = 7;

	/**
	 * @param $delimiters
	 * @param $string
	 * @return false|string[]
	 */
	public static function MultiExplode ($delimiters, $string) {

		$prepare = str_replace($delimiters, $delimiters[0], $string);
		return explode($delimiters[0], $prepare);

	}

	/**
	 * @param $filename
	 * @return string
	 */
	public static function FilterFilename($filename): string {
		// sanitize filename
		$filename = preg_replace(
			'~
        [<>:"/|?*]|            # file system reserved https://en.wikipedia.org/wiki/Filename#Reserved_characters_and_words
        [\x00-\x1F]|             # control characters http://msdn.microsoft.com/en-us/library/windows/desktop/aa365247%28v=vs.85%29.aspx
        [\x7F\xA0\xAD]|          # non-printing characters DEL, NO-BREAK SPACE, SOFT HYPHEN
        [#\[\]@!$&\'()+,;=]|     # URI reserved https://tools.ietf.org/html/rfc3986#section-2.2
        [{}^\~`]                 # URL unsafe characters https://www.ietf.org/rfc/rfc1738.txt
        ~x',
			'-', $filename);

		// avoids ".", ".." or ".hiddenFiles"
		$filename = ltrim($filename, '.-');

		$filename = preg_replace(array(
			// "file   name.zip" becomes "file-name.zip"
			'/ +/',
			// "file___name.zip" becomes "file-name.zip"
			'/_+/',
			// "file---name.zip" becomes "file-name.zip"
			'/-+/'
		), '-', $filename);

		$filename = preg_replace(array(
			// "file--.--.-.--name.zip" becomes "file.name.zip"
			'/-*\.-*/',
			// "file...name..zip" becomes "file.name.zip"
			'/\.{2,}/'
		), '.', $filename);

		// lowercase for windows/unix interoperability http://support.microsoft.com/kb/100625
		$filename = mb_strtolower($filename, mb_detect_encoding($filename));

		// ".file-name.-" becomes "file-name"
		$filename = trim($filename, '.-');

		// Remove anything which isn't a word, whitespace, number
		// or any of the following caracters -_~,;[]().
		// If you don't need to handle multi-byte characters
		// you can use preg_replace rather than mb_ereg_replace
		// Thanks @Łukasz Rysiak!
		$filename = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $filename);
		// Remove any runs of periods (thanks falstro!)
		$filename = mb_ereg_replace("([\.]{2,})", '', $filename);
		$filename = preg_replace("/[^a-z0-9.-_]/","",strtolower($filename));

		// maximize filename length to 255 bytes http://serverfault.com/a/9548/44086
		$ext = pathinfo($filename, PATHINFO_EXTENSION);

		return mb_strcut(pathinfo($filename, PATHINFO_FILENAME), 0, 255 - ($ext ? strlen($ext) + 1 : 0), mb_detect_encoding($filename)) . ($ext ? '.' . $ext : '');

	}

	/**
	 * @param $email
	 * @return bool
	 */
	public static function IsValidEmail($email): bool {

		if (filter_var ($email, FILTER_VALIDATE_EMAIL)) {
			return true;
		}

		if(preg_match("#^[a-z0-9._-]+@+[a-z0-9._-]+\.+[a-z]{2,6}$#",strtolower($email))) {
			return true;
		}

		return false;

	}

	/**
	 * @param string $text
	 * @return string
	 */
	public static function RemoveTextBreaks(string $text) : string {

		return str_replace(array("\r\n", "\r", "\n"), '', $text);

	}

	/**
	 * @param string $text
	 * @param string $replacement
	 * @return string
	 */
	public static function ReplaceTextBreaks(string $text, string $replacement = ' ') : string {

		return str_replace(array("\r\n", "\r", "\n"), $replacement, $text);

	}

	/**
	 * @param string $string
	 * @return null|string|string[]
	 */
	public static function RemoveSpecialCharacters (string $string = "") {

		$patterns[0] = '/ä/';
		$patterns[1] = '/Ä/';
		$patterns[2] = '/ö/';
		$patterns[3] = '/Ö/';
		$patterns[4] = '/ü/';
		$patterns[5] = '/Ü/';
		$patterns[6] = '/ß/';
		$patterns[7] = '/@/';
		$patterns[8] = '/[^a-zA-Z0-9_-]/u';

		$replacements[0] = "ae";
		$replacements[1] = "AE";
		$replacements[2] = "oe";
		$replacements[3] = "OE";
		$replacements[4] = "ue";
		$replacements[5] = "UE";
		$replacements[6] = "ss";
		$replacements[7] = "at";
		$replacements[8] = "_";

		return preg_replace ($patterns, $replacements, $string);

	}

	/**
	 * @param string $string
	 * @return string
	 */
	public static function PrepareForDBLookup (string $string = ""): string {

		return trim(htmlspecialchars ((strip_tags ($string))));

	}

	/**
	 * @param $price
	 * @return string
	 */
	public static function FormatPriceToSQL($price): string {

		$formattedPrice = $price;

		$posComma = strpos($price,",");
		$posDot = strpos($price,".");

		if($posComma !== false && $posDot !== false){
			// 1,234.67
			if($posComma < $posDot){
				$formattedPrice = str_replace (",","",$price);
			// 1.234,67
			}else{
				$formattedPrice = str_replace (".","#",$price);
				$formattedPrice = str_replace (",",".",$formattedPrice);
				$formattedPrice = str_replace ("#","",$formattedPrice);
			}

		}elseif(strstr($price, ",")){
			$formattedPrice = str_replace (",",".",$price);
		}elseif(strstr($price, ".")){
			$formattedPrice = str_replace (".","",$price);
		}

		return $formattedPrice;

	}

	/**
	 * @param $price
	 * @param string $currency
	 * @param bool $round
	 * @param bool $useThousandSeperator (if used 1.2345,45 will become 12345,45)
	 * @param int $decimals
	 * @return string
	 */
	public static function FormatPrice($price, string $currency = "", bool $round = false, bool $useThousandSeperator = true, int $decimals = 2) : string {

		$formattedPrice = "";

		$thousandSeperator = $useThousandSeperator ? "." :"";


		$priceCommaPos = strpos($price,",");
		$priceDotPos = strpos($price,".");
		if($priceCommaPos !== false && $priceDotPos !== false){
			//1,234.56
			if($priceCommaPos < $priceDotPos){
				$price = str_replace (",","",$price);

			//1.234,56
			}else{
				$price = str_replace (".","",$price);
				$price = str_replace (",",".",$price);
			}

		//1234,56
		}else if($priceCommaPos !== false){
			$price = str_replace (",",".",$price);
		}

		$formattedPrice = $round ?
			number_format (round ($price, 2), $decimals, ",", $thousandSeperator) :
			number_format ($price, $decimals, ",", $thousandSeperator);

		return (strlen($currency) > 0) ? $formattedPrice . " " . $currency : $formattedPrice;

	}

	/**
	 * @param $price
	 * @param int $decimals
	 * @param false $isNumberField
	 * @return string
	 */
	public static function FormatPriceForForm($price, int $decimals = 2, bool $isNumberField = false): string {

		if(strlen($price) == "") {
			return $price;
		}

		$decCharacter = $isNumberField ? "." : ",";

		$price = strstr($price, ",") ? str_replace (",",".",$price) : $price;

		return number_format($price,$decimals,$decCharacter,"");

	}

	/**
	 * @param $num
	 * @return string
	 */
	public static function Num2Word($num): string {

		$ones = array(
			0 =>"NULL",
			1 => "EIN",
			2 => "ZWEI",
			3 => "DREI",
			4 => "VIER",
			5 => "FÜNF",
			6 => "SECHS",
			7 => "SIEBEN",
			8 => "ACHT",
			9 => "NEUN",
			10 => "ZEHN",
			11 => "ELF",
			12 => "ZWÖLF",
			13 => "DREIZEHN",
			14 => "VIERZEHN",
			15 => "FÜNFZEHN",
			16 => "SECHZEN",
			17 => "SIEBZEHN",
			18 => "ACHTZEHN",
			19 => "NEUNZEHN",
			"014" => "VIERZEHN"
		);
		$tens = array(
			0 => "NULL",
			1 => "ZEHN",
			2 => "ZWANZIG",
			3 => "DREISSIG",
			4 => "VIERZIG",
			5 => "FÜNFZIG",
			6 => "SECHZIG",
			7 => "SIEBZIG",
			8 => "ACHTZIG",
			9 => "NEUNZIG"
		);
		$hundreds = array(
			"HUNDERT",
			"TAUSEND",
			"MILLION",
			"MILLIARDE",
			"TRILLION",
			"QUATRILLION"
		); /*limit t quadrillion */
		$num = number_format($num,2);
		$num_arr = explode(".",$num);
		$wholenum = $num_arr[0];
		$decnum = $num_arr[1];
		$whole_arr = array_reverse(explode(",",$wholenum));
		krsort($whole_arr,1);
		$rettxt = "";
		foreach($whole_arr as $key => $i){

			while(substr($i,0,1)=="0") {
				$i = substr ($i, 1, 5);
			}
			if($i < 20){
				/* echo "getting:".$i; */
				$rettxt .= $ones[$i];
			}elseif($i < 100){
				if(substr($i,0,1)!="0") {
					$rettxt .= $tens[substr ($i, 0, 1)];
				}
				if(substr($i,1,1)!="0") {
					$rettxt .= "".$ones[substr ($i, 1, 1)];
				}
			}else{
				if(substr($i,0,1)!="0") {
					$rettxt .= $ones[substr ($i, 0, 1)]." ".$hundreds[0];
				}
				if(substr($i,1,1)!="0") {
					$rettxt .= "".$tens[substr ($i, 1, 1)];
				}
				if(substr($i,2,1)!="0") {
					$rettxt .= "".$ones[substr ($i, 2, 1)];
				}
			}
			if($key > 0){
				$rettxt .= "".$hundreds[$key]."";
			}
		}
		if($decnum > 0){
			$rettxt .= " und ";
			if($decnum < 20){
				$rettxt .= $ones[$decnum];
			}elseif($decnum < 100){
				$rettxt .= $tens[substr($decnum,0,1)];
				$rettxt .= "".$ones[substr($decnum,1,1)];
			}
		}
		return $rettxt;
	}

	/**
	 * @param string $number
	 * @return string
	 */
	public static function FormatTelefoneNumber(string $number): string {

		try {

			$search = array('|^0|','|/|', '| |', '|\.|');
			$repl = array('+49', '', '', '');

			$output=  preg_replace ($search, $repl, $number);

		} catch (Exception $e){

			return $number;

		}

		return $output;

	}

	/**
	 * @param $variable
	 * @param $type
	 * @return false|float|int|string
	 * @throws ValidationException
	 */
	public static function ConvertTo ($variable, $type) {

		$returnVariable = $variable;

		switch(strtolower($type)){

			case "int":
			case self::PHP_VAR_TYPE_INT:
				$returnVariable = (int)$variable;
				break;
			case "string":
			case self::PHP_VAR_TYPE_STRING:
				$returnVariable = (string)$variable;
				break;
			case "numeric":
			case self::PHP_VAR_TYPE_NUMERIC:
				$returnVariable = (float)$variable;
				break;
			case "date":
			case self::PHP_VAR_TYPE_DATE:
				$returnVariable = strpos($variable,"-") ? $variable : Date::FormatDateToFormat($variable);
				break;
			case "sqldate":
			case self::SQL_VAR_TYPE_DATE_TIME:
				$returnVariable = strpos ($variable, "-") ? $variable : Date::FormatDateToFormat($variable, Date::DATE_FORMAT_SQL_DATE);
				break;
			case "time":
			case self::PHP_VAR_TYPE_TIME:
				$returnVariable = strpos($variable,"-") ? $variable : Date::FormatDateToFormat($variable, Date::DATE_FORMAT_GERMAN_TIME_WITH_SECONDS);
				break;
			case "datetime":
			case self::PHP_VAR_TYPE_DATE_TIME:
				$returnVariable = strpos($variable,"-") ? $variable : Date::FormatDateToFormat($variable, Date::DATE_FORMAT_GERMAN_DATETIME);
				break;
			default:
				break;


		}

		return $returnVariable;

	}

	/**
	 * @param $telefonnummer
	 * @return false|int
	 */
	public static function IsMobil($telefonnummer){

		//RegEx MobilNummern DE
		$regexDE="#(^\+49)|(^0049)|(^01[5-7][1-9])#";
		return preg_match($regexDE,$telefonnummer);

	}

	/**
	 * Checks if the parameter is provided in a German Price Format (e.g. 12,00 or just 12 without decimals)
	 * @param $preis
	 * @return false|int
	 */
	public static function IsPrice($preis){

		$pattern = "#^[-]?[0-9]{1,10}([,][0-9]{2,4})*$#";
		return preg_match($pattern,$preis);

	}

	/**
	 * @param string $input
	 * @param string $replacement
	 * @return string
	 */
	public static function RemoveInvalidChars(string $input, string $replacement = "_") : string {

		return preg_replace("/[^0-9a-zA-Z\-_.]/", $replacement, $input);

	}

	/**
	 * Checks if a given String is a JSON formatted string
	 * @param string $string
	 * @return bool
	 */
	public static function IsJSON(string $string): bool {
		return is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE);
	}

	/**
	 * @param int $length = 8
	 * @return string
	 */
	public static function CreatePassword (int $length = 10): string {

		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#%&*()_;:,.';

		return substr (str_shuffle ($chars), 0, $length);

	}

}
