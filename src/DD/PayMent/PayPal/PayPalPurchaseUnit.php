<?php

namespace DD\PayMent\PayPal;

class PayPalPurchaseUnit {

	public string        $reference_id    = 'default';
	public string        $description     = '';
	public string        $custom_id       = '';
	public string        $soft_descriptor = '';
	public array         $purchase_units  = [];
	public array 		 $amount          = [];
	public array         $items           = [];
	public array 		 $shipping		  = [];

}
