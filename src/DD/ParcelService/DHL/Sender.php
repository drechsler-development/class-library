<?php

namespace DD\ParcelService\DHL;

class Sender extends Contact
{

	public function __construct () { }

	/**
	 * @return $this
	 * @throws ValidationException
	 */
	public function Validate (): Sender {

		if(empty($this->company_name)){
			throw new ValidationException(sprintf("% is missing",'Company Name'));
		}
		if(empty($this->street_name)){
			throw new ValidationException(sprintf("% is missing",'Street Name'));
		}
		if(empty($this->street_number)){
			throw new ValidationException(sprintf("% is missing",'Street Number'));
		}
		if(empty($this->city)){
			throw new ValidationException(sprintf("% is missing",'City'));
		}
		if(empty($this->zip)){
			throw new ValidationException(sprintf("% is missing",'ZIP-Code'));
		}
		if(empty($this->country)){
			throw new ValidationException(sprintf("% is missing",'CountryCode'));
		}

		return $this;

	}

}
