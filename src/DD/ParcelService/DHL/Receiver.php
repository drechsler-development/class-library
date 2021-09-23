<?php

namespace DD\ParcelService\DHL;

class Receiver extends Contact
{

	public function __construct () { }

	/**
	 * @return $this
	 * @throws ValidationException
	 */
	public function Validate (): Receiver {

		if(empty($this->first_name) && empty($this->last_name)){
			throw new ValidationException(sprintf("% is missing",'First and/or lastname are missing'));
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
