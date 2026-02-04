<?php

namespace DD\TSE;

use DD\Exceptions\ValidationException;
use Exception;
use Random\RandomException;

class TSEService {

	const string BASE_URL = "https://kassensichv-middleware.fiskaly.com/api/v2";

	private string $tseClientId;
	private string $tseTssId;
	private string $tseApiKey;
	private string $tseApiSecret;
	private string $tseAccessToken;

	/**
	 * @throws Exception
	 */
	public function __construct (string $tseClientId, string $tseId, string $tseApiKey, string $tseApiSecret) {

		$this->tseClientId    = $tseClientId;
		$this->tseTssId       = $tseId;
		$this->tseApiKey      = $tseApiKey;
		$this->tseApiSecret   = $tseApiSecret;
		$this->tseAccessToken = $this->GetToken ();

	}

	/**
	 * @param array $schema
	 * @param int   $revisionNumber
	 *
	 * @return array
	 * @throws Exception
	 */
	public function StartTransaction (array $schema, int $revisionNumber): array {

		$payload = [
			"state"        => "ACTIVE",
			"client_id"    => $this->tseClientId,
			"process_type" => "RECEIPT",
			"schema"       => $schema,
		];

		$guid = $this->GetGuid ();

		return $this->CallFiskaliAPI ("PUT", "/tss/$this->tseTssId/tx/$guid?tx_revision=" . $revisionNumber, $payload);
	}

	/**
	 * @param array  $schema
	 * @param string $tseTransactionId
	 * @param int    $revisionNumber
	 *
	 * @return array
	 * @throws Exception
	 */
	public function FinishTransaction (array $schema, string $tseTransactionId, int $revisionNumber): array {

		$payload = [
			"state"        => "FINISHED",
			"client_id"    => $this->tseClientId,
			"schema"       => $schema,
			"process_type" => "RECEIPT",
		];

		return $this->CallFiskaliAPI ("PUT", "/tss/$this->tseTssId/tx/$tseTransactionId?tx_revision=" . $revisionNumber, $payload);
	}

	/**
	 * @param array $schema
	 * @param int   $revisionNumber
	 *
	 * @return array
	 * @throws ValidationException
	 * @throws Exception
	 */
	public function StartAndFinishTransaction (array $schema, int $revisionNumber): array {

		$start = $this->StartTransaction ($schema, $revisionNumber);

		if (!empty($start['error']) && !empty($start['message'])) {
			throw new ValidationException("Could not start TSE transaction. Error: " . $start['error'] . " - " . $start['message']);
		}

		if (empty($start['_id'])) {
			throw new ValidationException("Could not start TSE transaction. Error: No transaction ID (_id) received_");

		}

		$latestRevision = $start['latest_revision'] ?? 0;

		if (empty($latestRevision)) {
			throw new ValidationException("Could not start TSE transaction. Error: latest_revision not set");
		}

		$tseTransactionId = $start['_id'];
		$revisionNumber   = $latestRevision + 1;

		return $this->FinishTransaction ($schema, $tseTransactionId, $revisionNumber);
	}

	/**
	 * @param string $adminPin
	 *
	 * @return array
	 * @throws Exception
	 */
	public function AuthAdmin (string $adminPin): array {

		$payload = [
			"admin_pin" => $adminPin,
		];

		return $this->CallFiskaliAPI ("POST", "/tss/$this->tseTssId/admin/auth", $payload);

	}

	/**
	 * @param string $adminPuk
	 * @param string $newAdminPin
	 *
	 * @return array
	 * @throws Exception
	 */
	public function ChangeAdminPin (string $adminPuk, string $newAdminPin): array {

		$payload = [
			"admin_puk"     => $adminPuk,
			"new_admin_pin" => $newAdminPin,
		];

		return $this->CallFiskaliAPI ("PATCH", "/tss/$this->tseTssId/admin", $payload);

	}

	/**
	 * @param string $adminPin
	 * @param string $state
	 *
	 * @return array
	 * @throws Exception
	 */
	public function ChangeTSSState (string $adminPin, string $state = 'UNINITIALIZED'): array {

		$payload = [
			'state' => $state,
		];

		if ($state === 'INITIALIZED') {
			$this->AuthAdmin ($adminPin);
		}

		return $this->CallFiskaliAPI ("PATCH", "/tss/$this->tseTssId", $payload);

	}

	/**
	 * @param array $lines
	 *
	 * @return array[]
	 */
	public static function BuildSchema (array $lines): array {

		$vatGroups     = [];
		$paymentGroups = [];

		foreach ($lines as $line) {
			$gross   = (float)($line['price'] ?? 0);
			$vatRate = (int)($line['taxpercent'] ?? 0);
			$payment = $line['payment'] ?? 'NON_CASH';

			// --- VAT mapping zu FISKALY-Keys ---
			$vatKey = match (floatval ($vatRate)) {
				19.0 => 'NORMAL',
				7.0 => 'REDUCED_1',
				10.7 => 'SPECIAL_RATE_1',
				5.5 => 'SPECIAL_RATE_2',
				0.0 => '0',
				default => 'NULL',
			};

			if (!isset($vatGroups[$vatKey])) {
				$vatGroups[$vatKey] = 0.0;
			}
			$vatGroups[$vatKey] += $gross;

			// --- Payment grouping ---
			if (!isset($paymentGroups[$payment])) {
				$paymentGroups[$payment] = 0.0;
			}
			$paymentGroups[$payment] += $gross;
		}

		// amounts_per_vat_rate
		$amountsPerVatRate = [];
		foreach ($vatGroups as $vatKey => $sum) {
			$amountsPerVatRate[] = [
				'vat_rate' => $vatKey,
				'amount'   => number_format ($sum, 2, '.', ''),
			];
		}

		// amounts_per_payment_type
		$amountsPerPaymentType = [];
		foreach ($paymentGroups as $paymentType => $sum) {
			$amountsPerPaymentType[] = [
				'payment_type' => $paymentType,
				'amount'       => number_format ($sum, 2, '.', ''),
			];
		}

		return [
			'standard_v1' => [
				'receipt' => [
					'receipt_type'             => 'RECEIPT',
					'amounts_per_vat_rate'     => $amountsPerVatRate,
					'amounts_per_payment_type' => $amountsPerPaymentType,
				],
			],
		];
	}

	/**
	 * @param string     $method
	 * @param string     $endpoint
	 * @param array|null $data
	 * @param bool       $isAuth
	 *
	 * @return array
	 * @throws Exception
	 */
	private function CallFiskaliAPI (string $method, string $endpoint, array $data = null, bool $isAuth = false): array {

		$headers = $isAuth === false ? [
			"Content-Type: application/json",
			"Authorization: Bearer $this->tseAccessToken"
		] : [
			"Content-Type: application/json",
		];

		$curl = curl_init (self::BASE_URL . $endpoint);
		curl_setopt ($curl, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($curl, CURLOPT_HTTPHEADER, $headers);

		if ($data) {
			curl_setopt ($curl, CURLOPT_POSTFIELDS, json_encode ($data));
		}

		$response = curl_exec ($curl);
		$error    = curl_error ($curl);
		curl_close ($curl);

		if ($error) {
			throw new Exception("Fiskaly Auth Error: " . $error);
		}

		return json_decode ($response, true);
	}

	/**
	 * @throws Exception
	 */
	private function GetToken (): string {

		$payload = [
			"api_key"    => $this->tseApiKey,
			"api_secret" => $this->tseApiSecret
		];

		$response = $this->CallFiskaliAPI ("POST", "/auth", $payload, true);


		$this->tseAccessToken = $response['access_token'] ?? '';
		if (empty($this->tseAccessToken)) {
			throw new Exception("Fiskaly Auth Error: No access token received1.");
		}

		return $this->tseAccessToken;

	}

	/**
	 * @return string
	 * @throws RandomException
	 */
	private function GetGuid (): string {

		$data = random_bytes (16);

		// Set version to 0100 (v4)
		$data[6] = chr ((ord ($data[6]) & 0x0f) | 0x40);

		// Set variant to 10xx (RFC 4122)
		$data[8] = chr ((ord ($data[8]) & 0x3f) | 0x80);

		$hex = bin2hex ($data);

		// 8-4-4-4-12
		return sprintf (
			'%s-%s-%s-%s-%s',
			substr ($hex, 0, 8),
			substr ($hex, 8, 4),
			substr ($hex, 12, 4),
			substr ($hex, 16, 4),
			substr ($hex, 20, 12)
		);

	}

}
