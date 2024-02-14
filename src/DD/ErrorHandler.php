<?php /** @noinspection PhpUndefinedConstantInspection */

namespace DD;

use DD\Exceptions\ValidationException;
use DD\Mailer\Mailer;
use ErrorException;
use Exception;
use PDOException;
use Throwable;
use const DD\Exceptions\SHOW_ERRORS;
use const DD\Exceptions\SYSTEMTYPE_NAME;

class ErrorHandler {

	/**
	 * Error handler. Convert all errors to Exceptions by throwing an ErrorException.
	 *
	 * @param int    $level   Error level
	 * @param string $message Error message
	 * @param string $file    Filename the error was raised in
	 * @param int    $line    Line number in the file
	 *
	 * @return void
	 * @throws ErrorException
	 */
	public static function SetErrorHandler (int $level, string $message, string $file, int $line): void {

		if (error_reporting () !== 0) {
			throw new ErrorException($message, 0, $level, $file, $line);
		}
	}

	/**
	 * Exception handler.
	 *
	 * @param Throwable $exception The exception
	 *
	 * @return void
	 */
	public static function SetExceptionHandler (Throwable $exception): void {

		try {
			// Code is 404 (not found) or 500 (general error)
			$code = $exception->getCode ();
			$code = $code != 404 ? 200 : $code;

			http_response_code ($code);

			$showError = defined ('SHOW_ERRORS');

			if ($showError && SHOW_ERRORS) {

				$message = str_replace ('#', '<br>#', $exception->getMessage ());
				//$message.= str_replace ('Stack trace:', '<br>StackTrace:#######################', $exception->getTraceAsString ());
				$message = str_replace ($_SERVER['DOCUMENT_ROOT'], '', $message);
				$message = str_replace (str_replace ('/public', '', $_SERVER['DOCUMENT_ROOT']), '', $message);

				echo "<h1>Fatal error</h1>";

				echo "<p>Uncaught: <b>" . strtoupper (get_class ($exception)) . "</b></p>";

				echo "<p>Message: <em><b>" . $message . "</b></em></p>";

				$file = $exception->getFile ();
				//remove all those part until the file name starts with "/App"
				$file = substr ($file, strpos ($file, '/App'));
				echo "<p>Thrown in <b>" . $file . "</b> on line <b>" . $exception->getLine () . "</b></p>";

				$traceString = $exception->getTraceAsString ();
				$array       = explode ("#", $traceString);
				//remove first element from array as it only contains a blank space
				array_shift ($array);
				$i = 0;
				foreach ($array as $row) {
					if ($i == 0) {
						//divide the string based on ":" and take the first part
						$parts = explode (":", $row);
						$part1 = $parts[0];
						$part2 = $parts[1];
						$row   = $part1 . "<br><b>" . $part2 . "</b>";

					} else {
						$row = str_replace (str_replace ('/public', '', $_SERVER['DOCUMENT_ROOT']), '', $row);
					}
					echo "#" . $row . "<br>";
					$i++;
				}

			} else {

				$log = dirname (__DIR__) . '/logs/' . date ('Y-m-d') . '.txt';
				ini_set ('error_log', $log);

				$message = "Uncaught exception: '" . get_class ($exception) . "'";
				$message .= " with message '" . $exception->getMessage () . "'";
				$message .= "\nStack trace: " . $exception->getTraceAsString ();
				$message .= "\nThrown in '" . $exception->getFile () . "' on line " . $exception->getLine ();

				error_log ($message);

				echo $code == 404 ? "<h1>Page not found</h1>" : "<h1>An error occurred</h1>";
			}
		} catch (Exception $e) {
			echo self::HandleErrorMessage ($e);
		}
	}

	/**
	 * Sends an Email to the ADMINISTRATOR and returns back the error based on the current SYSTEMTYPE
	 *
	 * @param Exception $e
	 *
	 * @return string
	 */
	public static function HandleErrorMessage (Exception $e): string {

		//Check if class exists
		if (!class_exists ('Mailer')) {
			return $e->getMessage ();
		}

		$message = $e->getMessage ();
		$trace   = $e->getTraceAsString ();

		if ($e instanceof ValidationException) {
			$subject = Mailer::EMAIL_SUBJECT_VALIDATION_EXCEPTION;
		} else if ($e instanceof PDOException) {
			$subject = Mailer::EMAIL_SUBJECT_DB_EXCEPTION;
			if (str_contains ($message, 'Duplicate')) {
				$message = "Duplicate Entry";
			}
		} else {
			$subject = Mailer::EMAIL_SUBJECT_EXCEPTION;
		}

		Mailer::SendAdminMail ($message, $subject);

		$traceMessage = $e instanceof ValidationException ? '' : "\r\n" . $trace;

		return SYSTEMTYPE_NAME == SystemType::TEST ||
		SYSTEMTYPE_NAME == SystemType::DEV ||
		SYSTEMTYPE_NAME == SystemType::LOCAL ||
		$e instanceof ValidationException ? $message . $traceMessage : "Unknown Error (yet). Administrator has been informed";


	}

}
