<?php

namespace DD\PayMent\PayPal;

class PayPalOrder {

	const AUTHORIZE = 'AUTHORIZE';
	const CAPTURE   = 'CAPTURE';

	public string $intent              = '';
	public array  $application_context = [];
	public array  $purchase_units      = [];

}
