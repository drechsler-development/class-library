<?php

namespace DD\ParcelService\DHL;

class ParcelDetails
{

	public string $WeightInKG = '5.0';
	public string $LengthInCM = '10';
	public string $WidthInCM  = '10';
	public string $HeightInCM = '10';
	public string $PackageType = '';

	public function __construct () { }

}
