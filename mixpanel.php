<?php
/*
 * PHP library for Mixpanel data API -- http://www.mixpanel.com/
 * Requires PHP 5.2 with JSON
 */

class Mixpanel {
	private $api_url = 'http://mixpanel.com/api';
	private $version = '2.0';
	private $api_key;
	private $api_secret;

	public function __construct($api_key, $api_secret) {
		$this->api_key = $api_key;
		$this->api_secret = $api_secret;
	}

	public function request($methods, $params, $format = 'json') {
		// $end_point is an API end point such as events, properties, funnels, etc.
		// $method is an API method such as general, unique, average, etc.
		// $params is an associative array of parameters.
		// See http://mixpanel.com/api/docs/guides/api/

		if (!isset($params['api_key']))
			$params['api_key'] = $this->api_key;

		$params['format'] = $format;

		if (!isset($params['expire'])) {
			$current_utc_time = time() - date('Z');
			$params['expire'] = $current_utc_time + 60000; // Default 10 minutes
		}

		$param_query = '';
		foreach ($params as $param => &$value) {
			if (is_array($value))
				$value = json_encode($value);
			$param_query .= '&' . urlencode($param) . '=' . urlencode($value);
		}

		$sig = $this->signature($params);

		$uri = '/' . $this->version . '/' . join('/', $methods) . '/';
		$request_url = $uri . '?sig=' . $sig . $param_query;

		$curl_handle = curl_init();
		curl_setopt($curl_handle, CURLOPT_URL, $this->api_url . $request_url);
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($curl_handle);
		curl_close($curl_handle);

		return json_decode($data);
	}

	private function signature($params) {
		ksort($params);
		$param_string = '';
		foreach ($params as $param => $value) {
			$param_string .= $param . '=' . $value;
		}

		return md5($param_string . $this->api_secret);
	}
}

// Example usage
$api_key = 'fd7fb8c11b3deab6cd2ee8f3f6f1cd96';
$api_secret = 'aaebc5b04b6a7661259b25e0cd19dd7a';

$mp = new Mixpanel($api_key, $api_secret);

$data = $mp->request(array('engage'), array(''));
$s = "";
$i = 1;
$purchased = 0;
while (count($data->results) > 3) {
	$r = $data->results;
	echo count($r);
	foreach ($r as $k => $v) {
		if (isset($v->{'$properties'}->{'is_free'})) {
                        $s = $s . ($v->{'$properties'}->{'is_free'} ? "free" : "purchased");
                } else {
			$s = $s . 'free'; 
		}



		if (isset($v->{'$properties'}->{'$city'})) {
			$s = $s . ',' . $v->{'$properties'}->{'$country_code'} . ","
					. $v->{'$properties'}->{'$city'};
		} else {
			$s = $s . ', IL, Tel Aviv';
		}


		if (isset($v->{'$properties'}->{'$email'})) {
                        $s = $s . "," . $v->{'$properties'}->{'$email'} . ","   . $v->{'$properties'}->{'$first_name'};
                } 

		$s = $s . "\r\n";	


	}
	$data = $mp
			->request(array('engage'),
					array('session_id' => $data->session_id, 'page' => strval($i++)));
}

echo "<br/>\r\n";
echo $purchased;

file_put_contents("/tmp/customers", $s);

