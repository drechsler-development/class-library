<?php

namespace DD\PayMent\PayPal;

class PayPalItem {

	CONST DIGITAL_GOODS = 'DIGITAL_GOODS';
	CONST PHYSICAL_GOODS = 'PHYSICAL_GOODS';

	public string $name        = '';
	public string $description = '';
	public string $sku         = '';
	public array  $unit_amount = [];
	public array  $tax         = [];
	public string $quantity    = '1';
	public string $category    = self::DIGITAL_GOODS;

}
