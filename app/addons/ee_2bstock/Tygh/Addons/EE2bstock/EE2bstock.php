<?php

namespace Tygh\Addons\EE2bstock;

use Tygh\Registry;
use Tygh\Api\Response;

/**
* Ответ функции имеет следующие обязательные поля в массиве.
* status_code - код статуса ответа int
* response - массив ответа array
* error - статус ошибки boolean
* error_text - текст ошибки если имеется str
*/

class EE2bstock {
    
	public $module_oprions = [];
	public $error = false;
	public $error_text = '';
	public $status_code;
	private $response = ['status_code' => 0, 'response' => [], 'error' => false, 'error_text' => ''];
	private $data;
	private $method;
	private $cart_config;
	private $mysqli;
	
	public function __construct($data, $method) {
		$this->data = $data;
		$this->method = $method;
		$this->status_code = $this->get_status_code_by_method();
		$this->template_response([]);
		$this->module_oprions = Registry::get('addons.ee_2bstock');
		$this->cart_config = Registry::get('config');
		$this->mysqli = new \mysqli($this->cart_config['db_host'], $this->cart_config['db_user'], $this->cart_config['db_password'], $this->cart_config['db_name']);
    }

	public function features() {		
		$pref = $this->get_pref_func_by_method();
		$func_name = $pref . '_features';
		return $this->template_response($this->$func_name());
	}
	
	// Функции характеристик
	private function get_features() {		
		$response = [];
		try {
			if ((isset($this->data['external_id']) && mb_strlen($this->data['external_id']) > 2) || (isset($this->data['id']) && mb_strlen($this->data['id']) > 0)) {											
				$this->data['id'] = is_numeric($this->data['id']) ? $this->data['id'] : 0;
				$this->mysqli->real_query('SELECT * FROM ' . $this->cart_config['table_prefix'] . 'product_features as f, ' . $this->cart_config['table_prefix'] . 'product_features_descriptions as fd, ' . $this->cart_config['table_prefix'] . 'product_features_values as fv
				WHERE f.feature_id = ' . $this->data['id'] . ' OR f.external_id LIKE "' . $this->data['external_id'] . '" AND f.feature_id = fd.feature_id AND f.feature_id = fv.feature_id GROUP BY f.feature_id LIMIT 1');
			} else {
				$this->mysqli->real_query('SELECT * FROM ' . $this->cart_config['table_prefix'] . 'product_features as f, ' . $this->cart_config['table_prefix'] . 'product_features_descriptions as fd, ' . $this->cart_config['table_prefix'] . 'product_features_values as fv 
				WHERE f.feature_id = fd.feature_id AND f.feature_id = fv.feature_id GROUP BY f.feature_id');								
			}
			
			$res_mysqli = $this->mysqli->use_result();			
			while ($row = $res_mysqli->fetch_assoc()) {
				$response[] = $row;
			}			
			
			if (count($response) == 1 && !empty($response[0]['feature_id'])) { // Одна характеристика, допишем все её варианты
				$response['variants'] = db_get_array('SELECT * FROM ?:product_feature_variants as v, ?:product_feature_variant_descriptions  as vd
				WHERE v.feature_id =?i AND vd.variant_id = v.variant_id GROUP BY v.variant_id', $response[0]['feature_id']);
			}
		} catch (Exception $e) {
			$this->error_text = $e->getMessage();
			$this->error = true;
			$this->status_code = Response::STATUS_INTERNAL_SERVER_ERROR;
		}
		return $response;
	}
	
	private function update_features() {
		$response = [];
		try {
			
		} catch (Exception $e) {
			$this->error_text = $e->getMessage();
			$this->error = true;
			$this->status_code = Response::STATUS_INTERNAL_SERVER_ERROR;
		}		
	}
	
	private function create_features() {
		$response = [];
		try {
			
		} catch (Exception $e) {
			$this->error_text = $e->getMessage();
			$this->error = true;
			$this->status_code = Response::STATUS_INTERNAL_SERVER_ERROR;
		}		
	}
	
	private function dell_features() {
		$response = [];
		try {
			
		} catch (Exception $e) {
			$this->error_text = $e->getMessage();
			$this->error = true;
			$this->status_code = Response::STATUS_INTERNAL_SERVER_ERROR;
		}		
	}
	
	// Функции вариантов характеристик
	
	private function get_pref_func_by_method() {
		switch ($this->method) {
			case 'GET':
				$response = 'get';
				break;
			case 'PUT':
				$response = 'update';
				break;
			case 'POST':
				$response = 'create';
				break;
			case 'DELETE':
				$response = 'dell';
				break;
		}
		return $response;		
	}
	
	private function get_status_code_by_method() {
		if ($this->method == 'GET' || $this->method == 'PUT') {
			$status_code = Response::STATUS_OK;
		} elseif ($this->method == 'POST') {
			$status_code = Response::STATUS_CREATED;
		} elseif ($this->method == 'DELETE') {
			$status_code = Response::STATUS_NO_CONTENT;
		} else {
			$this->error = true;			
			$this->error_text = 'Неверный метод запроса ' . $this->method;			
			$status_code = Response::STATUS_BAD_REQUEST;
		}
		return $status_code;	
	}
	
	private function template_response($func_result) {		
		 if ($this->error) {
			 $func_result = ['REQUEST' => var_export($this->data, true)];
		 }
		 $this->response = ['status_code' => $this->status_code, 'response' => $func_result, 'error' => $this->error, 'error_text' => $this->error_text];
		 return $this->response;
	}
}

/**
	требуемые таблицы
	cscart_product_features
	cscart_product_features_descriptions
	cscart_product_features_values
	cscart_product_feature_variants
	cscart_product_feature_variant_descriptions
*/