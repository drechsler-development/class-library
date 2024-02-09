<?php

namespace DD\ExchangeRates;

use DateTime;
use DD\Exceptions\ValidationException;

/**
 * Class ExchangeRate
 * Documentation at: https://exchangeratesapi.io/documentation/
 *
 * @package DD\ExchangeRates
 */
class ExchangeRate {

	const API_URL = 'http://api.exchangeratesapi.io/v1';

	const C_EUR = 'EUR';
	const C_USD = 'USD';
	const C_GBP = 'GBP';
	const C_JPY = 'JPY';
	const C_AUD = 'AUD';
	const C_CAD = 'CAD';
	const C_CHF = 'CHF';
	const C_CNY = 'CNY';
	const C_SEK = 'SEK';
	const C_NZD = 'NZD';
	const C_MXN = 'MXN';
	const C_SGD = 'SGD';
	const C_HKD = 'HKD';
	const C_NOK = 'NOK';
	const C_KRW = 'KRW';
	const TRY = 'TRY';
	const C_INR = 'INR';
	const C_RUB = 'RUB';
	const C_BRL = 'BRL';
	const C_ZAR = 'ZAR';
	const C_DKK = 'DKK';
	const C_PLN = 'PLN';

	private array $allowedCurrencies = [
		self::C_EUR,
		self::C_USD,
		self::C_GBP,
		self::C_JPY,
		self::C_AUD,
		self::C_CAD,
		self::C_CHF,
		self::C_CNY,
		self::C_SEK,
		self::C_NZD,
		self::C_MXN,
		self::C_SGD,
		self::C_HKD,
		self::C_NOK,
		self::C_KRW,
		self::TRY,
		self::C_INR,
		self::C_RUB,
		self::C_BRL,
		self::C_ZAR,
		self::C_DKK,
		self::C_PLN,
	];

	private string $apiKey;

	public function __construct (string $apiKey) {
		$this->apiKey = $apiKey;
	}

	/**
	 * @param string        $baseCurrency
	 * @param string        $targetCurrency
	 * @param DateTime|null $date
	 *
	 * @return array
	 * @throws ValidationException
	 */
	public function GetExchangeRate (string $baseCurrency, string $targetCurrency, DateTime $date = null): array {

		$this->ValidateCurrency ($baseCurrency);
		$this->ValidateCurrency ($targetCurrency);

		$latestOrSpecialDate = '/latest';

		if ($date !== null) {
			$latestOrSpecialDate = '/' . $date->format ('Y-m-d');
		}

		$url = self::API_URL . '/' . $latestOrSpecialDate . '?access_key=' . $this->apiKey . '&base=' . $baseCurrency . '&symbols=' . $targetCurrency;

		$ch = curl_init ();
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec ($ch);
		curl_close ($ch);

		return json_decode ($output, true);

	}

	/**
	 * @param string $currency
	 *
	 * @return void
	 * @throws ValidationException
	 */
	private function ValidateCurrency (string $currency): void {
		if (!in_array ($currency, $this->allowedCurrencies)) {
			throw new ValidationException('Invalid currency: ' . $currency);
		}
	}

}
