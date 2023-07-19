<?php

namespace HyperBC;

class Signature {
	/**
	 * 私钥签名
	 *
	 * @param $data
	 * @param $private_key
	 */
	public static function encryption( $data, $private_key ) {
		$signature = '';

		if (is_array($data)) {
			$signString = self::getSignString($data);
		} else {
			$signString = $data;
		}
		$privKeyId = openssl_pkey_get_private($private_key);
		openssl_sign($signString, $signature, $privKeyId, OPENSSL_ALGO_MD5);
		return base64_encode($signature);
	}


	/**
	 * 使用对方的公钥验签，并且判断签名是否匹配
	 *
	 * @param $sign
	 * @param $data
	 * @param $public_key
	 * @return bool
	 */
	public static function checkSignature( $sign, $data, $public_key ) {
		$toSign = self::getSignString($data);
		$publicKeyId = openssl_pkey_get_public($public_key);
		$result = openssl_verify($toSign, base64_decode($sign), $publicKeyId, OPENSSL_ALGO_MD5);

		if (1 === $result) {
			return true;
		}
		return false;
	}

	public static function getSignString( $data ) {
		unset($data['sign']);
		ksort($data);
		reset($data);
		$pairs = array();
		foreach ($data as $k => $v) {
			if (is_array($v)) {
				$v = self::arrayToString($v);
			}
			$pairs[] = "$k=$v";
		}

		return implode('&', $pairs);
	}

	private static function arrayToString( $data ) {
		$str = '';
		foreach ($data as $list) {
			if (is_array($list)) {
				$str .= self::arrayToString($list);
			} else {
				$str .= $list;
			}
		}

		return $str;
	}
}
