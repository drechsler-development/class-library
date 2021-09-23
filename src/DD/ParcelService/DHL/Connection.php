<?php

namespace DD\ParcelService\DHL;

use Exception;
use SoapClient;
use SoapFault;
use SoapHeader;
use DD\Exceptions\ValidationException;

class Connection
{

	//Constants
	const DHL_AUTH_NAMESPACE   = 'https://dhl.de/webservice/cisbase';
	const DHL_WSDL             = 'https://cig.dhl.de/cig-wsdls/com/dpdhl/wsdl/geschaeftskundenversand-api/3.0/geschaeftskundenversand-api-3.0.wsdl';
	const DHL_SANDBOX_AUTH_URL = 'https://cig.dhl.de/services/sandbox/soap';
	const DHL_PROD_AUTH_URL    = 'https://cig.dhl.de/services/production/soap';
	const DHL_SANDBOX_URL      = 'https://cig.dhl.de/services/sandbox/soap';
	const DHL_PRODUCTION_URL   = 'https://cig.dhl.de/services/production/soap';

	//Public
	public string       $errorMessage;
	public string       $ekp;
	public string       $participantId = '01';

	//Private
	private bool        $useSandbox;
	private string      $url;
	private string      $authUrl;
	private Credentials $credentials;

	/**
	 * Connection constructor.
	 * @param Credentials $credentials
	 * @throws ValidationException
	 */
	public function __construct (Credentials $credentials) {

		$this->credentials = $credentials;

		//You need to declare global CONSTANT (in your application config file or at runtime) as a constant like:
		//  const DHL_USE_SANDBOX = false
		//to activate the PRODUCTION environment

		if(!defined ('DHL_USE_SANDBOX')){
			throw new ValidationException("You need to declare a global constant named DHL_USE_SANDBOX in your global config file");
		}

		$this->useSandbox  = DHL_USE_SANDBOX === true;

		$this->authUrl     = $this->useSandbox ? self::DHL_SANDBOX_AUTH_URL : self::DHL_PROD_AUTH_URL;
		$this->url         = $this->useSandbox ? self::DHL_SANDBOX_URL : self::DHL_PRODUCTION_URL;
		$this->ekp         = $credentials->ekp;
		$this->participantId = $credentials->participantId;

	}

	/**
	 * @return SoapClient
	 * @throws ValidationException
	 * @throws SoapFault
	 */
	public function CreateSoapClient() : SoapClient {


		if(empty($this->credentials->user)){
			throw new ValidationException("No User is set");
		}

		if(empty($this->credentials->signature)){
			throw new ValidationException("No Password (Signature) is set");
		}

		if(empty($this->credentials->api_user)){
			throw new ValidationException("No API User is set");
		}

		if(empty($this->credentials->api_password)){
			throw new ValidationException("No API Password is set");
		}

		if(empty($this->url)){
			throw new ValidationException("No URL is set");
		}

		if(empty($this->authUrl)){
			throw new ValidationException("No URL is set");
		}

		$data = [
			'user'      => $this->credentials->user,
			'signature' => $this->credentials->signature

		];
		$header = new SoapHeader( self::DHL_AUTH_NAMESPACE, 'Authentification', $data);



		$auth_params = array(
			'login'    => $this->credentials->api_user,
			'password' => $this->credentials->api_password,
			'location' => $this->url,
			'soap_version' => SOAP_1_1,
			'trace'    => 1,
			'cache_wsdl' => WSDL_CACHE_NONE,
			'exceptions' => 1,
			'encoding' => 'UTF-8',

		);

		$client = new SoapClient( self::DHL_WSDL, $auth_params );
		$client->__setSoapHeaders( $header );

		return $client;

	}

}
