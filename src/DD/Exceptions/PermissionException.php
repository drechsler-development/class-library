<?php
namespace DD\Exceptions;

use Exception;

class PermissionException extends Exception {

	public function errorMessage(): string {

		return $this->getMessage();

	}

}
