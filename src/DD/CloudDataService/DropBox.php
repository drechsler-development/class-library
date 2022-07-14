<?php

namespace DD\CloudDataService;

use DD\Exceptions\ValidationException;
use Exception;


class DropBox {

	//const DROPBOX_HOME_URL    = 'https://www.dropbox.com/home';
	const DROPBOX_CONTENT_URL = 'https://content.dropboxapi.com/2';
	const DROPBOX_URL         = 'https://api.dropboxapi.com/2';

	#region public vars

	/**
	 * @var array
	 */
	private array $responseArray = ['error' => false];

	/**
	 * @var string
	 */
	private string $rootFolder = '';

	#endregion

	#region private vars

	/**
	 * @var string
	 */
	public string $token = '';

	#endregion

	/**
	 * DropBox constructor.
	 * @param string $accessToken
	 * @param bool $useRootFolder if true, the defined ROOT folder will be used. Set only to false if you have already created a folder, and it is assigned in your DropBox API settings!
	 * @throws ValidationException
	 */
	public function __construct (string $accessToken, bool $useRootFolder = true) {

		$this->token = $accessToken;

		if($useRootFolder){
			if(!defined ("DROPBOX_ROOT")){
				throw new ValidationException("Dropbox root folder needs to be setup as a constant in a global config file, where this class has access to it");
			}
			$this->rootFolder = '/'.DROPBOX_ROOT;
		}

		$this->rootFolder = defined ('SYSTEMTYPE_NAME') ? $this->rootFolder.'/'.SYSTEMTYPE_NAME : $this->rootFolder;

	}

	/**
	 * @param string $file that will be posted from the server
	 * @param string $folder like 'test/some_other_folder/other_folder'
	 * @return array (associative)
	 */
	public function PostFile(string $file, string $folder = "") : array {

		$this->responseArray['error'] = "";

		$endPoint = '/files/upload';

		$path = $folder != "" ? $folder."/".basename($file) : basename($file);
		$path = $this->rootFolder."/".$path;

		$args = json_encode(
							array(
								"path"=> $path,
								"mode" => "add",
								"autorename" => true,
								"mute" => false
							)
		);
		$header = [
			'Authorization: Bearer '.$this->token,
			'Content-Type: application/octet-stream',
			'Dropbox-API-Arg: '.$args
		];

		try {

			$curl = curl_init (self::DROPBOX_CONTENT_URL.$endPoint);
			$path = $file;
			$fp = fopen($path, 'rb');
			$filesize = filesize($path);

			curl_setopt ($curl, CURLOPT_HTTPHEADER,$header);
			curl_setopt ($curl, CURLOPT_POST, true);
			curl_setopt ($curl, CURLOPT_POSTFIELDS, fread($fp, $filesize));
			curl_setopt ($curl, CURLOPT_RETURNTRANSFER, true);

			$response = curl_exec ($curl);
			$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

			if($http_code == 200){
				$this->responseArray['result'] = json_decode ($response,true);
			}else{
				$this->responseArray['error'] = json_decode ($response,true);
			}

			curl_close ($curl);

		} catch (Exception $e){
			$this->responseArray['error'] = $e->getMessage();
		}

		return $this->responseArray;

	}

	/**
	 * Gets the current used DropBox space (just for testing if the access token is working
	 * @return array (associative)
	 */
	public function GetFolders() : array {

		$this->responseArray['error'] = "";

		$endPoint = '/files/list_folder';

		$header = [
			'Authorization: Bearer '.$this->token,
			'Content-Type: application/json',
		];

		$postFields = json_encode(
			array(
				"path"=> "",
				"recursive" => false,
				"include_mounted_folders" => false
			)
		);

		try {

			$curl = curl_init (self::DROPBOX_URL.$endPoint);

			curl_setopt ($curl, CURLOPT_HTTPHEADER,$header);
			curl_setopt ($curl, CURLOPT_POST, true);
			curl_setopt ($curl, CURLOPT_POSTFIELDS, $postFields);
			curl_setopt ($curl, CURLOPT_RETURNTRANSFER, true);

			$response = curl_exec ($curl);
			$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

			if($http_code == 200){
				$this->responseArray['result'] = json_decode ($response,true);
			}else{
				$this->responseArray['error'] = 1;
				$this->responseArray['errormessage'] = $response; //json_decode ($response,true);
			}

			curl_close ($curl);

		} catch (Exception $e){
			$this->responseArray['error'] = $e->getMessage();
		}

		return $this->responseArray;

	}

	/**
	 * @param string $folder
	 * @return array
	 */
	public function ListFiles(string $folder = "") : array {

		$this->responseArray['error'] = "";

		$header = [
			'Authorization: Bearer '.$this->token,
			'Content-Type: application/json',
		];

		$postFields = json_encode(
			array(
				"path"=> "/".$this->rootFolder."/".$folder,
				"recursive" => false,
			    "include_media_info" => false,
			    "include_deleted" => false,
			    "include_has_explicit_shared_members" => false,
			    "include_mounted_folders" => false
			)
		);

		try {

			$curl = curl_init (self::DROPBOX_URL.'/files/list_folder');

			curl_setopt ($curl, CURLOPT_HTTPHEADER,$header);
			curl_setopt ($curl, CURLOPT_POST, true);
			curl_setopt ($curl, CURLOPT_POSTFIELDS, $postFields);
			curl_setopt ($curl, CURLOPT_RETURNTRANSFER, true);

			$response = curl_exec ($curl);
			$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

			if($http_code == 200){
				$this->responseArray['result'] = $response;
			}else{
				$this->responseArray['error'] = 1;
				$this->responseArray['error']['message'] = json_decode ($response,true);
			}

			curl_close ($curl);

		} catch (Exception $e){
			$this->responseArray['error'] = $e->getMessage();
		}

		return $this->responseArray;

	}

}
