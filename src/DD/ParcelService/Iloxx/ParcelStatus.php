<?php

namespace DD\ParcelService\Iloxx;

use DD\Exceptions\ValidationException;
use Exception;
use SoapClient;
use SoapFault;
use SoapHeader;

class ParcelStatus extends Iloxx {

	protected array $environment;
	protected $authorisation;

	/**
	 * @param Authorisation $authorisationObject
	 * @param bool $wsdlCache
	 * @throws ValidationException
	 */
	public function __construct (Authorisation $authorisationObject, bool $wsdlCache = true) {

		parent::__construct ();

		$this->authorisation = $authorisationObject->authorisation;
		$this->environment = ['wsdlCache' => $wsdlCache, 'parcelStatusWsdl' => $this->ILOXX_PARCELSTATUS_WSDL];

	}

	/**
	 * Get the parcel's current status
	 * @param  string $parcelLabelNumber
	 * @return array
	 * @throws Exception
	 */
	public function GetStatus (string $parcelLabelNumber) : array {

		$soapParams = ['cache_wsdl' => $this->ILOXX_WSDL_CACHE, 'exceptions' => $this->ILOXX_WSDL_EXCEPTIONS];

		try {

			$client = new SoapClient($this->ILOXX_PARCELSTATUS_WSDL, $soapParams);
			$header = new SoapHeader($this->ILOXX_SOAPHEADER_URL, 'authentication', $this->authorisation['token']);
			$client->__setSoapHeaders ($header);
			$response = $client->__call("getTrackingData",['parcelLabelNumber' => $parcelLabelNumber]);

			$check = (array)$response->trackingresult;
			if (empty($check)) {
				throw new Exception('Parcel not found');
			}

			foreach ($response->trackingresult->statusInfo as $statusInfo) {
				if ($statusInfo->isCurrentStatus) {
					return ['statusCode' => $statusInfo->status, 'statusLabel' => $statusInfo->label->content, 'statusDescription' => $statusInfo->description->content->content,];
				}
			}
		} catch (SoapFault $e) {
			throw new Exception($e->faultstring);
		}

		return [];

	}

}
