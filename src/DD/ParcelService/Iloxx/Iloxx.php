<?php

namespace DD\ParcelService\Iloxx;

use DD\Exceptions\ValidationException;

class Iloxx {

	protected string $ILOXX_LOGIN_WSDL      = '';
	protected string $ILOXX_SHIPMENT_WSDL   = '';
	protected string $ILOXX_PARCELSTATUS_WSDL;
	protected int    $ILOXX_WSDL_CACHE      = WSDL_CACHE_BOTH;
	protected bool   $ILOXX_WSDL_EXCEPTIONS = false;
	protected string $ILOXX_SOAPHEADER_URL  = 'https://dpd.com/common/service/types/Authentication/2.0';
	protected string $ILOXX_TRACKING_URL    = 'https://tracking.dpd.de/parcelstatus?locale=:lang&query=:awb';

	/**
	 * @throws ValidationException
	 */
	public function __construct () {

		if (!defined ('SYSTEMTYPE')) {
			throw new ValidationException('No SYSTEMTYPE has been declared as a global constant');
		}

		if (!defined ('PROD')) {
			throw new ValidationException('No PROD System has been declared as a global constant');
		}

		if(SYSTEMTYPE == PROD){

			$this->ILOXX_LOGIN_WSDL        = 'https://public-ws.dpd.com/services/LoginService/V2_0?wsdl';
			$this->ILOXX_SHIPMENT_WSDL     = 'https://public-ws.dpd.com/services/ShipmentService/V3_1?wsdl';
			$this->ILOXX_PARCELSTATUS_WSDL = 'https://public-ws.dpd.com/services/ParcelLifeCycleService/V2_0/?wsdl';
			$this->ILOXX_WSDL_CACHE        = WSDL_CACHE_BOTH;
			$this->ILOXX_WSDL_EXCEPTIONS   = false;

		}else{

			$this->ILOXX_LOGIN_WSDL        = 'https://public-ws-stage.dpd.com/services/LoginService/V2_0/?wsdl';
			$this->ILOXX_SHIPMENT_WSDL     = 'https://public-ws-stage.dpd.com/services/ShipmentService/V3_1?wsdl';
			$this->ILOXX_PARCELSTATUS_WSDL = 'https://public-ws-stage.dpd.com/services/ParcelLifeCycleService/V2_0/?wsdl';
			$this->ILOXX_WSDL_CACHE        = WSDL_CACHE_NONE;
			$this->ILOXX_WSDL_EXCEPTIONS   = true;

		}

	}

}
