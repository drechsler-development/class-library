<?php

namespace DD\Helper\CSV;

use DD\Exceptions\ValidationException;

abstract class AbstractCsvFile {
	const DEFAULT_DELIMITER  = ',';
	const DEFAULT_ENCLOSURE  = '"';
	const DEFAULT_LINE_BREAK = "\r\n";
	const LINUX_LINE_BREAK   = "\n";
	const MAC_LINE_BREAK     = "\r";

	/**
	 * @var string
	 */
	protected string $fileName;
	/**
	 * @var resource
	 */
	protected $filePointer;

	/**
	 * @var string
	 */
	private string $delimiter;

	/**
	 * @var string
	 */
	private string $enclosure;

	/**
	 * @return string
	 */
	public function getDelimiter (): string {
		return $this->delimiter;
	}

	/**
	 * @param string $delimiter
	 *
	 * @throws ValidationException
	 */
	protected function setDelimiter (string $delimiter): void {
		$this->validateDelimiter ($delimiter);
		$this->delimiter = $delimiter;
	}

	/**
	 * @param string $delimiter
	 *
	 * @throws ValidationException
	 */
	protected function validateDelimiter (string $delimiter): void {
		if (strlen ($delimiter) > 1) {
			throw new ValidationException(
				"Delimiter must be a single character. " . json_encode ($delimiter) . " received",
				CSVException::INVALID_PARAM
			);
		}

		if (strlen ($delimiter) == 0) {
			throw new ValidationException(
				"Delimiter cannot be empty.",
				CSVException::INVALID_PARAM
			);
		}
	}

	/**
	 * @return string
	 */
	public function getEnclosure (): string {
		return $this->enclosure;
	}

	/**
	 * @param string $enclosure
	 *
	 * @return $this
	 * @throws ValidationException
	 */
	protected function setEnclosure (string $enclosure): AbstractCsvFile {
		$this->validateEnclosure ($enclosure);
		$this->enclosure = $enclosure;
		return $this;
	}

	/**
	 * @param string $enclosure
	 *
	 * @throws ValidationException
	 */
	protected function validateEnclosure (string $enclosure): void {
		if (strlen ($enclosure) > 1) {
			throw new ValidationException(
				"Enclosure must be a single character. " . json_encode ($enclosure) . " received",
				CSVException::INVALID_PARAM
			);
		}
	}

	public function __destruct () {
		$this->closeFile ();
	}

	protected function closeFile (): void {
		if ($this->fileName && is_resource ($this->filePointer)) {
			fclose ($this->filePointer);
		}
	}

	/**
	 * @param string $file
	 */
	protected function setFile (string $file): void {
		$this->openCsvFile ($file);
		$this->fileName = $file;
	}

	abstract protected function openCsvFile ($fileName);

	/**
	 * @return resource
	 */
	protected function getFilePointer () {
		return $this->filePointer;
	}
}
