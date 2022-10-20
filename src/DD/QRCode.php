<?php

namespace DD;

class QRCode
{

	/**
	 * URL to the GOOGLE Chart API
	 */
	const API_CHART_URL = "https://chart.apis.google.com/chart";

	/**
	 * Code data
	 *
	 * @var string $data
	 */
	private string $data;

	/**
	 * Bookmark code
	 *
	 * @param string $title
	 * @param string $url
	 */
	public function SetBookmark (string $title, string $url) {

		$this->data = "MEBKM:TITLE:$title;URL:$url;;";
	}

	/**
	 * MECARD code
	 *
	 * @param string $name
	 * @param string $address
	 * @param string $phone
	 * @param string $email
	 */
	public function SetContact (string $name, string $address, string $phone, string $email) {

		$this->data = "MECARD:N:$name;ADR:$address;TEL:$phone;EMAIL:$email;;";
	}

	/**
	 * Create code with GIF, JPG, etc.
	 *
	 * @param string $type
	 * @param int|null $size
	 * @param string $content
	 */
	public function SetContent (string $type, int $size, string $content) {

		$this->data = "CNTS:TYPE:$type;LNG:$size;BODY:$content;;";
	}

	/**
	 * Generate QR code image
	 *
	 * @param int $size
	 * @param string|null $filename if filename is not ending with PNG it will be automatically added as suffix
	 * @return bool
	 */
	public function Draw (int $size = 150, string $filename = null): bool {

		$ch = curl_init ();
		curl_setopt ($ch, CURLOPT_URL, self::API_CHART_URL);
		curl_setopt ($ch, CURLOPT_POST, true);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, "chs={$size}x$size&cht=qr&chl=".urlencode ($this->data));
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($ch, CURLOPT_HEADER, false);
		curl_setopt ($ch, CURLOPT_TIMEOUT, 30);
		$img = curl_exec ($ch);
		curl_close ($ch);

		if ($img) {
			if ($filename) {
				if (!preg_match ("#\.png$#i", $filename)) {
					$filename .= ".png";
				}

				return file_put_contents ($filename, $img);
			} else {
				header ("Content-type: image/png");
				print $img;

				return true;
			}
		}

		return false;
	}

	/**
	 * Email address code
	 *
	 * @param string $email
	 * @param string $subject
	 * @param string $message
	 */
	public function SetEmail (string $email, string $subject, string $message) {

		$this->data = "MATMSG:TO:$email;SUB:$subject;BODY:$message;;";
	}

	/**
	 * Geo location code
	 *
	 * @param string $lat
	 * @param string $lon
	 * @param int|null $height
	 */
	public function SetGeo (string $lat, string $lon, int $height) {

		$this->data = "GEO:$lat,$lon,$height";
	}

	/**
	 * Telephone number code
	 *
	 * @param string $phone
	 */
	public function SetPhone (string $phone) {

		$this->data = "TEL:$phone";
	}

	/**
	 * SMS code
	 *
	 * @param string $phone
	 * @param string $text
	 */
	public function SetSMS (string $phone, string $text) {

		$this->data = "SMSTO:$phone:$text";
	}

	/**
	 * Text code
	 *
	 * @param string $text
	 */
	public function SetText (string $text) {

		$this->data = $text;
	}

	/**
	 * URL code
	 *
	 * @param string $url
	 */
	public function SetUrl (string $url) {

		$this->data = preg_match ("#^https?://#", $url) ? $url : "https://$url";
	}

	/**
	 * Wifi code
	 *
	 * @param string $type
	 * @param string $ssid
	 * @param string $password
	 */
	public function SetWifi (string $type, string $ssid, string $password) {

		$this->data = "WIFI:S:$ssid;T:$type;P:$password;;";
	}
}

