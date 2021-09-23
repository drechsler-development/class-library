<?php

namespace DD\ParcelService\Iloxx;

use DD\Exceptions\ValidationException;
use SoapClient;
use SoapFault;

class Authorisation extends Iloxx {

	public  array $environment = [];
	private string $wsdl        = '';

	public array $authorisation = [
		'delisId' => null,
		'password' => null,
		'messageLanguage' => 'de_DE',
		'customerNumber' => null,
		'token' => null
	];

	/**
	 * Get an authorisationtoken from the ILOXX webservice
	 * @param array("delisId","authToken","messageLanguage") $array
	 * @throws ValidationException|SoapFault
	 */
	public function __construct ($array) {

		parent::__construct();

		if(!isset($array['delisId'])) {
			throw new ValidationException("ILOXX delisId fehlt");
		}

		if(!isset($array['password'])) {
			throw new ValidationException("ILOXX password fehlt");
		}

		$this->authorisation = array_merge ($this->authorisation, $array);
		$soapParams = [
			'cache_wsdl' => $this->ILOXX_WSDL_CACHE,
			'exceptions' => $this->ILOXX_WSDL_EXCEPTIONS
		];
		$this->wsdl = $this->ILOXX_LOGIN_WSDL;

		$client = new SoapClient($this->wsdl, $soapParams);

		$auth = $client->__call ("getAuth", [
			[
				'delisId' => $this->authorisation['delisId'],
				'password' => $this->authorisation['password'],
				'messageLanguage' => $this->authorisation['messageLanguage']
			]
		]);

		$auth->return->messageLanguage = $this->authorisation['messageLanguage'];
		$this->authorisation['token'] = $auth->return;

	}

}
