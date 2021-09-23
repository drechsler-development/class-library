<?php

namespace DD\ParcelService\DHL;

class Credentials
{
	//SoapHeader
	public string $user          = '2222222222_01';
	public string $signature     = 'pass';
	public string $ekp           = '2222222222';
	public string $participantId = '01';

	//API-Auth
	public string $api_user;
	public string $api_password;
}
