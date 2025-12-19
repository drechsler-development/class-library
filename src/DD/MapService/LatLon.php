<?php

namespace DD\MapService;

class LatLon {

	const string URL = 'https://nominatim.openstreetmap.org';

	public function __construct() {
	}

	/**
	 * @param string $address1
	 * @param string $address2
	 *
	 * @return float
	 */
	public function GetDistance (string $address1, string $address2): float {

		[$lat1, $lon1] = array_values ($this->GetLatLon ($address1));
		[$lat2, $lon2] = array_values ($this->GetLatLon ($address2));

		return $this->CalculateDistance($lat1, $lon1, $lat2, $lon2);

	}

	/**
	 * @param float $lat1
	 * @param float $lon1
	 * @param float $lat2
	 * @param float $lon2
	 *
	 * @return float
	 */
	private function  CalculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float {

		$earthRadius = 6371;

		$dLat = deg2rad ($lat2 - $lat1);
		$dLon = deg2rad ($lon2 - $lon1);

		$a = sin ($dLat / 2) ** 2
			+ cos (deg2rad ($lat1)) * cos (deg2rad ($lat2))
			* sin ($dLon / 2) ** 2;

		return 2 * $earthRadius * asin (min (1, sqrt ($a)));

	}

	/**
	 * @param string $address
	 *
	 * @return array
	 */
	public function GetLatLon(string $address) : array {

		$data = [
			'q'      => $address,
			'format' => 'json',
			'limit'  => 1
		];

		$url = self::URL . '/search?' . http_build_query ($data);

		$ch = curl_init ();

		curl_setopt_array ($ch, [
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => 10,
			CURLOPT_HTTPHEADER     => [
				'User-Agent: VABS-DeliveryCheck/1.0 (support@deinedomain.de)'
			]
		]);

		$response = curl_exec ($ch);

		if ($response === false) {
			curl_close ($ch);
			return [];
		}

		$httpCode = curl_getinfo ($ch, CURLINFO_HTTP_CODE);
		curl_close ($ch);

		if ($httpCode !== 200) {
			return [];
		}

		$data = json_decode ($response, true);

		if (empty($data[0])) {
			return [];
		}

		if (empty($data[0]['lat']) || empty($data[0]['lon'])) {
			return [];
		}

	    return [
		    'lat' => (float)$data[0]['lat'],
		    'lon' => (float)$data[0]['lon']
	    ];

	}

}
