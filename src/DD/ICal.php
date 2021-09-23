<?php

namespace DD;

use DateTime;
use Exception;

class ICal {

	private string $content;
	private string $eol = "\r\n";

	public function __construct() {

		$this->content =
			'BEGIN:VCALENDAR'.$this->eol.
			'PRODID:-//dd//rd//EN'.$this->eol.
			'VERSION:2.0'.$this->eol.
			'CALSCALE:GREGORIAN'.$this->eol;
	}

	/**
	 * @param DateTime $dateFrom
	 * @param DateTime $dateTo
	 * @param string $summary
	 * @param string $location
	 * @param string $description
	 * @param string $url
	 */
	public function AddEvent(DateTime $dateFrom, DateTime $dateTo, string $summary = "", string $location = "", string $description = "", string $url = "") {

		$currentTime = date("Ymd")."T".date("His");

		$dateFromFormatted = $dateFrom->format ("Ymd\THis");
		$dateToFormatted = $dateTo->format ("Ymd\THis");

		$this->content =
			'BEGIN:VEVENT'.$this->eol.
			'UID:'.md5($currentTime).$this->eol.
			'DTSTAMP:'.$currentTime.$this->eol.
			'SUMMARY:'.$summary.$this->eol.
			'DESCRIPTION:'.$description.$this->eol.
			'DTSTART:'.$dateFromFormatted.$this->eol.
			'DTEND:'.$dateToFormatted.$this->eol.
			'LOCATION:'.$location.$this->eol.
			'URL;VALUE=URI:'.$url.$this->eol.
			'END:VEVENT'.$this->eol;

	}

	/**
	 *
	 */
	public function Finalize(){

		$this->content.='END:VCALENDAR';

	}

	/**
	 * @param string $fileName
	 * @return bool
	 */
	public function SaveFile(string $fileName) : bool {

		return file_put_contents($fileName, $this->content) !== false;

	}

}
