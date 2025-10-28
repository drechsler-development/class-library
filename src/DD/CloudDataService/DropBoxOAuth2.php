<?php

namespace DD\CloudDataService;

use DateTime;
use Exception;

class DropBoxOAuth2
{

	#region CONSTANTS

	const DROPBOX_CONTENT_URL = "https://content.dropboxapi.com/2";
	const DROPBOX_URL         = "https://api.dropboxapi.com/2";
	const DROPBOX_TOKEN_URL   = "https://api.dropbox.com/1/oauth2/token";
	const DROPBOX_AUTH_URL    = "https://www.dropbox.com/1/oauth2/authorize?client_id=%s&response_type=code&token_access_type=offline&redirect_uri=%s&state=%s";

	const TOKEN_VALID     = 1;
	const TOKEN_REFRESHED = 2;

	#endregion

	#region private vars

	/**
	 * this will contain the necessary (required) values from the json_decoded curl_response
	 * and will be returned in the appropriate methods for a better further process in your software
	 * @var array
	 */
	public array $responseArray = ['error' => false];

	/**
	 * This is your DropBOX API client id (aka app key)
	 * @var string
	 */
	private string $apiClientId;

	/**
	 * This is your DropBOX API client secret (aka app secret)
	 * @var string
	 */
	private string $apiSecret;

	#endregion

	#region public vars

	/**
	 * This is the authorization code that you get back in the GetAuthUrl in the $_GET parameter
	 * You need to set that by pass through the code from the $_GET['code'] and call then the GetToken() method
	 * @var string
	 */
	public string $code = '';

	/**
	 * This will be your (main) token that needs to be passed in any call to your DropBox API
	 * You should save that either in your systems database or to other storages to use it in case your token has been expired!
	 * @var string
	 */
	public string $token = '';

	/**
	 * This value contains the refresh token you need to use to refresh your actual token
	 * That will be either set in the first call to get the token (GetToken)
	 * You should save that either in your systems database or to other storages to use it in case your token has been expired!
	 * @var string
	 */
	public string $refreshToken = '';

	/**
	 * This contains the current expiration date.
	 * That will be either set in the first call to get the token (GetToken)
	 * or in the method where we get a refreshed token in case a token already exists (GetRefreshToken)
	 * You should save that either in your systems database or to other storages to check if a token has expired!
	 * @var DateTime
	 */
	public DateTime $expireDate;

	/**
	 * This is the callBackUrl to your script that will call the method GetToken() after you got the (Authorization) code
	 * @var string
	 */
	public string $callBackUrl = '';

	#endregion

	/**
	 * DropBox constructor.
	 */
	public function __construct (string $apiClientId, string $apiSecret) {

		$this->token       = $_SESSION['DROPBOX_TOKEN'] ?? '';
		$this->apiClientId = $apiClientId;
		$this->apiSecret   = $apiSecret;

	}

	/**
	 * This methods returns back the Authorization URL to authorize your software to use your DropBox API
	 * @return string
	 * @throws Exception
	 */
	public function GetAuthUrl (): string {

		if (empty($this->apiClientId)) {
			throw new Exception(__METHOD__.": The appClientId (aka app key) has not been set to call this method");
		}

		if (empty($this->callBackUrl)) {
			throw new Exception(__METHOD__.": The callBackUrl has not been set to call this method");
		}

		return sprintf (self::DROPBOX_AUTH_URL, $this->apiClientId, $this->callBackUrl, rand (10000, 1000000));

	}

	/**
	 * @throws Exception
	 */
	public function CheckApi (): bool {

		if (empty($this->token)) {
			throw new Exception(__METHOD__.": The Token has not been set to call this method");
		}

		$endPoint = '/check/app';
		$url      = self::DROPBOX_URL.$endPoint;
		$header   = [
			'Content-Type: application/json',
		];
		$data     = [
			"query" => "foo"
		];

		$response = $this->MakeCurlRequest ($url, $header, $data, true);

		if (!empty($response['error'])) {
			throw new Exception(__METHOD__.": Error: ".$response['error']." ErrorDescription: ".$response['error_description']);
		} else if (empty($response['result']['result'] || $response['result']['result'] != "foo")) {
			throw new Exception("No proper response came back from the CheckApi method");
		}

		return true;

	}

	/**
	 * This method will get the token after you have authorized your software to use the DropBox API for your DropBox Account
	 * You should implement this call in your callBack script you provided in the callBackUrl
	 * @throws Exception
	 */
	public function GetToken (): void {

		if (empty($this->code)) {
			throw new Exception(__METHOD__.": The code (AuthorizationCode) has not been set to call this method");
		}

		$url    = self::DROPBOX_TOKEN_URL;
		$header = [
			'Content-Type: application/x-www-form-urlencoded',
		];
		$data   = [
			"code"         => $this->code,
			"grant_type"   => "authorization_code",
			"redirect_uri" => $this->callBackUrl,
		];

		$response            = $this->MakeCurlRequest ($url, $header, $data);
		$this->responseArray = $response;

		if (!empty($response['error'])) {
			throw new Exception(__METHOD__.": Error: ".$response['error']." ErrorDescription: ".$response['error_description']);
		} else if (!empty($response['result'])) {
			$result             = $response['result'];
			$this->token        = $result['access_token'];
			$this->refreshToken = $result['refresh_token'] ?? '';
			$seconds            = (int)$result['expires_in'];
			$this->expireDate   = (new DateTime())->modify ("+$seconds seconds");
		} else {
			throw new Exception("No access token has been provided in the result");
		}

	}

	/**
	 * This method will refresh the actuall main token in case it is expired based on the refresh token you got in the first GetToken call
	 * @throws Exception
	 */
	public function GetRefreshToken (): void {

		if (empty($this->refreshToken)) {
			throw new Exception(__METHOD__.": The refreshToken has not been set to call this method");
		}

		$url    = self::DROPBOX_TOKEN_URL;
		$header = [
			'Content-Type: application/x-www-form-urlencoded',
		];
		$data   = [
			"refresh_token" => $this->refreshToken,
			"grant_type"    => "refresh_token",
		];

		$response = $this->MakeCurlRequest ($url, $header, $data);

		if (!empty($response['error'])) {
			throw new Exception(__METHOD__.":".$response['error']);
		} else if (!empty($response['result'])) {
			$result = $response['result'];
			if (!empty($result['access_token'])) {
				$this->token      = $result['access_token'];
				$seconds          = (int)$result['expires_in'];
				$this->expireDate = (new DateTime())->modify ("+$seconds seconds");
			} else {
				throw new Exception("No access token has been provided in the result.");
			}
		} else {
			throw new Exception("Unknown error in the result.");
		}

	}

	/**
	 * This Method creates a temporary link (valid for 4 hours) to download a file without any authorization.
	 * Can be used if you want to send it via Email or to any external party/partner/user
	 * @throws Exception
	 */
	public function GetTemporaryLink (string $path): string {

		$endPoint = "/files/get_temporary_link";
		$url      = self::DROPBOX_URL.$endPoint;
		$header   = [
			"Authorization: Bearer $this->token",
			'Content-Type: application/json',
		];

		//As this is a POST Request we need to set the data and method
		$data = [
			"path" => $path
		];

		$response = $this->MakeCurlRequest ($url, $header, $data, true);

		$this->responseArray = $response;

		return $response['link'] ?? '';

	}

	/**
	 * This method will upload a file to your DropBox
	 * @param string $filePath that will be posted from the server
	 * @param string $folder like 'test/some_other_folder/other_folder'
	 * @return array (associative)
	 * @throws Exception
	 */
	public function UploadFile (string $filePath, string $folder = "", bool $autoRename = true): array {

		if (empty($this->token)) {
			throw new Exception(__METHOD__.":Token is not set");
		}

		$this->responseArray['error'] = "";

		if (substr ($folder, 0, 1) == "/") {
			$folder = substr ($folder, 1);
		}
		if (substr ($folder, -1) == "/") {
			$folder = substr ($folder, 0, -1);
		}

		$endPoint = '/files/upload';

		//$folder = strlen ($folder) > 0 ? "/".$this->appFolder."/".$folder : "/".$this->appFolder;
		$path = !empty($folder) ? "/".$folder."/".basename ($filePath) : basename ($filePath);

		$args   = json_encode ([
			"path"       => $path,
			"mode"       => "add",
			"autorename" => $autoRename,
			"mute"       => false
		]);
		$header = [
			'Authorization: Bearer '.$this->token,
			'Content-Type: application/octet-stream',
			'Dropbox-API-Arg: '.$args
		];

		try {

			$curl     = curl_init (self::DROPBOX_CONTENT_URL.$endPoint);
			$path     = $filePath;
			$fp       = fopen ($path, 'rb');
			$filesize = filesize ($path);

			curl_setopt ($curl, CURLOPT_HTTPHEADER, $header);
			curl_setopt ($curl, CURLOPT_POST, true);
			curl_setopt ($curl, CURLOPT_POSTFIELDS, fread ($fp, $filesize));
			curl_setopt ($curl, CURLOPT_RETURNTRANSFER, true);

			$response  = curl_exec ($curl);
			$http_code = curl_getinfo ($curl, CURLINFO_HTTP_CODE);

			if ($http_code == 200) {
				$this->responseArray['result'] = json_decode ($response, true);
			} else {
				$this->responseArray['error'] = json_decode ($response, true);
			}

			curl_close ($curl);

		} catch (Exception $e) {
			$this->responseArray['error'] = $e->getMessage ();
		}

		return $this->responseArray;

	}

	/**
	 * Gets the current used DropBox space (just for testing if the access token is working
	 * @return array (associative)
	 * @throws Exception
	 */
	public function ListFolders (string $path, bool $recursive = false): array {

		if (empty($this->token)) {
			throw new Exception("Token is not set");
		}

		$this->responseArray['error'] = "";

		$endPoint = '/files/list_folder';

		$header = [
			'Authorization: Bearer '.$this->token,
			'Content-Type: application/json',
		];

		$parameters = [
			"path"                    => $path,
			"recursive"               => $recursive,
			"include_mounted_folders" => false
		];

		$url                 = self::DROPBOX_URL.$endPoint;
		$this->responseArray = self::MakeCurlRequest ($url, $header, $parameters, true);

		/*try {

			$curl = curl_init (self::DROPBOX_URL.$endPoint);

			curl_setopt ($curl, CURLOPT_HTTPHEADER, $header);
			curl_setopt ($curl, CURLOPT_POST, true);
			curl_setopt ($curl, CURLOPT_POSTFIELDS, $parameters);
			curl_setopt ($curl, CURLOPT_RETURNTRANSFER, true);

			$response  = curl_exec ($curl);
			$http_code = curl_getinfo ($curl, CURLINFO_HTTP_CODE);

			if ($http_code == 200) {
				$this->responseArray['result'] = json_decode ($response, true);
			} else {
				$this->responseArray['error']        = 1;
				$this->responseArray['errormessage'] = $response;
			}

			curl_close ($curl);

		} catch (Exception $e) {
			$this->responseArray['error'] = $e->getMessage ();
		}*/

		return $this->responseArray;

	}

	/**
	 * This method checks if a token has been expired
	 * It requires that your system will store the expiration date that you need to pass as parameter
	 * If $gapInSeconds will be provided the token will be 'calculated' as expired then NOW - gapInSeconds > expirationDate.
	 * Can be used in case you have a bigger upload to force to create a new fresh token that will be valid for the next 4 hours
	 * @param DateTime $expirationDate
	 * @param int|null $gapInSeconds
	 * @return bool
	 */
	public static function CheckTokenExired (DateTime $expirationDate, int $gapInSeconds = 0): bool {

		$now = empty($gapInSeconds) ? new DateTime() : (new DateTime())->modify ("-$gapInSeconds seconds");

		return $now > $expirationDate;

	}

	/**
	 * @param string $url
	 * @param array $header
	 * @param array $data
	 * @param bool $sendDataAsJson
	 * @return array
	 * @throws Exception
	 */
	private function MakeCurlRequest (string $url, array $header, array $data, bool $sendDataAsJson = false): array {

		$curl = curl_init ($url);

		$parameters = $sendDataAsJson ? json_encode ($data) : http_build_query ($data);

		curl_setopt ($curl, CURLOPT_POST, true);
		curl_setopt ($curl, CURLOPT_POSTFIELDS, $parameters);
		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt ($curl, CURLOPT_USERPWD, $this->apiClientId.':'.$this->apiSecret);

		$curl_response = curl_exec ($curl);

		$http_code = curl_getinfo ($curl, CURLINFO_HTTP_CODE);

		if ($http_code == 200) {
			$this->responseArray['result'] = json_decode ($curl_response, true);
		} else {
			$this->responseArray['error']        = 1;
			$this->responseArray['errormessage'] = json_decode ($curl_response, true);
		}

		curl_close ($curl);

		return $this->responseArray;

	}

	/**
	 * This method checks if the current token is still valid and not expired by calling the CheckApi method.
	 * If the response is ok. All is fine, otherwise it will check if the response contains an error message reflecting to the expiration of the token
	 * It will then automatically call the GetRefreshToken to refresh the Token and pass it to the application
	 * @return int
	 * @throws Exception
	 */
	private function CheckAndGetToken (): int {

		if (empty($this->token)) {
			throw new Exception("Token is not set");
		}

		try {

			if ($this->CheckApi ()) {
				return self::TOKEN_VALID;
			}

		} catch (Exception $e) {

			$message = $e->getMessage ();

			if (strstr ($message, 'expire')) {

				if (empty($this->refreshToken)) {
					throw new Exception("RefreshToken is not set");
				}

				$this->GetRefreshToken ();

				return self::TOKEN_REFRESHED;

			} else {
				throw new Exception(__METHOD__." Unknown Response from Exception Message");
			}

		}

		return 0;

	}

}
