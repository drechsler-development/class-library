<?php

namespace DD\ParcelService\Iloxx;

use DD\Exceptions\ValidationException;
use DD\Helper\CSV;

class Export extends CSV {

	CONST FIRST_ROW = 'Firma;Name;Straße;Adresszusatz;PLZ;Ort;Land;Telefon;E-Mail;KundenNr;Referenz;Inhalt;Gewicht;Nachnahmebetrag';
	CONST DELIMITER = ";";

	public string $errorMessage = '';

	public array $rows = [];

	/**
	 * Export constructor.
	 */
	public function __construct () {

		$this->rows = [];
		$this->rows[] = explode (self::DELIMITER,self::FIRST_ROW);
		$this->errorMessage = '';

	}

	/**
	 * @param string $filename
	 * @param array $data
	 * @param string $delimiter
	 * @param string $enclosedBy
	 * @return int
	 * @throws ValidationException
	 */
	public function Export(string $filename, array $data,  string $delimiter = ",", string $enclosedBy = "") : int {

		return parent::Array2CSV ($filename,$data, $delimiter, $enclosedBy);

	}

	/**
	 * @param string $companyName
	 * @param string $name
	 * @param string $address
	 * @param string $additionalAddress
	 * @param string $postCode
	 * @param string $city
	 * @param string $countryCode
	 * @param string $telephone
	 * @param string $email
	 * @param string $accountNumber
	 * @param string $reference
	 * @param string $content
	 * @param string $weight
	 * @param string $amountToBeCollected
	 * @return bool

	public function AddRow(string $companyName, string $name, string $address, string $additionalAddress, string $postCode, string $city, string $countryCode = 'DEU', string $telephone = '', string $email = '', string $accountNumber = '', string $reference = '', string $content = '', string $weight = '', string $amountToBeCollected = '') : bool {

		try {

			if(strlen($companyName) > 30){
				$this->errorMessage[] = sprintf (L("Firma '%s' ist länger als 30 Zeichen und wird abgeschnitten",$companyName));
				$companyName = substr($companyName,0,30);
			}
			if(strlen($name) > 30){
				$this->errorMessage[] = sprintf (L("Name '%s' ist länger als 30 Zeichen und wird abgeschnitten",$name));
				$name = substr($name,0,30);
			}
			if(strlen($address) > 50){
				$this->errorMessage[] = sprintf (L("Adresse '%s' ist länger als 50 Zeichen und wird abgeschnitten",$address));
				$address = substr($address,0,30);
			}
			if(strlen($additionalAddress) > 30){
				$this->errorMessage[] = sprintf (L("Zusatzadresse '%s' ist länger als 30 Zeichen und wird abgeschnitten",$additionalAddress));
				$additionalAddress = substr($additionalAddress,0,30);
			}
			if(strlen($postCode) > 10){
				$this->errorMessage[] = sprintf (L("PLZ '%s' ist länger als 10 Zeichen und wird abgeschnitten",$postCode));
				$postCode = substr($postCode,0,30);
			}
			if(strlen($city) > 50){
				$this->errorMessage[] = sprintf (L("Ort '%s' ist länger als 50 Zeichen und wird abgeschnitten",$city));
				$city = substr($city,0,30);
			}
			if(strlen($countryCode) > 3){
				$this->errorMessage[] = sprintf (L("Ländercode '%s' ist länger als 3 Zeichen und wird abgeschnitten",$countryCode));
				$countryCode = substr($countryCode,0,30);
			}
			if(strlen($telephone) > 25){
				$this->errorMessage[] = sprintf (L("Telefon '%s' ist länger als 25 Zeichen und wird abgeschnitten",$telephone));
				$telephone = substr($telephone,0,30);
			}
			if(strlen($email) > 50){
				$this->errorMessage[] = sprintf (L("Email '%s' ist länger als 50 Zeichen und wird abgeschnitten",$email));
				$email = substr($email,0,30);
			}
			if(strlen($accountNumber) > 25){
				$this->errorMessage[] = sprintf (L("AccountNumber '%s' ist länger als 25 Zeichen und wird abgeschnitten",$accountNumber));
				$accountNumber = substr($accountNumber,0,30);
			}
			if(strlen($reference) > 25){
				$this->errorMessage[] = sprintf (L("Reference '%s' ist länger als 25 Zeichen und wird abgeschnitten",$reference));
				$reference = substr($reference,0,30);
			}
			if(strlen($content) > 30){
				$this->errorMessage[] = sprintf (L("Inhalt '%s' ist länger als 30 Zeichen und wird abgeschnitten",$content));
				$content = substr($content,0,30);
			}
			if(strlen($weight) > 7){
				$this->errorMessage[] = sprintf (L("Gewicht '%s' ist länger als 7 Zeichen und wird abgeschnitten",$weight));
				$weight = substr($weight,0,30);
			}
			if(strlen($amountToBeCollected) > 7){
				$this->errorMessage[] = sprintf (L("Nachnamebetrag '%s' ist länger als 7 Zeichen und wird abgeschnitten",$amountToBeCollected));
				$amountToBeCollected = substr($amountToBeCollected,0,30);
			}

			if($name == '' && $companyName == ''){
				throw new ValidationException(L("Mindestens eins der beiden Werte muss gesetzt werden. Firma/Name"));
			}

			if($address == ''){
				throw new ValidationException( L("Adresse ist leer"));
			}

			if($postCode == ''){
				throw new ValidationException(L("Postleitzahl ist leer"));
			}

			if($city == ''){
				throw new ValidationException(L("Keine Stadt angegeben"));
			}

			//$this->rows[] = array('Firma;Name;Straße;Adresszusatz;PLZ;Ort;Land;Telefon;E-Mail;KundenNr;Referenz;Inhalt;Gewicht;Nachnahmebetrag');
			$this->rows[] = array($companyName, $name,$address,$additionalAddress,$postCode,$city,$countryCode, $telephone,$email, $accountNumber, $reference, $content, $weight, $amountToBeCollected);

		} catch (ValidationException $e) {

			echo $this->errorMessage = $e->getMessage ();
			SendAdminMail ("File: ".__FILE__."<br> Method:".__FUNCTION__." <br>Line: ".__LINE__."<br>Error: ".$this->errorMessage, EMAIL_SUBJECT_VALIDATION_EXCEPTION);

		} catch (Exception $e) {

			echo $this->errorMessage = $e->getMessage ();
			SendAdminMail ("File: ".__FILE__."<br> Method:".__FUNCTION__." <br>Line: ".__LINE__."<br>Error: ".$this->errorMessage, EMAIL_SUBJECT_EXCEPTION);

		} // ENDE try {

	}
	*/


}
