<?php

namespace DD\ParcelService\DHL;

use Exception;
use SoapClient;

class Shipment {

	//CONST

	const DHL_PAKET               = "V01PAK";  //Verfahren 01
	const DHL_PAKET_PRIO          = 'V01PRIO'; //Verfahren 01
	const DHL_PAKET_INTERNATIONAL = 'V53WPAK'; //Verfahren 53
	const DHL_EUROPAKET           = 'V54EPAK'; //Verfahren 54
	const DHL_WARENPOST           = 'V62WP';   //Verfahren 62


	const ALLOWED_PRODUCT_CODES = [
		self::DHL_PAKET,
		self::DHL_PAKET_PRIO,
		self::DHL_PAKET_INTERNATIONAL,
		self::DHL_EUROPAKET,
		self::DHL_WARENPOST
	];

	const MAJOR_RELEASE = 3;
	const MINOR_RELEASE = 0;

	//PUBLIC
	public ?Connection    $credentials       = null;
	public ?ParcelDetails $ParcelDetails     = null;
	public string         $documentId        = '';
	public string         $shipReferenceText = '';
	public string         $productCode       = self::DHL_PAKET;
	public array          $errors            = [];

	//PRIVATE

	private ?Sender    $Sender   = null;
	private ?Receiver  $Receiver = null;
	private SoapClient $soapClient;
	private string     $ekp;
	private string     $participantId;


	//Response
	public string $sequenceNumber = '';
	public string $trackingNumber = '';
	public string $statusCode     = '';
	public string $statusText     = '';
	public string $statusMessage  = '';
	public string $labelUrl       = '';

	/**
	 * Constructor for DHL
	 *
	 * @throws Exception
	 */
	public function __construct(Connection $connection) {

		$this->soapClient = $connection->CreateSoapClient ();
		$this->ekp = $connection->ekp;
		$this->participantId = $connection->participantId;

	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	public function CreateShipmentOrder() : bool {

		try {

			if(!in_array($this->productCode, self::ALLOWED_PRODUCT_CODES)){
				$allowedValues = implode (",", self::ALLOWED_PRODUCT_CODES);
				throw new Exception("Product Code provided as $this->productCode is not in the allowed values ($allowedValues)");
			}

			$shipment = [];

			// Version
			$shipment['Version'] = ['majorRelease' => self::MAJOR_RELEASE, 'minorRelease' => self::MINOR_RELEASE];


			// Order
			$shipment['ShipmentOrder'] = [];

			// Own Shipping Document Number
			$shipment['ShipmentOrder']['sequenceNumber'] = $this->documentId ?? '';

			// Shipment Details
			$shipmentDetails            = [];
			$shipmentDetails['product'] = $this->productCode;


			$procedure = $this->GetProcedure ();
			$shipmentDetails['accountNumber'] = $this->ekp.$procedure.$this->participantId;

			$shipmentDetails['shipmentDate']  = date ('Y-m-d');

			$shipmentDetails['Attendance']              = [];
			$shipmentDetails['Attendance']['partnerID'] = '01';

			if ($this->ParcelDetails == null ) {

				$this->ParcelDetails = new ParcelDetails(); //Load Standard Values

			}


			//Shipment Details
			$shipmentDetails['ShipmentItem']               = [];
			$shipmentDetails['ShipmentItem']['weightInKG'] = $this->ParcelDetails->WeightInKG;
			$shipmentDetails['ShipmentItem']['lengthInCM'] = $this->ParcelDetails->LengthInCM;
			$shipmentDetails['ShipmentItem']['widthInCM']  = $this->ParcelDetails->WidthInCM;
			$shipmentDetails['ShipmentItem']['heightInCM'] = $this->ParcelDetails->HeightInCM;
			$shipmentDetails['ShipmentItem']['packageType'] = $this->ParcelDetails->PackageType;


			$shipment['ShipmentOrder']['Shipment']['ShipmentDetails'] = $shipmentDetails;


			//Add the Sender
			$shipper                  = [];
			$shipper['Name']          = [];
			$shipper['Name']['name1'] = $this->Sender->company_name;

			$shipper['Address']                             = [];
			$shipper['Address']['streetName']               = $this->Sender->street_name;
			$shipper['Address']['streetNumber']             = $this->Sender->street_number;
			$shipper['Address']['zip']                      = $this->Sender->zip;
			$shipper['Address']['city']                     = $this->Sender->city;
			$shipper['Address']['Origin']['countryISOCode'] = $this->Sender->country;

			$shipper['Communication']                  = [];
			$shipper['Communication']['email']         = $this->Sender->email;
			$shipper['Communication']['phone']         = $this->Sender->phone;
			//$shipper['Communication']['internet']      = $this->Sender->internet;
			$shipper['Communication']['contactPerson'] = $this->Sender->contact_person;


			$shipment['ShipmentOrder']['Shipment']['Shipper'] = $shipper;

			//Add the Receiver
			$receiver = [];

			$receiver['name1']                               = $this->Receiver->first_name." ".$this->Receiver->last_name;
			$receiver['Address']                             = [];
			$receiver['Address']['streetName']               = $this->Receiver->street_name;
			$receiver['Address']['streetNumber']             = $this->Receiver->street_number;
			$receiver['Address']['zip']                      = $this->Receiver->zip;
			$receiver['Address']['city']                     = $this->Receiver->city;
			$receiver['Address']['Origin']['countryISOCode'] = $this->Receiver->country;
			$receiver['Communication']                       = [];
			$receiver['Communication']['phone']              = $this->Receiver->phone;
			$receiver['Communication']['email']              = $this->Receiver->email;
			$receiver['Communication']['contactPerson']      = $this->Receiver->first_name." ".$this->Receiver->last_name;


			$shipment['ShipmentOrder']['Shipment']['Receiver'] = $receiver;

			//ShipmentReference
			//$shipment['ShipmentOrder']['Shipment']['ShipperReference'] = $this->shipReferenceText ?? '';

			$response = $this->soapClient->createShipmentOrder($shipment);

			//echo "REQUEST:\n" . htmlentities($this->soapClient->__getLastRequest()) . "\n";

			if ( is_soap_fault( $response ) || $response->Status->statusCode != 0 ) {

				if ( is_soap_fault( $response ) ) {

					$this->errors[] = "Fehler1: ".$response->faultstring;

				} else {

					$this->errors[] = "Fehler2: ".$response->Status->statusText;

				}

			} else {

				$this->sequenceNumber = (string)$response->CreationState->sequenceNumber;
				$this->trackingNumber = (string)$response->CreationState->shipmentNumber;
				$this->statusCode     = (string)$response->CreationState->LabelData->Status->statusCode;
				$this->statusText     = (string)$response->CreationState->LabelData->Status->statusText;
				$statusMessage        = $response->CreationState->LabelData->Status->statusMessage;
				if(is_array ($statusMessage)){
					$statusMessage = implode(",",$statusMessage);
				}
				$this->statusMessage  = $statusMessage;
				$this->labelUrl       = (string)$response->CreationState->LabelData->labelUrl;

				return true;

			}

		} catch (Exception $e) {

			throw new Exception(__FUNCTION__." Exception:".$e->getMessage ());

		}

		return false;

	}

	/**
	 * @param Sender $sender
	 * @throws ValidationException
	 */
	public function SetSender(Sender $sender){

		$this->Sender = $sender->Validate();

	}

	/**
	 * @param Receiver $receiver
	 * @throws ValidationException
	 */
	public function SetReceiver(Receiver $receiver){

		$this->Receiver = $receiver->Validate();

	}

	/**
	 * @throws ValidationException
	 * @throws Exception
	 */
	public function SavePDF(string $fileLocation): bool {


		if(empty($this->labelUrl)){
			throw new ValidationException("labelURL was empty");
		}

		$result = file_put_contents($fileLocation, fopen($this->labelUrl, 'r'));

		# Write the PDF contents to a local file
		return $result == true;


	}

	/**
	 * Gets the needed Procedure part as extracted string from the Product code
	 * @return false|string
	 */
	private function GetProcedure(){
		return substr($this->productCode,1,2);
	}

}
