<?php
namespace HyperBC\Merchant;

use HyperBC\HyperBC;
use HyperBC\Merchant;
use HyperBC\OrderIsNotValid;
use HyperBC\OrderNotFound;

class Order extends Merchant {
	private $order;

	public function __construct( $order ) {
		$this->order = $order;
	}

	public function toHash() {
		return $this->order;
	}

	public function __get( $name ) {
		return $this->order[$name];
	}

	public static function find( $params, $options = array(), $authentication = array() ) {
		try {
			return self::findOrFail($params, $options, $authentication);
		} catch (OrderNotFound $e) {
			return false;
		}
	}

	public static function findOrFail( $params, $options = array(), $authentication = array() ) {
		$order = HyperBC::request('/h5_order/detail', 'POST', \HyperBC\HyperBC::sign($params), $authentication);

		if (\HyperBC\HyperBC::check_sign($order['sign'], $order)) {
			return new self($order['data']);
		}
		\HyperBC\Exception::throwException(422, array('error' => 'SignatureError'));
	}

	public static function create( $params, $options = array(), $authentication = array() ) {
		try {
			return self::createOrFail($params, $options, $authentication);
		} catch (OrderIsNotValid $e) {
			return false;
		}
	}

	public static function createOrFail( $params, $options = array(), $authentication = array() ) {
		$order = HyperBC::request('h5_order/create', 'POST', \HyperBC\HyperBC::sign($params), $authentication);

		if (200 != $order['status']) {
			\HyperBC\Exception::throwException(422, array('error' => 'OrderIDDuplicate'));
			return;
		}

		if (\HyperBC\HyperBC::check_sign($order['sign'], $order)) {
			return new self($order['data']);
		}
		\HyperBC\Exception::throwException(422, array('error' => 'SignatureError'));
	}
}
