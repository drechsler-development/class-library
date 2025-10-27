<?php
namespace DD;

use DD\Exceptions\ValidationException;
use PDO;
use PDOException;

/**
 * This class has only one static method and is aka a Singleton
 */
class Database {

	/***
	 * @var ?PDO
	 */
	public static ?PDO $_instance = null; //The single instance

	/***
	 * Database constructor.
	 */
	private function __construct() {}

	/***
	 * Magic method clone is empty to prevent duplication of connection
	 */
	private function __clone(){}

	/***
	 * @return PDO
	 * @throws ValidationException
	 */
	public static function getInstance (?string $host = null, ?string $dbName = null, ?string $dbUser = null, ?string $dbPass = null): ?PDO {

		if($host && $dbName && $dbUser && $dbPass){
			define('DB_HOST', $host);
			define('DB_NAME', $dbName);
			define('DB_USER', $dbUser);
			define('DB_PASS', $dbPass);
		}else{
			//Check if global constants are defined
			if(!defined ("DB_HOST")){
				throw new ValidationException("DB_HOST not defined");
			}

			if(!defined ("DB_NAME")){
				throw new ValidationException('DB_NAME not defined');
			}

			if(!defined ("DB_USER")){
				throw new ValidationException('DB_USER not defined');
			}

			if(!defined ("DB_PASS")){
				throw new ValidationException('DB_PASS not defined');
			}
		}

		$pdoOptions = array(
			PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_EMULATE_PREPARES => false,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		);

		if(!defined ("DB_HOST")){
			throw new ValidationException("DB_HOST not defined");
		}

		if(!defined ("DB_NAME")){
			throw new ValidationException('DB_NAME not defined');
		}

		if(!defined ("DB_USER")){
			throw new ValidationException('DB_USER not defined');
		}

		if(!defined ("DB_PASS")){
			throw new ValidationException('DB_PASS not defined');
		}

		try {
			if (!self::$_instance){
				self::$_instance = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS, $pdoOptions);
			}
		} catch (PDOException $e) {
			throw new PDOException("File: ".__FILE__."<br> Method:".__FUNCTION__." <br>Line: ".__LINE__."<br>Error: ".$e->getMessage ());
		}

		return self::$_instance;
	}

}
