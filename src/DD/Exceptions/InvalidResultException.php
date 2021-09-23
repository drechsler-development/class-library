<?php
namespace DD\Exceptions;

use Exception;

class InvalidResultException extends Exception {

	public function errorMessage(): string {

		return $this->getMessage();

	}

}
