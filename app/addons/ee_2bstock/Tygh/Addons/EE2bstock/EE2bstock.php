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

	/**
	* Вызовет методы работы с характеристиками
	*/
	public function features() {		
		$pref = $this->get_pref_func_by_method();
		$func_name = $pref . '_features';
		return $this->template_response($this->$func_name());
	}
	
	/**
	* Вызовет методы работы с вариантами характеристик
	*/	
	public function features_variants() {		
		$pref = $this->get_pref_func_by_method();
		$func_name = $pref . '_variants';
		die($func_name);
		return $this->template_response($this->$func_name());
	}
	
	// Функции вариантов характеристик
	private function get_variants() {		
		$response = [];
		try {
			if ((isset($this->data['external_id']) && mb_strlen($this->data['external_id']) > 2) || (isset($this->data['id']) && mb_strlen($this->data['id']) > 0)) {
				$this->data['id'] = is_numeric($this->data['id']) ? $this->data['id'] : 0;
				/*$this->mysqli->real_query('SELECT * FROM ' . $this->cart_config['table_prefix'] . 'product_features as f, ' . $this->cart_config['table_prefix'] . 'product_features_descriptions as fd, ' . $this->cart_config['table_prefix'] . 'product_features_values as fv
				WHERE f.feature_id = ' . $this->data['id'] . ' OR f.external_id LIKE "' . $this->data['external_id'] . '" AND f.feature_id = fd.feature_id AND f.feature_id = fv.feature_id GROUP BY f.feature_id LIMIT 1');
				$res_mysqli = $this->mysqli->use_result();
				while ($row = $res_mysqli->fetch_assoc()) {
					$response[] = $row;
				}
				$response['variants'] = db_get_array('SELECT * FROM ?:product_feature_variants as v, ?:product_feature_variant_descriptions  as vd
				WHERE v.feature_id = ?i AND vd.variant_id = v.variant_id GROUP BY v.variant_id', $response[0]['feature_id']);*/
				if ($this->data['id']) {
					$add_query = ' v.variant_id = ' . $this->data['id'] . ' ';
				} else {
					$add_query = ' v.external_id LIKE "' . $this->data['external_id'] . '" ';
				}
				$response['variants'] = db_get_array('SELECT * FROM ?:product_feature_variants as v, ?:product_feature_variant_descriptions  as vd
				WHERE ' . $add_query . ' AND vd.variant_id = v.variant_id');
			} else {
				$response['variants'] = db_get_array('SELECT * FROM ?:product_feature_variants as v, ?:product_feature_variant_descriptions  as vd
				WHERE vd.variant_id = v.variant_id GROUP BY v.variant_id');				
			}
		} catch (Exception $e) {
			$this->error_text = $e->getMessage();
			$this->error = true;
			$this->status_code = Response::STATUS_INTERNAL_SERVER_ERROR;
		}
		return $response;
	}
	
	private function update_variants() {
		$response = [];
		try {
			$this->clean_variants_params();
			die('UPDATE VARIANTS');
		} catch (Exception $e) {
			$this->error_text = $e->getMessage();
			$this->error = true;
			$this->status_code = Response::STATUS_INTERNAL_SERVER_ERROR;
		}		
	}
	
	private function create_variants() {
		$response = [];
		try {
			
		} catch (Exception $e) {
			$this->error_text = $e->getMessage();
			$this->error = true;
			$this->status_code = Response::STATUS_INTERNAL_SERVER_ERROR;
		}		
	}
	
	private function dell_variants() {
		$response = [];
		try {
			
		} catch (Exception $e) {
			$this->error_text = $e->getMessage();
			$this->error = true;
			$this->status_code = Response::STATUS_INTERNAL_SERVER_ERROR;
		}		
	}
	
	// Очистит параметры вариаций
	private function clean_variants_params() {
		
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
			$data = $this->prepare_features_data();
			if (isset($data['feature_id']) && isset($data['external_id'])) { // (если используем в системе тип id int то, проверяем если в БД у этого объекта external_id пустое то заполняем его значением из запроса)
				$external_id = db_get_field('SELECT external_id FROM ?:product_features WHERE feature_id = ?i', (int)$data['feature_id']);
				if (!$external_id || mb_strlen($external_id) < 5) {
					db_query('UPDATE ?:product_features SET external_id = ?s WHERE feature_id = ?i', $data['external_id'], (int)$data['feature_id']);
				}
			}
			// query product_features
			$arr_fields = ['feature_id', 'external_id', 'company_id', 'feature_type', 'parent_id', 'display_on_product', 'display_on_catalog', 'display_on_header', 'status', 'comparison' ,'position'];
			// подготовим допустимые поля для таблицы product_features
			foreach ($arr_fields as $item) {
				foreach ($data as $k => $v) {
					if ($k == $item && mb_strlen($v) > 0) {
						$field_query[$k] = $v;
					}
				}
			}
			// соберём WHERE для запроса
			if (isset($field_query['external_id'])) {
				$where_query = 'WHERE external_id LIKE ' . $field_query['external_id'];
				unset($field_query['external_id']);
			} elseif (isset($field_query['feature_id'])) {
				$where_query = 'WHERE feature_id = ' . $field_query['feature_id'];
				unset($field_query['feature_id']);
			} else {
				$this->error = true;
				$this->error_text = 'нет ни одного идентификатора для запроса к product_features(feature_id, external_id)';
				$this->status_code = 400;
				return $response;
			}
			// запрос к таблице product_features
			db_query('UPDATE ?:product_features SET ?u ' . $where_query, $field_query);
			
			// query product_features_descriptions
			$arr_fields = ['description', 'full_description', 'prefix', 'suffix', 'lang_code', 'internal_name'];
			// подготовим допустимые поля для таблицы product_features_descriptions
			foreach ($arr_fields as $item) {
				foreach ($data as $k => $v) {
					if ($k == $item && mb_strlen($v) > 0) {
						$field_query[$k] = $v;
					}
				}
			}
			// соберём WHERE для запроса
			if (isset($data['external_id'])) {
				$feature_id = db_get_field('SELECT feature_id FROM ?:product_features LIKE ?s', $data['external_id']);
				if (!$feature_id) {
					$this->error = true;
					$this->error_text = 'не удалось найти feature_id по переданному external_id в таблице product_features';
					$this->status_code = 400;
					return $response;					
				}
				$where_query = 'WHERE feature_id = ' . $feature_id;
			} elseif (isset($data['feature_id'])) {
				$where_query = 'WHERE feature_id = ' . $data['feature_id'];
			} else {
				$this->error = true;
				$this->error_text = 'нет ни одного идентификатора для запроса к product_features_descriptions(feature_id, external_id)';
				$this->status_code = 400;
				return $response;
			}
			// запрос к таблице product_features_descriptions
			db_query('UPDATE ?:product_features_descriptions SET ?u ' . $where_query, $field_query);
			$this->status_code = 201;
		} catch (Exception $e) {
			$this->error_text = $e->getMessage();
			$this->error = true;
			$this->status_code = Response::STATUS_INTERNAL_SERVER_ERROR;
		}
		return $response;
	}
	
	private function create_features() {
		$response = [];
		try {
			$data = $this->prepare_features_data();
			$response['create_features'] = 'OK';
		} catch (Exception $e) {
			$this->error_text = $e->getMessage();
			$this->error = true;
			$this->status_code = Response::STATUS_INTERNAL_SERVER_ERROR;
		}
		return $response;		
	}
	
	private function dell_features() {
		$response = [];
		try {
			$response['dell_features'] = 'OK';
		} catch (Exception $e) {
			$this->error_text = $e->getMessage();
			$this->error = true;
			$this->status_code = Response::STATUS_INTERNAL_SERVER_ERROR;
		}		
	}
	
	// подготовим данные для характеристики
	private function prepare_features_data() {
		$data = [];
		
		if (isset($this->data['feature_id']) && mb_strlen($this->data['feature_id']) > 0) {
			$data['feature_id'] = $this->data['feature_id'];
		}		
		if (isset($this->data['company_id']) && mb_strlen($this->data['company_id']) > 0) {
			$data['company_id'] = $this->data['company_id'];
		}		
		if (isset($this->data['feature_type']) && mb_strlen($this->data['feature_type']) > 0) {
			$data['feature_type'] = $this->data['feature_type'];
		}		
		if (isset($this->data['parent_id']) && mb_strlen($this->data['parent_id']) > 0) {
			$data['parent_id'] = $this->data['parent_id'];
		}		
		if (isset($this->data['display_on_product']) && mb_strlen($this->data['display_on_product']) > 0) {
			$data['display_on_product'] = $this->data['display_on_product'];
		}		
		if (isset($this->data['display_on_catalog']) && mb_strlen($this->data['display_on_catalog']) > 0) {
			$data['display_on_catalog'] = $this->data['display_on_catalog'];
		}		
		if (isset($this->data['display_on_header']) && mb_strlen($this->data['display_on_header']) > 0) {
			$data['display_on_header'] = $this->data['display_on_header'];
		}		
		if (isset($this->data['description']) && mb_strlen($this->data['description']) > 0) {
			$data['description'] = $this->data['description'];
		}
		if (isset($this->data['internal_name']) && mb_strlen($this->data['internal_name']) > 0) {
			$data['internal_name'] = $this->data['internal_name'];
		}		
		if (isset($this->data['lang_code']) && mb_strlen($this->data['lang_code']) > 0) {
			$data['lang_code'] = $this->data['lang_code'];
		}		
		if (isset($this->data['prefix']) && mb_strlen($this->data['prefix']) > 0) {
			$data['prefix'] = $this->data['prefix'];
		}		
		if (isset($this->data['suffix']) && mb_strlen($this->data['suffix']) > 0) {
			$data['suffix'] = $this->data['suffix'];
		}		
		if (isset($this->data['categories_path']) && mb_strlen($this->data['categories_path']) > 0) {
			$data['categories_path'] = $this->data['categories_path'];
		}		
		if (isset($this->data['full_description']) && mb_strlen($this->data['full_description']) > 0) {
			$data['full_description'] = $this->data['full_description'];
		}		
		if (isset($this->data['status']) && mb_strlen($this->data['status']) > 0) {
			$data['status'] = $this->data['status'];
		}		
		if (isset($this->data['comparison']) && mb_strlen($this->data['comparison']) > 0) {
			$data['comparison'] = $this->data['comparison'];
		}		
		if (isset($this->data['position']) && mb_strlen($this->data['position']) > 0) {
			$data['position'] = $this->data['position'];
		}		
		if (isset($this->data['external_id']) && mb_strlen($this->data['external_id']) > 0) {
			$data['external_id'] = $this->data['external_id'];
		}
		return $data;		
	}
	
	// Вернёт префикс функции исходя из метода запроса
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