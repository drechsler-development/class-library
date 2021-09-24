<?php

namespace DD\PayMent\PayPal;

class PayPalApplicationContext {

	const PAY_NOW  = 'PAY_NOW';
	const CONTINUE = 'CONTINUE';

	public string  $return_url          = '';
	public string  $cancel_url          = '';
	public ?string $brand_name          = null;
	public ?string $locale              = null;
	public ?string $landing_page        = 'BILLING';
	public ?string $shipping_preference = 'SET_PROVIDED_ADDRESS';
	public ?string $user_action         = self::PAY_NOW;

}
