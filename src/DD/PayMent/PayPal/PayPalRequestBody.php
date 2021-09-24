<?php

namespace DD\PayMent\PayPal;

class PayPalRequestBody {

	public string $requestBody = '';

	public function __construct (PayPalOrder $PayPalOrder) {

		$this->requestBody = json_encode((array)$PayPalOrder);

	}

}
