<?php
namespace DD\Exceptions;

use Exception;

class InvalidArgumentException extends Exception {

	public function errorMessage(): string {

		return $this->getMessage();

	}

}
