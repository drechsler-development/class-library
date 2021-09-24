<?php

namespace DD\Mailer;

class Parser {

	public string $openingTag = '{{';
	public string $closingTag = '}}';
	public array $replacements = [];
	public string $content    = '';

	public function __construct($emailValues) {
		$this->replacements = $emailValues;
	}

	public function output() {
		$html = $this->content;
		foreach ($this->replacements as $key => $value) {
			$html = str_replace($this->openingTag . $key . $this->closingTag, $value, $html);
		}
		return $html;
	}
}
