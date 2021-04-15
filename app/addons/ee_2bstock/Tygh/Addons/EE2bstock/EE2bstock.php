<?php

namespace Tygh\Addons\EE2bstock;

use Tygh\Registry;
use Tygh\Api\Response;

class EE2bstock {
    
	public $module_oprions = [];
	private $error;
	
	public function __construct () {
		$this->module_oprions = Registry::get('addons.ee_2bstock');
		$this->error = false;
    }

	public function features($data, $method) {		
		switch ($method) {
			case 'GET':
				$response = $this->get_features($data);
				break;
			case 'PUT':
				$response = $this->update_features($data);
				break;
			case 'POST':
				$response = $this->create_features($data);
				break;
			case 'DELETE':
				$response = $this->dell_features($data);
				break;
		}
		$status_code = $this->get_status_code($method);
		$resp = ['status_code' => $status_code, 'response' => $response];
		return !$this->error ? $resp : $this->error;
	}
	
	private function get_features($data) {
		$res = false;
		if ((isset($data['external_id']) && mb_strlen($data['external_id']) > 5) || (isset($data['id']) && mb_strlen($data['id']) > 0)) {
			$res = db_get_row('SELECT * FROM ?:product_features as f, ?:product_features_descriptions as fd, ?:product_features_values as fv
			WHERE f.feature_id = ?i OR f.external_id LIKE ?s AND f.feature_id = fd.feature_id AND f.feature_id = fv.feature_id GROUP BY f.feature_id LIMIT 1', $data['id'], $data['external_id']);
		} else {
			$res = db_get_array('SELECT * FROM ?:product_features as f, ?:product_features_descriptions as fd, ?:product_features_values as fv
			WHERE f.feature_id = fd.feature_id AND f.feature_id = fv.feature_id GROUP BY f.feature_id');		
		}
		if ($res && !empty($res['feature_id'])) { // Одна характеристика, допишем все её варианты
			$res['variants'] = db_get_array('SELECT ');
		}
		return $res;
	}
	
	private function update_features($data) {
		
	}
	
	private function create_features($data) {
		
	}
	
	private function dell_features($data) {
		
	}
	
	/*
	public function features_variants($data, $method) {
		
		$status_code = $this->get_status_code($method);
		$resp = ['status_code' => $status_code, 'response' => $response];
		return !$this->error ? $resp : $this->error;
	}
	*/
	
	private function get_status_code($method) {
		if ($method == 'GET' || $method == 'PUT') {
			$status_code = Response::STATUS_OK;
		} elseif ($method == 'POST') {
			$status_code = Response::STATUS_CREATED;
		} elseif ($method == 'DELETE') {
			$status_code = Response::STATUS_NO_CONTENT;
		} else {
			$this->error = ['status_code' => 400, 'error' => 400, 'text_error' => 'Запрос с недопустимым методом: ' . $method, 'comments' => ['REQUEST'=> $_REQUEST]];
			$status_code = Response::STATUS_BAD_REQUEST;
		}
		return $status_code;	
	}
}

/**
	cscart_product_features
	cscart_product_features_descriptions
	cscart_product_features_values
	cscart_product_feature_variants
	cscart_product_feature_variant_descriptions
*/