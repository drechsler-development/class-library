<?php

namespace DD\Helper\CSV;

use DD\Exceptions\ValidationException;
use Iterator;
use ReturnTypeWillChange;

class CsvReader extends AbstractCsvFile implements Iterator {
	const DEFAULT_ESCAPED_BY = "";

	/**
	 * @var string
	 */
	private string $escapedBy;

	/**
	 * @var int
	 */
	private int $skipLines;

	/**
	 * @var int
	 */
	private int $rowCounter = 0;

	/**
	 * @var array|null|false
	 */
	private array|null|false $currentRow;

	/**
	 * @var array
	 */
	private array $header;

	/**
	 * @var string
	 */
	private string $lineBreak;

	/**
	 * CsvFile constructor.
	 *
	 * @param string $file
	 * @param string $delimiter
	 * @param string $enclosure
	 * @param string $escapedBy
	 * @param int    $skipLines
	 *
	 * @throws ValidationException
	 */
	public function __construct (
		string $file,
		string $delimiter = self::DEFAULT_DELIMITER,
		string $enclosure = self::DEFAULT_ENCLOSURE,
		string $escapedBy = self::DEFAULT_ESCAPED_BY,
		int    $skipLines = 0
	) {
		$this->escapedBy = $escapedBy;
		$this->setDelimiter ($delimiter);
		$this->setEnclosure ($enclosure);
		$this->setSkipLines ($skipLines);
		$this->setFile ($file);
		$this->lineBreak = $this->detectLineBreak ();
		rewind ($this->filePointer);
		$this->header = $this->readLine ();
		$this->rewind ();
	}

	/**
	 * @param integer $skipLines
	 *
	 * @return CsvReader
	 * @throws ValidationException
	 */
	protected function setSkipLines (int $skipLines): CsvReader {
		$this->validateSkipLines ($skipLines);
		$this->skipLines = $skipLines;
		return $this;
	}

	/**
	 * @param integer $skipLines
	 *
	 * @throws ValidationException
	 */
	protected function validateSkipLines (int $skipLines): void {
		if ($skipLines < 0) {
			throw new ValidationException(
				"Number of lines to skip must be a positive integer. \"$skipLines\" received.",
				CSVException::INVALID_PARAM
			);
		}
	}

	/**
	 * @param $fileName
	 *
	 * @throws CSVException
	 */
	protected function openCsvFile ($fileName): void {
		if (!is_file ($fileName)) {
			throw new CSVException(
				"Cannot open file " . $fileName,
				CSVException::FILE_NOT_EXISTS
			);
		}
		$this->filePointer = @fopen ($fileName, "r");
		if (!$this->filePointer) {
			throw new CSVException(
				"Cannot open file $fileName " . error_get_last ()['message'],
				CSVException::FILE_NOT_EXISTS
			);
		}
	}

	/**
	 * @return string
	 */
	protected function detectLineBreak (): string {
		rewind ($this->getFilePointer ());
		$sample = fread ($this->getFilePointer (), 10000);

		$possibleLineBreaks = [
			"\r\n", // win
			"\r", // mac
			"\n", // unix
		];

		$lineBreaksPositions = [];
		foreach ($possibleLineBreaks as $lineBreak) {
			$position = strpos ($sample, $lineBreak);
			if ($position === false) {
				continue;
			}
			$lineBreaksPositions[$lineBreak] = $position;
		}


		asort ($lineBreaksPositions);

		return empty($lineBreaksPositions) ? "\n" : key ($lineBreaksPositions);
	}

	/**
	 * @return array|false|null
	 * @throws ValidationException
	 */
	protected function readLine (): bool|array|null {
		$this->validateLineBreak ();

		// allow empty enclosure hack
		$enclosure = !$this->getEnclosure () ? chr (0) : $this->getEnclosure ();
		$escapedBy = !$this->escapedBy ? chr (0) : $this->escapedBy;
		return fgetcsv ($this->getFilePointer (), null, $this->getDelimiter (), $enclosure, $escapedBy);
	}

	/**
	 * @return string
	 * @throws ValidationException
	 */
	protected function validateLineBreak (): string {
		$lineBreak = $this->getLineBreak ();
		if (in_array ($lineBreak, ["\r\n", "\n"])) {
			return $lineBreak;
		}

		throw new ValidationException(
			"Invalid line break. Please use unix \\n or win \\r\\n line breaks.",
			CSVException::INVALID_PARAM
		);
	}

	/**
	 * @return string
	 */
	public function getLineBreak (): string {
		return $this->lineBreak;
	}

	/**
	 * @inheritdoc
	 * @throws ValidationException
	 */
	#[ReturnTypeWillChange]
	public function rewind (): void {

		rewind ($this->getFilePointer ());
		for ($i = 0; $i < $this->skipLines; $i++) {
			$this->readLine ();
		}
		$this->currentRow = $this->readLine ();
		$this->rowCounter = 0;

	}

	/**
	 * @return string
	 */
	public function getEscapedBy (): string {
		return $this->escapedBy;
	}

	/**
	 * @return int
	 */
	public function getColumnsCount (): int {
		return count ($this->getHeader ());
	}

	/**
	 * @return bool|array|null
	 */
	public function getHeader (): bool|array|null {
		if ($this->header) {
			return $this->header;
		}
		return [];
	}

	/**
	 * @return string
	 */
	public function getLineBreakAsText (): string {
		return trim (json_encode ($this->getLineBreak ()), '"');
	}

	/**
	 * @inheritdoc
	 */
	#[ReturnTypeWillChange]
	public function current () {
		return $this->currentRow;
	}

	/**
	 * @inheritdoc
	 * @throws ValidationException
	 */
	#[ReturnTypeWillChange]
	public function next (): void {
		$this->currentRow = $this->readLine ();
		$this->rowCounter++;
	}

	/**
	 * @inheritdoc
	 */
	#[ReturnTypeWillChange]
	public function key () {
		return $this->rowCounter;
	}

	/**
	 * @inheritdoc
	 */
	public function valid (): bool {
		return $this->currentRow !== false;
	}
}
