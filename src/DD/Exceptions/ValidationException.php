<?php
namespace DD\Exceptions;

use Exception;

class ValidationException extends Exception {

	public function errorMessage(): string {

		return $this->getMessage();

	}

}
