<?php

namespace DD\Helper;

use Exception;
use DD\Exceptions\ValidationException;

class File {

	/**
	 * Download a large distant file to a local destination.
	 *
	 * This method is very memory efficient :-)
	 * The file can be huge, PHP doesn't load it in memory.
	 *
	 * /!\ Warning, the return value is always true, you must use === to test the response type too.
	 *
	 * @param string $url
	 *    The file to download
	 * @param string $dest
	 *    The local file path or ressource (file handler)
	 * @return boolean true or the error message
	 * @author dalexandre
	 */
	public static function DownloadFileFromURL (string $url, string $dest) : bool {

		$options = [
			CURLOPT_FILE => is_resource ($dest) ? $dest : fopen ($dest, 'w'),
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_URL => $url,
			CURLOPT_FAILONERROR => true, // HTTP code > 400 will throw curl error
		];

		$ch = curl_init ();
		curl_setopt_array ($ch, $options);
		$return = curl_exec ($ch);

		if ($return === false) {
			return curl_error($ch);
		}

		return true;

	}

	// Usage: Move($_FILE['file'],'/destination/path/','my_new_name.jpg', true)

	/**
	 * @param array $source must be $_FILES['xxx']
	 * @param string $destination
	 * @param string $filename
	 * @param bool $createDestinationFolder
	 * @return string
	 * @throws ValidationException
	 */
	public static function MoveUploadedFile(array $source, string $destination, string $filename, bool $createDestinationFolder = false): string {

		$message = "";

		if(!isset($source['tmp_name'])){
			throw new ValidationException("Source doesn't exists");
		}

		if(!file_exists ($source['tmp_name']) || is_dir ($source['tmp_name'])){
			throw new ValidationException("Source doesn't exists or is a directory");
		}

		if(!is_dir($destination)){

			if(!$createDestinationFolder){
				mkdir ($destination);
			} else {
				throw new ValidationException("Target dir doesn't exists");
			}

		}

		if(strlen($filename) == 0) {
			$filename = $source['name'];
		}

		$filename = preg_replace("/[^0-9\-_.a-z]/","", $filename);

		if(!move_uploaded_file ($source['tmp_name'], $destination."/".$filename)){
			$message = "Upload failed";
		}

		return $message;

	}

	/**
	 * @param string $dir
	 * @param bool $deleteNonEmptyFolder
	 * @return bool
	 * @throws ValidationException
	 */
	public static function DeleteFolder(string $dir, bool $deleteNonEmptyFolder): bool {


		if(!strstr($dir,"userfiles")) {
			throw new ValidationException("Der Ordner, welcher gelöscht werden soll, befindet sich nicht im Unterverzweichnis 'userfiles'");
		}

		if($deleteNonEmptyFolder){

			$files = array_diff(scandir($dir), array('.','..'));
			foreach ($files as $file) {
				is_dir("$dir/$file") ? self::DeleteFolder("$dir/$file", true) : unlink("$dir/$file");
			}

		}

		return rmdir($dir);

	}

}
