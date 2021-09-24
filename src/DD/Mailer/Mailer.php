<?php

namespace DD\Mailer;

use DD\SystemType;
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
class Mailer extends PHPMailer {

	private bool $isComment;
	public bool $isAdminMail = false;

	public function __construct ($exceptions = null, $isComment = false) {

		parent::__construct ($exceptions);

		$this->isSMTP (); //Set the use of mailer via "SMTP"

		$this->CharSet 		= parent::CHARSET_UTF8;
		$this->Username 	= defined ("SMTP_USER") ? SMTP_USER : '';
		$this->Password 	= defined ("SMTP_PASS") ? SMTP_PASS : '';
		$this->Host 		= defined ("SMTP_SERVER") ? SMTP_SERVER : '';
		$this->isComment 	= (bool)$isComment;
		$this->SMTPAuth 	= defined ("SMTP_AUTH") ? SMTP_AUTH : true;
		$this->SMTPSecure 	= 'tls';

		$systemType = defined("SYSTEMTYPE") ? SYSTEMTYPE : '';

		if($systemType == SystemType::DEV){

			$this->SMTPDebug = 1;
			$folder = defined ("PHP_MAILER_LOGS") ? PHP_MAILER_LOGS : '';
			if(!empty($folder) && is_dir($folder)){

				$this->Debugoutput = function($str, $level) {
					global $folder;
					file_put_contents ($folder.'/'.date ('Y-m-d').'.log', date ('Y-m-d H:i:s')."\t$level\t$str\n", FILE_APPEND | LOCK_EX);
				};

			}

		}

	}

	/**
	 * @param string $email
	 * @param string $name
	 * @param bool $replaceInNonProdSystems
	 * @throws Exception
	 */
	public function DDAddTo(string $email, string $name = "", bool $replaceInNonProdSystems = true){

		$email = $this->PossibleReplaceEmail($email, $replaceInNonProdSystems);
		parent::addAddress ($email, $name);

	}

	/**
	 * @param string $email
	 * @param string $name
	 * @param bool $replaceInNonProdSystems
	 * @throws Exception
	 */
	public function DDAddCc(string $email, string $name = "", bool $replaceInNonProdSystems = true){

		$email = $this->PossibleReplaceEmail($email, $replaceInNonProdSystems);
		parent::addCC ($email, $name);

	}

	/**
	 * @param string $email
	 * @param string $name
	 * @param bool $replaceInNonProdSystems
	 * @throws Exception
	 */
	public function DDAddBcc(string $email, string $name = "", bool $replaceInNonProdSystems = true){

		$email = $this->PossibleReplaceEmail($email, $replaceInNonProdSystems);
		parent::addBCC ($email, $name);

	}

	/**
	 * @return bool|void
	 * @throws Exception
	 */
	public function DDSend($sendToDeveloper = false): bool {

		//Add developer in BCC
		$email = defined("EMAIL_DEVELOPER") ? EMAIL_DEVELOPER : '';
		if(!empty($email) && $sendToDeveloper){
			//To prevent the replacemnet in NON-PROD environments, we need to set the parameter to false!!!
			self::DDAddBcc($email,"",false);
		}

		//Check if standard values from the phpmailer class have been used and replace it with the constants if defined
		if($this->From == 'root@localhost' || $this->From == ''){
			$from = defined ("SMTP_FROM") ? SMTP_FROM : '';
			$fromName = defined ("SMTP_FROM_NAME") ? SMTP_FROM_NAME : '';
			$this->setFrom($from, $fromName);
		}

		//In case we are using the comment functionality this won't be processed
		//as comments (CHats) will be sent to a separate mailbox where the system will handle the email in a different way
		if (!$this->isComment){

			$systemType = defined("SYSTEMTYPE") ? SYSTEMTYPE : '';
			$emailDeveloper = defined("EMAIL_DEVELOPER") ? EMAIL_DEVELOPER : '';

			if($this->isAdminMail){
				$email = $emailDeveloper;
			}else{

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

			if(isset($email)) {
				self::DDAddCc ($email, "", false);
			}

		}

		return parent::send ();

	}

	/**
	 * @param $subject
	 */
	public function DDSubject($subject){
		$this->Subject = $this->PossibleReplaceSubject ($subject);
	}

	/**
	 * @param string $email
	 * @param bool $replaceInNonProdSystems
	 * @return mixed
	 */
	private function PossibleReplaceEmail(string $email, bool $replaceInNonProdSystems = true): string {

		if (!$this->isComment && $replaceInNonProdSystems){

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
	private function PossibleReplaceSubject($subject): string {

		$systemType     = defined ('SYSTEMTYPE') ? SYSTEMTYPE : '';

		return $systemType != SystemType::PROD ? $systemType." :: ".$subject : $subject;

	}

}
