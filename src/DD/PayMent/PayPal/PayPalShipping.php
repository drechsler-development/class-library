<?php

namespace DD\PayMent\PayPal;

class PayPalShipping {

	const SHIPPING = 'SHIPPING';
	const PICKUP_IN_PERSON = 'PICKUP_IN_PERSON';

	public string $type    = '';
	public array  $name    = [];
	public array  $address = [];

}
