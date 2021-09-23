<?php

namespace DD\Helper;

use DD\Exceptions\ValidationException;
use Exception;

class Image
{

	/**
	 * @param string $file
	 * @param int $height
	 * @param int $width
	 * @param bool $sourceFileContainsDocRoot
	 * @param bool $recreate
	 * @param bool $outputWithDocRoot
	 * @param bool $log
	 * @return array|string|string[]
	 * @throws Exception
	 */
	public static function CropImage (string $file, int $height, int $width, bool $sourceFileContainsDocRoot, bool $recreate, bool $outputWithDocRoot, bool $log) {

		ini_set ('gd.jpeg_ignore_warning', 1);

		$outputFilePath = "";


		$file = !$sourceFileContainsDocRoot ? $_SERVER['DOCUMENT_ROOT'].$file : $file;

		if(file_exists($file) && !is_dir($file)){

			//Typ bestimmmen
			$type = @getimagesize($file);
			if($type !== false){

				$dir = dirname($file);
				$filename = basename($file);
				$tn_filename = $dir.'/tn_'.$width."_".$height."_".$filename;

				switch(strtoupper($type['mime'])){

					case "IMAGE/JPEG":
						$image = @imagecreatefromjpeg($file);
						if (!$image) {
							$image = @imagecreatefromstring (file_get_contents ($file));
						}
						break;
					case "IMAGE/JPG":
						$image = @imagecreatefromjpeg($file);
						break;
					case "IMAGE/PNG":
						$image = @imagecreatefrompng($file);
						break;
					case "IMAGE/GIF":
						$image = @imagecreatefromgif($file);
						break;
					default:
						throw new ValidationException("Bildtyp nicht erkannt");

				}

				if(!file_exists($tn_filename) || $recreate){

					$thumb_width = $width; //355;
					$thumb_height = $height; //178;

					$width = @imagesx($image);
					$height = @imagesy($image);

					$original_aspect = $width / $height;
					$thumb_aspect = $thumb_width / $thumb_height;

					if ( $original_aspect >= $thumb_aspect ) {
						// If image is wider than thumbnail (in aspect ratio sense)
						$new_height = $thumb_height;
						$new_width = $width / ($height / $thumb_height);
					} else {
						// If the thumbnail is wider than the image
						$new_width = $thumb_width;
						$new_height = $height / ($width / $thumb_width);
					}

					$thumb = @imagecreatetruecolor( $thumb_width, $thumb_height );
					if(strtoupper($type['mime']) == "IMAGE/PNG"){
						@imagealphablending($thumb, false);
						@imagesavealpha($thumb, true);
					}
					// Resize and crop
					@imagecopyresampled($thumb, $image, 0 - ($new_width - $thumb_width) / 2, // Center the image horizontally
						0 - ($new_height - $thumb_height) / 2, // Center the image vertically
						0, 0, $new_width, $new_height, $width, $height);

					switch(strtoupper($type['mime'])){

						case "IMAGE/JPG":
						case "IMAGE/JPEG":
							@imagejpeg($thumb, $tn_filename, 100);
							break;
						case "IMAGE/PNG":
							@imagepng($thumb, $tn_filename, 0);
							break;
						case "IMAGE/GIF":
							@imagegif ($thumb, $tn_filename);
							break;
						default:
							break;

					}

					@imagedestroy($thumb);

				}
				$outputFilePath = !$outputWithDocRoot ? str_replace($_SERVER['DOCUMENT_ROOT'],'',$tn_filename) : $tn_filename;

			}

		}else{

			$bildpfad = "/assets/kein_bild.jpg";
			$outputFilePath = !$outputWithDocRoot ? str_replace($_SERVER['DOCUMENT_ROOT'],'',$bildpfad) : $bildpfad;

		}

		return str_replace("//","/",$outputFilePath);

	}

	/**
	 * @param string $firstname
	 * @param string $lastname
	 * @param int $size
	 * @param float $fontSize
	 * @param string $bgcolor
	 * @param string $color
	 * @param bool $rounded
	 * @param bool $uppercase
	 * @param int $length
	 * @return string
	 */
	public static function CreateAvatar (string $firstname, string $lastname, int $size = 32, float $fontSize = 0.3, string $bgcolor = '0D8ABC', string $color = 'FFF', bool $rounded = false, bool $uppercase = true, int $length = 2): string {

		$baseUrl = "https://ui-avatars.com/api/";
		$url = $baseUrl;

		$name = strlen ($firstname) > 0 ? "?name=".trim ($firstname) : "?name=-";
		$name .= strlen ($lastname) > 0 ? " ".trim ($lastname) : "";

		$url .= $name."&size=".$size."&background=".$bgcolor."&color=".$color."&length=".$length."&font-size=".$fontSize."&rounded=".(int)$rounded."&uppercase=".(int)$uppercase;

		return $url;

	}

}
