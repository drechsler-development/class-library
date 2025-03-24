<?php

namespace DD\WordPress\VABS;

use DD\Database;
use DD\Exceptions\ValidationException;
use Exception;
use PDO;
use PDOException;

class VABSAPIWPSettings {

	public string $apiToken    = '';
	public string $apiClientId = '';
	public string $apiURL      = '';
	public string $dsgvoLink   = '';
	public string $agbLink     = '';
	public string $successPage = '';
	public string $cancelPage  = '';

	public string $textBeforeBooking = '';
	public int    $referrerId        = 0;

	public int    $underConstruction     = 0;
	public string $underConstructionText = '';

	public int    $payPal             = 0;
	public int    $payPalSandbox      = 0;
	public string $payPalClientId     = '';
	public string $payPalClientSecret = '';

	public int    $useStripe           = 0;
	public int    $stripeSandbox       = 0;
	public string $stripeSecretTestKey = '';
	public string $stripeSecretProdKey = '';

	public int    $blockBookingEnabled             = 0;
	public string $blockBookingFrom                = '';
	public string $blockBookingTo                  = '';
	public string $blockBookingText                = '';
	public int    $additionalCalendarStartDays     = 0;
	public string $additionalCalendarStartDaysText = '';

	public int    $useOrderTimes = 0;
	public string $orderTimeFrom = '';
	public string $orderTimeTo   = '';

	public int $debug = 0;

	public VABSAPIWPSettings $row;
	public string            $errorMessage  = '';
	public string            $versionNumber = '';
	public int               $allowKeyPickedUp = 0;
	private string           $table;

	private PDO $conPDO;

	/**
	 * VABSAPIWPSettings constructor.
	 *
	 * @param string      $table
	 * @param string|null $dbUser if you are passing NULL, the Database class expects the global constant defined as DB_USER
	 * @param string|null $dbPass if you are passing NULL, the Database class expects the global constant defined as DB_PASS
	 * @param string|null $dbName if you are passing NULL, the Database class expects the global constant defined as DB_NAME
	 * @param string|null $dbHost if you are passing NULL, the Database class expects the global constant defined as DB_HOST
	 *
	 * @throws ValidationException
	 */
	public function __construct (string $table, string $dbUser = null, string $dbPass = null, string $dbName = null, string $dbHost = null) {

		$this->table  = $table;
		$this->conPDO = Database::getInstance ($dbUser, $dbPass, $dbName, $dbHost);

		$this->CreateTableIfNotExists ();

	}

	/**
	 * @return void
	 * @throws ValidationException
	 */
	public function Load (): void {

		$SQL = "SELECT  
					 
					IFNULL(apiToken,'') as apiToken,
					IFNULL(apiClientId,'') as apiClientId,
					IFNULL(apiURL,'') as apiURL,
					IFNULL(dsgvoLink,'') as dsgvoLink,
					IFNULL(agbLink,'') as agbLink,
					IFNULL(successPage,'') as successPage,
					IFNULL(cancelPage,'') as cancelPage,
					IFNULL(textBeforeBooking,'') as textBeforeBooking,
					IFNULL(referrerId,0) as referrerId,
					
					IFNULL(underConstruction,0) as underConstruction,
					IFNULL(underConstructionText,'') as underConstructionText,
					
					IFNULL(payPal,0) as payPal,
					IFNULL(payPalSandbox,0) as payPalSandbox,
					IFNULL(payPalClientId,'') as payPalClientId,
					IFNULL(payPalClientSecret,'') as payPalClientSecret,
					
					IFNULL(useStripe,0) as useStripe,
					IFNULL(stripeSandbox,0) as stripeSandbox,
					IFNULL(stripeSecretTestKey,'') as stripeSecretTestKey,
					IFNULL(stripeSecretProdKey,'') as stripeSecretProdKey,
					
					IFNULL(blockBookingEnabled,0) as blockBookingEnabled,
					IFNULL(blockBookingFrom,'') as blockBookingFrom,
					IFNULL(blockBookingTo,'') as blockBookingTo,
					IFNULL(blockBookingText,'') as blockBookingText,
					IFNULL(additionalCalendarStartDays,0) as additionalCalendarStartDays,
					IFNULL(additionalCalendarStartDaysText,'') as additionalCalendarStartDaysText,
					
					IFNULL(allowKeyPickedUp,0) as allowKeyPickedUp,
					
					IFNULL(versionNumber,0) as versionNumber

				FROM
					$this->table";
		$stm = $this->conPDO->prepare ($SQL);
		$stm->execute ();

		//the param is needed as we are passing the table name to the constructor of the class that isn't available in the ->row property when we assign the fetched row to it
		$stm->setFetchMode (PDO::FETCH_CLASS, __CLASS__, [$this->table]);
		$this->row = $stm->fetch ();

	}

	/**
	 * Saves the settings into a settings file
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function Save (): bool {

		try {

			if (empty($this->apiURL)) {
				throw new Exception("API URL must not be empty");
			}

			if (empty($this->apiToken)) {
				throw new Exception("API TOKEN must not be empty");
			}

			if (empty($this->apiClientId)) {
				throw new Exception("API ClientId must not be empty");
			}

			$SQL = "UPDATE  
						$this->table 
					SET 
						apiToken = :apiToken,
						apiClientId = :apiClientId,
						apiURL = :apiURL,
						dsgvoLink = :dsgvoLink,
						agbLink = :agbLink,
						successPage = :successPage,
						cancelPage = :cancelPage,
						textBeforeBooking = :textBeforeBooking,
						referrerId = :referrerId,
			
						underConstruction = :underConstruction,
						underConstructionText = :underConstructionText,
						
						payPal = :payPal,
						payPalSandbox = :payPalSandbox,
						payPalClientId = :payPalClientId,
						payPalClientSecret = :payPalClientSecret,
						
						useStripe = :useStripe,
						stripeSandbox = :stripeSandbox,
						stripeSecretTestKey = :stripeSecretTestKey,
						stripeSecretProdKey = :stripeSecretProdKey,
						
						useOrderTimes = :useOrderTimes,
						orderTimeFrom = :orderTimeFrom,
						orderTimeTo = :orderTimeTo,
						
						blockBookingEnabled = :blockBookingEnabled,
						blockBookingFrom = :blockBookingFrom,
						blockBookingTo = :blockBookingTo,
						blockBookingText = :blockBookingText,
						additionalCalendarStartDays = :additionalCalendarStartDays,
						additionalCalendarStartDaysText = :additionalCalendarStartDaysText,
						allowKeyPickedUp = :allowKeyPickedUp";
			$stm = $this->conPDO->prepare ($SQL);
			$stm->bindValue (':apiToken', $this->apiToken);
			$stm->bindValue (':apiClientId', $this->apiClientId);
			$stm->bindValue (':apiURL', $this->apiURL);
			$stm->bindValue (':dsgvoLink', $this->dsgvoLink);
			$stm->bindValue (':agbLink', $this->agbLink);
			$stm->bindValue (':successPage', $this->successPage);
			$stm->bindValue (':cancelPage', $this->cancelPage);
			$stm->bindValue (':textBeforeBooking', $this->textBeforeBooking);
			$stm->bindValue (':referrerId', $this->referrerId, PDO::PARAM_INT);

			$stm->bindValue (':underConstruction', $this->underConstruction, PDO::PARAM_INT);
			$stm->bindValue (':underConstructionText', $this->underConstructionText);

			$stm->bindValue (':payPal', $this->payPal, PDO::PARAM_INT);
			$stm->bindValue (':payPalSandbox', $this->payPalSandbox, PDO::PARAM_INT);
			$stm->bindValue (':payPalClientId', $this->payPalClientId);
			$stm->bindValue (':payPalClientSecret', $this->payPalClientSecret);

			$stm->bindValue (':useStripe', $this->useStripe, PDO::PARAM_INT);
			$stm->bindValue (':stripeSandbox', $this->stripeSandbox, PDO::PARAM_INT);
			$stm->bindValue (':stripeSecretTestKey', $this->stripeSecretTestKey);
			$stm->bindValue (':stripeSecretProdKey', $this->stripeSecretProdKey);

			$stm->bindValue (':useOrderTimes', $this->useOrderTimes, PDO::PARAM_INT);
			$stm->bindValue (':orderTimeFrom', $this->orderTimeFrom);
			$stm->bindValue (':orderTimeTo', $this->orderTimeTo);

			$stm->bindValue (':blockBookingEnabled', $this->blockBookingEnabled, PDO::PARAM_INT);
			if (empty($this->blockBookingFrom)) {
				$stm->bindValue (':blockBookingFrom', null, PDO::PARAM_NULL);
			} else {
				$stm->bindValue (':blockBookingFrom', $this->blockBookingFrom);
			}
			if (empty($this->blockBookingTo)) {
				$stm->bindValue (':blockBookingTo', null, PDO::PARAM_NULL);
			} else {
				$stm->bindValue (':blockBookingTo', $this->blockBookingTo);
			}
			$stm->bindValue (':blockBookingText', $this->blockBookingText);
			$stm->bindValue (':additionalCalendarStartDays', $this->additionalCalendarStartDays);
			$stm->bindValue (':additionalCalendarStartDaysText', $this->additionalCalendarStartDaysText);
			$stm->bindValue (':allowKeyPickedUp', $this->allowKeyPickedUp, PDO::PARAM_INT);

			$stm->execute ();

			return true;

		} catch (Exception $e) {
			$this->errorMessage = $e->getMessage ();
		}

		return false;

	}

	/**
	 * @return void
	 */
	private function CreateTableIfNotExists (): void {

		try {

			$SQL = "SHOW TABLES LIKE '$this->table'";
			$stm = $this->conPDO->prepare ($SQL);
			$stm->execute ();
			if ($stm->rowCount () == 0) {

				$SQL = "CREATE TABLE $this->table (
					`id` TINYINT(1) NOT NULL AUTO_INCREMENT,
					`apiToken` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
					`apiClientId` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
					`apiURL` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
					`dsgvoLink` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
					`agbLink` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
					`successPage` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
					`cancelPage` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
					`textBeforeBooking` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
					`referrerId` SMALLINT(6) NULL DEFAULT NULL,
					
					`underConstruction` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
					`underConstructionText` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
					
					`payPal` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
					`payPalSandbox` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
					`payPalClientId` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
					`payPalClientSecret` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
					
					`useStripe` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
					`stripeSandbox` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
					`stripeSecretTestKey` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
					`stripeSecretProdKey` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
					
					`blockBookingEnabled` TINYINT(1) UNSIGNED NOT NULL,
					`blockBookingFrom` DATE NULL DEFAULT NULL,
					`blockBookingTo` DATE NULL DEFAULT NULL,
					`blockBookingText` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
					`additionalCalendarStartDays` TINYINT(3) UNSIGNED NOT NULL,
					`additionalCalendarStartDaysText` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
					
					`versionNumber` VARCHAR(10) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
					PRIMARY KEY (`id`) USING BTREE
				)
				COLLATE='utf8mb4_unicode_520_ci'
				ENGINE=InnoDB;";
				$stm = $this->conPDO->prepare ($SQL);
				$stm->execute ();

				$SQL = "INSERT INTO 
							$this->table 
						SET 
							id = 1";
				$stm = $this->conPDO->prepare ($SQL);

				$stm->execute ();

			}

			$SQL = "SHOW COLUMNS FROM $this->table LIKE 'blockBookingEnabled'";
			$stm = $this->conPDO->prepare ($SQL);
			$stm->execute ();
			if ($stm->rowCount () == 0) {
				$SQL = "ALTER TABLE $this->table
						ADD COLUMN `blockBookingEnabled` TINYINT(1) UNSIGNED NOT NULL,
						ADD COLUMN `blockBookingFrom` DATE NULL DEFAULT NULL,
						ADD COLUMN `blockBookingTo` DATE NULL DEFAULT NULL,
						ADD COLUMN `blockBookingText` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
						ADD COLUMN `additionalCalendarStartDays` TINYINT(3) UNSIGNED NOT NULL,
						ADD COLUMN `additionalCalendarStartDaysText` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci'";
				$stm = $this->conPDO->prepare ($SQL);
				$stm->execute ();
			}

			$SQL = "SHOW COLUMNS FROM $this->table LIKE 'allowKeyPickedUp'";
			$stm = $this->conPDO->prepare ($SQL);
			$stm->execute ();
			if ($stm->rowCount () == 0) {
				$SQL = "ALTER TABLE $this->table
						ADD COLUMN `allowKeyPickedUp` TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL";
				$stm = $this->conPDO->prepare ($SQL);
				$stm->execute ();
			}

			$SQL = "SHOW COLUMNS FROM $this->table LIKE 'useOrderTimes'";
			$stm = $this->conPDO->prepare ($SQL);
			$stm->execute ();
			if ($stm->rowCount () == 0) {
				$SQL = "ALTER TABLE $this->table
						ADD COLUMN `useOrderTimes` TINYINT(1) UNSIGNED NOT NULL AFTER `additionalCalendarStartDays`,
						ADD COLUMN `orderTimeFrom` TIME NULL DEFAULT NULL AFTER `useOrderTimes`,
						ADD COLUMN `orderTimeTo` TIME NULL DEFAULT NULL AFTER `orderTimeFrom`";
				$stm = $this->conPDO->prepare ($SQL);
				$stm->execute ();
			}

		} catch (PDOException|Exception $e) {
			$this->errorMessage = $e->getMessage ();
		}

	}

}
