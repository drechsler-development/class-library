<?php

namespace DD\ParcelService\Iloxx;

use Exception;
use InvalidArgumentException;
use SoapClient;
use SoapFault;
use SoapHeader;

class Shipment extends Iloxx {

	const ILOXX_PRODUCT_CODE_CL = 'CL';
	/*
	const ILOXX_PRODUCT_CODE_E830 = 'E830';
	const ILOXX_PRODUCT_CODE_E10 = 'E10';
	const ILOXX_PRODUCT_CODE_E12 = 'E12';
	const ILOXX_PRODUCT_CODE_E18 = 'E18';
	const ILOXX_PRODUCT_CODE_IE2 = 'IE2';
	const ILOXX_PRODUCT_CODE_PL = 'PL';
	const ILOXX_PRODUCT_CODE_PL_PLUS = 'PL+';
	const ILOXX_PRODUCT_CODE_MAIL = 'MAIL';
	*/

	const ILOXX_CHANNEL_CODE_EMAIL 		= 1;
	const ILOXX_CHANNEL_CODE_TELEPHONE 	= 2;
	const ILOXX_CHANNEL_CODE_SMS 		= 3;

	const ILOXX_PAPER_FORMAT_A4 = 'A4';
	const ILOXX_PAPER_FORMAT_A6 = 'A6';

	protected array $environment;

	protected $authorisation;

	protected array $predictCountries = ['BE', 'NL', 'DE', 'AT', 'PL', 'FR', 'PT', 'GB', 'LU', 'EE', 'CH', 'IE', 'SK', 'LV', 'SI', 'LT', 'CZ', 'HU'];

	protected array $storeOrderMessage = [
		'printOptions' => [
			'paperFormat' => null,
			'printerLanguage' => null
		],
		'order' => [
			'generalShipmentData' => [
				'sendingDepot' => null,
				'product' => null,
				'mpsCustomerReferenceNumber1' => null,
				'mpsCustomerReferenceNumber2' => null,
				'sender' => [
					'name1' => null,
					'name2' => null,
					'street' => null,
					'houseNo' => null,
					'state' => 'NY',
					'country' => null,
					'zipCode' => null,
					'city' => null,
					'email' => null,
					'phone' => null,
					'gln' => null,
					'contact' => null,
					'fax' => null,
					'customerNumber' => null
				],
				'recipient' => [
					'name1' => null,
					'name2' => null,
					'street' => null,
					'houseNo' => null,
					'state' => 'NY',
					'country' => null,
					'gln' => null,
					'zipCode' => null,
					'customerNumber' => null,
					'contact' => null,
					'phone' => null,
					'fax' => null,
					'email' => null,
					'city' => null,
					'comment' => 'comment'
				]
			],
			'parcels' => [],
			'productAndServiceData' => [
				'saturdayDelivery' => false,
				'orderType' => 'consignment'
			]
		]
	];

	protected string $trackingLanguage 	= '';
	protected string $label            	= '';
	protected array $airWayBills      	= [];

	private bool $reference1Set = false;
	private bool $reference2Set = false;

	/**
	 * @param Authorisation $authorisationObject
	 * @param boolean [$wsdlCache         = true]
	 * @throws Exception
	 */
	public function __construct (Authorisation $authorisationObject, $wsdlCache = true) {

		parent::__construct ();

		$this->authorisation = $authorisationObject->authorisation;
		$this->environment = ['wsdlCache' => $wsdlCache, 'shipWsdl' => $this->ILOXX_SHIPMENT_WSDL];
		$this->storeOrderMessage['order']['generalShipmentData']['sendingDepot'] = $this->authorisation['token']->depot;
	}

	/**
	 * Add a parcel to the shipment
	 * @param array $array
	 * @throws Exception
	 */
	public function AddParcel (array $array) : void {

		if (!isset($array['weight']) or !isset($array['height']) or !isset($array['length']) or !isset($array['width'])) {
			throw new Exception('Parcel array not complete');
		}
		$volume = str_pad ((string)ceil ($array['length']), 3, '0', STR_PAD_LEFT);
		$volume .= str_pad ((string)ceil ($array['width']), 3, '0', STR_PAD_LEFT);
		$volume .= str_pad ((string)ceil ($array['height']), 3, '0', STR_PAD_LEFT);

		$this->storeOrderMessage['order']['parcels'][] = ['volume' => $volume, 'weight' => (int)ceil ($array['weight'] / 10)];

	}

	/**
	 * Submit the parcel to the ILOXX webservice
	 * @throws Exception
	 */
	public function Submit () : void {

		$soapParams = ['cache_wsdl' => $this->ILOXX_WSDL_CACHE, 'exceptions' => $this->ILOXX_WSDL_EXCEPTIONS];

		try {

			if (isset($this->storeOrderMessage['order']['productAndServiceData']['predict'])) {
				if (!in_array (strtoupper ($this->storeOrderMessage['order']['generalShipmentData']['recipient']['country']), $this->predictCountries)) {
					throw new Exception('Predict service not available for this destination');
				}
			}
			if (count ($this->storeOrderMessage['order']['parcels']) === 0) {
				throw new Exception('Create at least 1 parcel');
			}

			$client = new SoapClient($this->environment['shipWsdl'], $soapParams);
			$header = new SoapHeader($this->ILOXX_SOAPHEADER_URL, 'authentication', $this->authorisation['token']);
			$client->__setSoapHeaders ($header);

			$response = $client->__call ("storeOrders", [$this->storeOrderMessage]);

			if (isset($response->orderResult->shipmentResponses->faults)) {
				throw new Exception($response->orderResult->shipmentResponses->faults->message);
			}

			$this->label = @$response->orderResult->parcellabelsPDF;
			unset($response->orderResult->parcellabelsPDF);

			if (is_array ($response->orderResult->shipmentResponses->parcelInformation)) {
				foreach ($response->orderResult->shipmentResponses->parcelInformation as $parcelResponse) {
					$this->airWayBills[] = [
						'airWayBill' => $parcelResponse->parcelLabelNumber,
						'trackingLink' => strtr ($this->ILOXX_TRACKING_URL,
							[
								':awb' => $parcelResponse->parcelLabelNumber,
								':lang' => $this->trackingLanguage
							])
					];
				}
			} else {
				$this->airWayBills[] = [
					'airWayBill' => $response->orderResult->shipmentResponses->parcelInformation->parcelLabelNumber,
					'trackingLink' => strtr ($this->ILOXX_TRACKING_URL, [
						':awb' => $response->orderResult->shipmentResponses->parcelInformation->parcelLabelNumber,
						':lang' => $this->trackingLanguage
					])
				];
			}

		} catch (SoapFault $eFault) {
			throw new Exception($eFault->faultstring);
		} catch (Exception $e) {
			throw new Exception($e->getMessage ());
		}

	}

	/**
	 * Enable ILOXX's B2C service. Only allowed for countries in protected $predictCountries
	 * @param array $array
	 *  'channel' => email|telephone|sms,
	 *  'value' => emailaddress or phone number,
	 *  'language' => EN
	 * @throws Exception
	 */
	public function SetPredict (array $array) : void {

		if (!isset($array['channel']) or !isset($array['value']) or !isset($array['language'])) {
			throw new Exception('Predict array not complete');
		}

		switch (strtolower ($array['channel'])) {
			case self::ILOXX_CHANNEL_CODE_EMAIL:

				if (!filter_var ($array['value'], FILTER_VALIDATE_EMAIL)) {
					throw new Exception('Predict email address not valid');
				}
				break;
			case self::ILOXX_CHANNEL_CODE_TELEPHONE:
				if (empty($array['value'])) {
					throw new Exception('Predict value (telephone) empty');
				}
				break;
			case self::ILOXX_CHANNEL_CODE_SMS:
				if (empty($array['value'])) {
					throw new Exception('Predict value (sms) empty');
				}
				break;
			default:
				throw new Exception('Predict channel not allowed');
		}

		if (ctype_alpha ($array['language']) && strlen ($array['language']) === 2) {
			$array['language'] = strtoupper ($array['language']);
		}
		$this->storeOrderMessage['order']['productAndServiceData']['predict'] = $array;

	}

	/**
	 * Get an array with parcelnumber and trackinglink for each package
	 * @return array
	 */
	public function GetParcelResponses () : array {

		return $this->airWayBills;

	}

	/**
	 * Set the general shipmentdata
	 * @param array $array see protected $storeOrderMessage
	 */
	public function SetGeneralShipmentData (array $array) : void {

		$this->storeOrderMessage['order']['generalShipmentData'] = array_merge ($this->storeOrderMessage['order']['generalShipmentData'], $array);

	}

	/**
	 * Enable saturday delivery
	 * @param boolean $bool default false
	 */
	public function SetSaturdayDelivery (bool $bool) : void {

		$this->storeOrderMessage['order']['productAndServiceData']['saturdayDelivery'] = $bool;

	}

	/**
	 * Set the shipment's sender
	 * @param Contact $contact
	 * @throws Exception
	 */
	public function SetSender (Contact $contact) : void {


		try{

			if($contact instanceof Contact){
				$array = (array) $contact;
			} else {
				throw new InvalidArgumentException("Type of paraeter must be either array or DBDContact");
			}

		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		$array['customerNumber'] = $this->authorisation['customerNumber'];
		$array['city'] = strtoupper ($array['city']);
		$this->storeOrderMessage['order']['generalShipmentData']['sender'] = array_merge ($this->storeOrderMessage['order']['generalShipmentData']['sender'], $array);

	} // END public function SetSender ($object) {

	/**
	 * @param string $referenceMessage
	 * @param int $position
	 * @throws Exception
	 */
	public function SetReference(string $referenceMessage, int $position = 1) : void {

		if(strlen($referenceMessage) > 0){

			if(strlen($referenceMessage) > 35)
				$referenceMessage = substr ($referenceMessage,0,35);

			if($position == 1){
				if(!$this->reference1Set) {
					$this->storeOrderMessage['order']['generalShipmentData']['mpsCustomerReferenceNumber1'] = $referenceMessage;
					$this->reference1Set = true;
				}else{
					throw new Exception("Reference Position 1 ist bereits belegt");
				}
			}elseif($position == 2 && !$this->reference2Set){
				if(!$this->reference2Set) {
					$this->storeOrderMessage['order']['generalShipmentData']['mpsCustomerReferenceNumber2'] = $referenceMessage;
					$this->reference2Set = true;
				}else{
					throw new Exception("Reference Position 2 ist bereits belegt");
				}
			}

		}

	}

	/**
	 * Set the shipment's receiver
	 * @param Contact $contact
	 * @throws Exception
	 */
	public function SetReceiver (Contact $contact) : void {

		try{

			if($contact instanceof Contact){
				$array = (array) $contact;
			}else {
				throw new InvalidArgumentException("Type of paraeter must be either array or DBDContact");
			}

		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		$this->storeOrderMessage['order']['generalShipmentData']['recipient'] = array_merge ($this->storeOrderMessage['order']['generalShipmentData']['recipient'], $array);
	}

	/**
	 * Set the printoptions
	 * @param array $printoptions
	 */
	public function SetPrintOptions (array $printoptions) : void {

		$this->storeOrderMessage['printOptions'] = array_merge ($this->storeOrderMessage['printOptions'], $printoptions);

	}

	/**
	 * Set the language for the track & trace link
	 * @param string $language format: en_EN
	 */
	public function SetTrackingLanguage (string $language) : void {

		$this->trackingLanguage = $language;

	}

	/**
	 * Gets the shipment label pdf as a string
	 * @return string
	 */
	public function GetLabels () : string {

		return $this->label;

	}

}
