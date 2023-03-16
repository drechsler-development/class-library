<?php

namespace DD\Mailer;

use DD\Exceptions\ValidationException;
use DD\SystemType;
use DD\Utils;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * This class is an extension of the phpmailer class and allows you to
 * - replace the receiver email adresses with any address you declared as a global constant named as EMAIL_DEVELOPER and
 * - add a prefix with your environemnt name to the subject to see from which environment the email was sent
 * whenever you are using different environments.
 * This prevents sending out emails to the original receiver, whenever you are using a NON-PROD enviropnment
 * To use this class you must define a constant named as SYSTEMTYPE and assign a value from the type DD\SystemType based on the environment you are using it can be DEV, TEST and so on
 * I am using a php file named domaincontroller.php I include on top of any script. In that file I define this SYSTEMTYPE based on the domain. Like if ($domain == "test.domain.de") define("SYSTEMTYPE,SystemType::TEST);
 * You also must declare the SMTP_USER, SMTP_PASS, SMTP_SERVER somehwere as global constants, to be able to send emails via SMTP
 */
class Mailer extends PHPMailer
{

	const EMAIL_SUBJECT_EXCEPTION                   = "Exception";
	const EMAIL_SUBJECT_DEBUG                       = 'DEBUG Information';
	const EMAIL_SUBJECT_DB_EXCEPTION                = 'DB Exception';
	const EMAIL_SUBJECT_INVALID_ARGUMNENT_EXCEPTION = 'Invalid Argument Exception';
	const EMAIL_SUBJECT_VALIDATION_EXCEPTION        = 'Validation Exception';
	const EMAIL_SUBJECT_PERMISSION_EXCEPTION        = 'Permission Exception';
	const LOG_FOLDER                                = 'logs/email';

	/**
	 * Defines if the mail is a comment mail for chat functionality
	 * @var bool
	 */
	private bool $isComment;
	/**
	 * defines if the email will be sent to an admin. In that case the developer will get the email
	 * @var bool
	 */
	public bool  $isAdminMail = false;

	public function __construct ($exceptions = null, $isComment = false) {

		parent::__construct ($exceptions);

		$this->isSMTP (); //Set the use of mailer via "SMTP"

		$this->CharSet    = parent::CHARSET_UTF8;

		//These are the standard settings for the mailer they need to be defined as global constants in your project
		$this->Username   = defined (constant_name: "SMTP_USER") ? SMTP_USER : '';
		$this->Password   = defined ("SMTP_PASS") ? SMTP_PASS : '';
		$this->Host       = defined ("SMTP_SERVER") ? SMTP_SERVER : '';
		$this->SMTPAuth   = defined ("SMTP_AUTH") ? SMTP_AUTH : true;

		// However, if you want to use a different SMTP server that the global defined one, you can use the OVERWRITE constants

		$this->Username = defined ("SMTP_USER_OVERWRITE") ? SMTP_USER_OVERWRITE : $this->Username;
		$this->Password = defined ("SMTP_PASS_OVERWRITE") ? SMTP_PASS_OVERWRITE : $this->Password;
		$this->Host     = defined ("SMTP_SERVER_OVERWRITE") ? SMTP_SERVER_OVERWRITE : $this->Host;
		$this->SMTPAuth = defined ("SMTP_AUTH_OVERWRITE") ? SMTP_AUTH_OVERWRITE : $this->SMTPAuth;

		$this->SMTPSecure = 'tls';
		$this->isComment  = (bool)$isComment;

		$systemType = defined ("SYSTEMTYPE") ? SYSTEMTYPE : '';
		$folder     = defined ("PHP_MAILER_LOGS") ? PHP_MAILER_LOGS : '';
		$isFolder   = !empty($folder) && is_dir ($folder);

		if($isFolder){

			switch($systemType){

				case SystemType::DEV:
				case SystemType::DEMO:
				case SystemType::LOCAL:
				case SystemType::STAGING:
				case SystemType::REFERENCE:
					$level = Logger::DEBUG;
					break;
				case SystemType::TEST:
					$level = Logger::INFO;
					break;
				case SystemType::PROD:
					$level = Logger::WARNING;
					break;
				default:
					$level = Logger::ERROR;
					break;
			}

			if ($systemType == SystemType::DEV) {

				$logger = new Logger('logger');
				$logger->pushHandler (new StreamHandler($folder.'/'.date ('Y-m-d').'.log', $level));

				$this->SMTPDebug = 1;

				$this->Debugoutput = $logger;

			}

		}

	}

	/**
	 * @param string $email
	 * @param string $name
	 * @param bool $replaceInNonProdSystems
	 * @throws Exception
	 */
	public function DDAddTo (string $email, string $name = "", bool $replaceInNonProdSystems = true) {

		$email = $this->PossibleReplaceEmail ($email, $replaceInNonProdSystems);
		parent::addAddress ($email, $name);

	}

	/**
	 * @param string $email
	 * @param string $name
	 * @param bool $replaceInNonProdSystems
	 * @throws Exception
	 */
	public function DDAddCc (string $email, string $name = "", bool $replaceInNonProdSystems = true) {

		$email = $this->PossibleReplaceEmail ($email, $replaceInNonProdSystems);
		parent::addCC ($email, $name);

	}

	/**
	 * @param string $email
	 * @param string $name
	 * @param bool $replaceInNonProdSystems
	 * @throws Exception
	 */
	public function DDAddBcc (string $email, string $name = "", bool $replaceInNonProdSystems = true) {

		$email = $this->PossibleReplaceEmail ($email, $replaceInNonProdSystems);
		parent::addBCC ($email, $name);

	}

	/**
	 * @param bool $sendToDeveloper
	 * @return bool
	 * @throws Exception
	 */
	public function DDSend (bool $sendToDeveloper = false): bool {

		//Add developer in BCC
		$email = defined ("EMAIL_DEVELOPER") ? EMAIL_DEVELOPER : '';
		if (!empty($email) && $sendToDeveloper) {
			//To prevent the replacemnet in NON-PROD environments, we need to set the parameter to false!!!
			self::DDAddBcc ($email, "", false);
		}

		//Check if standard values from the phpmailer class have been used and replace it with the constants if defined
		if ($this->From == 'root@localhost' || $this->From == '') {
			$from     = defined ("SMTP_FROM") ? SMTP_FROM : '';
			$fromName = defined ("SMTP_FROM_NAME") ? SMTP_FROM_NAME : '';
			$this->setFrom ($from, $fromName);
		}

		//In case we are using the comment functionality this won't be processed
		//as comments (CHats) will be sent to a separate mailbox where the system will handle the email in a different way
		if (!$this->isComment) {

			$systemType     = defined ("SYSTEMTYPE") ? SYSTEMTYPE : '';
			$emailDeveloper = defined ("EMAIL_DEVELOPER") ? EMAIL_DEVELOPER : '';

			if ($this->isAdminMail) {
				$email = $emailDeveloper;
			} else {

				switch ($systemType) {
					case SystemType::PROD:
					case SystemType::TEST:
						$email = $_SESSION['login']['email'] ?? $emailDeveloper;
						break;
					//In all NON-PROD we will use the DEVELOPER email address
					case SystemType::REFERENCE:
					case SystemType::STAGING:
					case SystemType::DEV:
					case SystemType::DEMO:
						$email = $emailDeveloper;
						break;
					default:
						break;
				}

			}

			if (isset($email)) {
				self::DDAddCc ($email, "", false);
			}

		}

		return parent::send ();

	}

	/**
	 * @param $subject
	 */
	public function DDSubject ($subject) {

		$this->Subject = $this->PossibleReplaceSubject ($subject);
	}

	/**
	 * @param string $email
	 * @param bool $replaceInNonProdSystems
	 * @return mixed
	 */
	private function PossibleReplaceEmail (string $email, bool $replaceInNonProdSystems = true): string {

		if (!$this->isComment && $replaceInNonProdSystems) {

			$systemType     = defined ('SYSTEMTYPE') ? SYSTEMTYPE : '';
			$emailDeveloper = defined ('EMAIL_DEVELOPER') ? EMAIL_DEVELOPER : '';

			switch ($systemType) {
				case SystemType::DEV:
				case SystemType::REFERENCE:
				case SystemType::STAGING:
				case SystemType::LOCAL:
					$email = $emailDeveloper;
					break;
				case SystemType::TEST:
					$email = $_SESSION['login']['email'] ?? $emailDeveloper;
					break;
				default:
					break;
			}

		}

		return $email;

	}

	/**
	 * Based on your environment type this method will add a prefix to the subject,
	 * so that you can see in the subject from which environment the email was sent
	 * @param $subject
	 * @return string
	 */
	private function PossibleReplaceSubject ($subject): string {

		$systemType = defined ('SYSTEMTYPE_NAME') ? SYSTEMTYPE_NAME : '';

		return $systemType != SystemType::PROD ? $systemType." :: ".$subject : $subject;

	}

	/**
	 * Send an email to the administrator/developer
	 */
	public static function SendAdminMail (string $body, string $subject = '', string $filePath = '', string $fileName = '') {

		ob_start ();
		Utils::PrintStack (debug_backtrace ());
		$varBacktrace = ob_get_contents ();
		ob_end_clean ();

		try {

			$recipient = defined ("EMAIL_DEVELOPER") ? EMAIL_DEVELOPER : '';
			if (empty($recipient)) {
				throw new ValidationException("No constant EMAIL_DEVELOPER defined");
			}

			//create a new Email-Objekt as this is a static method
			$mail              = new Mailer();
			$mail->isAdminMail = true;
			$mail->addReplyTo ('noreply@'.$_SERVER['HTTP_HOST']);
			$mail->DDAddTo ($recipient, false, false);
			$subject = strlen (trim ($subject)) > 0 ? trim ($subject) : 'Email für VABS Admininistrator';
			$mail->DDSubject ($subject);
			//Body
			$body = str_replace ('::', '<br>', $body);
			$body = str_replace ("\n", '<br>', $body);
			$body = str_replace (' Fehler: ', '<br> Fehler: ', $body);
			$body .= '<br>'.$varBacktrace;

			//if you are using a CMS system with logged in users,
			//you can add them to the end of the body, so you know who has thrown that error
			if (!empty($_SESSION['login'])) {
				$userFirstName = $_SESSION['login']['firstname'] ?? '';
				$userLastname  = $_SESSION['login']['lastname'] ?? '';
				$userId        = $_SESSION['login']['id'] ?? '';
				$body          .= '<br>User: '.$userFirstName.' '.$userLastname.' ('.$userId.')';
			}

			$mail->msgHTML ($body);

			if (!empty($filePath) && !empty($fileName) && file_exists ($filePath)) {
				$mail->addAttachment ($filePath, $fileName);
			}

			//Send the mail
			if (!$mail->DDSend (true)) {
				throw new Exception($mail->ErrorInfo);
			}

		} catch (Exception|ValidationException $e) {

			self::Log ($e->getMessage ());

		}

	}

	/**
	 * Logs a message to a daily log file
	 * @param string $message
	 */
	public static function Log (string $message = '') {

		try {

			$loginId = $_SESSION['login']['id'] ?? 0;

			$path = $_SERVER['DOCUMENT_ROOT'].'/'.self::LOG_FOLDER;

			if (!@file_exists ($path)) {
				@mkdir ($path, 0777, true);
			}

			$date       = date ('Y-m-d');
			$time       = date ('H:i:s');
			$text       = '';
			$fileName   = $path.'/'.$date.'.log';
			$backTrace  = debug_backtrace ();
			$scriptPath = $backTrace[0]['file'];

			//Write header Data if file not exists
			if (!@file_exists ($fileName)) {
				$text .= "Date\tTime\tUser\tFile\tMessage\r\n";
			} // ENDE

			$text .= $date."\t".$time."\t".$loginId."\t".$scriptPath."\t".$message;

			$filestream = @fopen ($fileName, 'a');
			if ($filestream !== false) {
				@fwrite ($filestream, $text."\r\n");
				@fclose ($filestream);
			} else {
				throw new Exception("File could not be wrote to the email log folder");
			}

		} catch (Exception $e) {
			error_log ($e->getMessage ());
		}

	}

}
