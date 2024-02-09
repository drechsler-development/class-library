<?php

namespace DD\Helper\CSV;

use DD\Exceptions\ValidationException;

class CsvWriter extends AbstractCsvFile {
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
	 * @param string $lineBreak
	 *
	 * @throws CSVException
	 * @throws ValidationException
	 */
	public function __construct (
		string $file,
		string $delimiter = self::DEFAULT_DELIMITER,
		string $enclosure = self::DEFAULT_ENCLOSURE,
		string $lineBreak = self::DEFAULT_LINE_BREAK
	) {
		$this->setDelimiter ($delimiter);
		$this->setEnclosure ($enclosure);
		$this->setLineBreak ($lineBreak);
		$this->setFile ($file);
	}

	/**
	 * @param string $lineBreak
	 *
	 * @throws CSVException
	 */
	private function setLineBreak (string $lineBreak): void {
		$this->validateLineBreak ($lineBreak);
		$this->lineBreak = $lineBreak;
	}

	/**
	 * @param string $lineBreak
	 *
	 * @throws CSVException
	 */
	private function validateLineBreak (string $lineBreak): void {
		$allowedLineBreaks = [
			"\r\n", // win
			"\r", // mac
			"\n", // unix
		];
		if (!in_array ($lineBreak, $allowedLineBreaks)) {
			throw new CSVException(
				"Invalid line break: " . json_encode ($lineBreak) .
				" allowed line breaks: " . json_encode ($allowedLineBreaks),
				CSVException::INVALID_PARAM
			);
		}
	}

	/**
	 * @param string $fileName
	 *
	 * @throws CSVException
	 */
	protected function openCsvFile ($fileName): void {
		$this->filePointer = @fopen ($fileName, 'w');
		if (!$this->filePointer) {
			throw new CSVException(
				"Cannot open file $fileName " . error_get_last ()['message'],
				CSVException::FILE_NOT_EXISTS
			);
		}
	}

	/**
	 * @param array $row
	 *
	 * @throws CSVException
	 */
	public function writeRow (array $row): void {
		$str = $this->rowToStr ($row);
		$ret = @fwrite ($this->getFilePointer (), $str);

		/* According to http://php.net/fwrite the fwrite() function
		 should return false on error. However, not writing the full
		 string (which may occur e.g. when disk is full) is not considered
		 as an error. Therefore, both conditions are necessary. */
		if (($ret === false) || (($ret === 0) && (strlen ($str) > 0))) {
			throw new CSVException(
				"Cannot write to CSV file " . $this->fileName .
				($ret === false && error_get_last () ? 'ErrorHandler: ' . error_get_last ()['message'] : '') .
				' Return: ' . json_encode ($ret) .
				' To write: ' . strlen ($str) . ' Written: ' . $ret,
				CSVException::WRITE_ERROR
			);
		}
	}

	/**
	 * @param array $row
	 *
	 * @return string
	 * @throws CSVException
	 */
	public function rowToStr (array $row): string {
		$return = [];
		foreach ($row as $column) {
			if (!(
				is_scalar ($column)
				|| is_null ($column)
				|| (
					is_object ($column)
					&& method_exists ($column, '__toString')
				)
			)) {
				throw new CSVException(
					"Cannot write data into column: " . var_export ($column, true),
					CSVException::WRITE_ERROR
				);
			}

			$return[] = $this->getEnclosure () .
				str_replace ($this->getEnclosure (), str_repeat ($this->getEnclosure (), 2), $column) .
				$this->getEnclosure ();
		}
		return implode ($this->getDelimiter (), $return) . $this->lineBreak;
	}
}
