<?php
namespace HyperBC;

class HyperBC {
	const VERSION = '1.0.0';
	const USER_AGENT_ORIGIN = 'HyperPC PHP Library';

	public static $auth_token = '';
	public static $environment = 'Sandbox';
	public static $user_agent = '';
	public static $curlopt_ssl_verifypeer = false;
	public static $version = '1.0';
	public static $app_id = '';
	public static $public_key = '';
	public static $private_key = '';

	public static function config( $authentication ) {
		if (isset($authentication['app_id'])) {
			self::$app_id = $authentication['app_id'];
		}
		if (isset($authentication['public_key'])) {
			self::$public_key = $authentication['public_key'];
		}
		if (isset($authentication['private_key'])) {
			self::$private_key = $authentication['private_key'];
		}
		if (isset($authentication['environment'])) {
			self::$environment = $authentication['environment'];
		}
		if (isset($authentication['user_agent'])) {
			self::$user_agent = $authentication['user_agent'];
		}
	}

	public static function testConnection( $authentication = array() ) {
		try {
			self::request('/auth/test', 'GET', array(), $authentication);

			return true;
		} catch (\Exception $e) {
			return get_class($e) . ': ' . $e->getMessage();
		}
	}

	public static function request( $url, $method = 'POST', $params = array(), $authentication = array() ) {
		// $environment = isset($authentication['environment']) ? $authentication['environment'] : self::$environment;
		// $user_agent  = isset($authentication['user_agent']) ? $authentication['user_agent'] : (isset(self::$user_agent) ? self::$user_agent : (self::USER_AGENT_ORIGIN . ' v' . self::VERSION));
		// $curlopt_ssl_verifypeer = isset($authentication['curlopt_ssl_verifypeer']) ? $authentication['curlopt_ssl_verifypeer'] : self::$curlopt_ssl_verifypeer;

		# Check if right environment passed
		// $environments = array('live', 'sandbox');

		// if (!in_array($environment, $environments)) {
		//     $availableEnvironments = join(', ', $environments);
		//     \HyperBC\Exception::throwException(400, array('reason' => 'BadEnvironment', 'message' => "Environment does not exist. Available environments: $availableEnvironments"));
		// }
		error_log(print_r($params, true));
		$params = json_encode($params);
		if ('Sandbox' == self::$environment) {
			$url = 'http://apitest.hyperbc.top/shopapi/' . $url;
		} else {
			$url = 'http://api.hyperbc.top/shopapi/' . $url;
		}

		$headers = [
			'Content-Type: application/json; charset=utf-8',
			'Content-Length:' . strlen($params)
		];
		$curl = curl_init();
		$SSL = substr($url, 0, 8) == 'https://' ? true : false;

		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_URL, $url);
		if ($SSL) {
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		}


		$headers = [
			'Content-Type: application/json; charset=utf-8',
			'Content-Length:' . strlen($params)
		];
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		// curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

		$response = json_decode(curl_exec($curl), true);
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		error_log(print_r($response, true));
		if (array_key_exists('data', $response)) {
			return $response;
		} else {
			\HyperBC\Exception::throwException($http_status, $response);
		}
	}

	public static function sign( $obj, $return = false ) {
		if (true != $return) {
			$obj['app_id'] = self::$app_id;
			$obj['version'] = self::$version;

			// $obj["lang"] = "en";
			// $obj["sign"] = self::$version;
			$obj['time'] = strval(time());
		}

		$obj['sign'] = \HyperBC\Signature::encryption($obj, self::$private_key);

		return $obj;
	}

	public static function check_sign( $sign, $data ) {
		return \HyperBC\Signature::checkSignature($sign, $data, self::$public_key);
	}
}
