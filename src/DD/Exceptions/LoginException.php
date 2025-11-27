<?php
namespace DD\Exceptions;

use Exception;

class LoginException extends Exception {

	public function errorMessage(): string {

		return $this->getMessage();

	}

}
