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
			foreach ($this->data['values'] as $data) {
				$field_query = [];
				if (isset($data['variant_id']) && isset($data['external_id']) && mb_strlen($data['external_id']) > 5) { // (если используем в системе тип id int то, проверяем если в БД у этого объекта external_id пустое то заполняем его значением из запроса)
					$external_id = db_get_field('SELECT external_id FROM ?:product_feature_variants WHERE variant_id = ?i', (int)$data['variant_id']);
					if (!$external_id || mb_strlen($external_id) < 5) {
						db_query('UPDATE ?:product_feature_variants SET external_id = ?s WHERE variant_id = ?i', $data['external_id'], (int)$data['variant_id']);
					}
				}
				// query product_feature_variants
				$arr_fields = ['variant_id', 'external_id', 'url', 'color', 'position'];
				// подготовим допустимые поля для таблицы product_feature_variants
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
				} elseif (isset($field_query['variant_id'])) {
					$where_query = 'WHERE variant_id = ' . $field_query['variant_id'];
					unset($field_query['variant_id']);
				} else {
					$this->error = true;
					$this->error_text = 'нет ни одного идентификатора для запроса к product_feature_variants(variant_id, external_id)';
					$this->status_code = 400;
					return $response;
				}
				// запрос к таблице product_feature_variants
				db_query('UPDATE ?:product_feature_variants SET ?u ' . $where_query, $field_query);
				
				// query product_feature_variant_descriptions
				$arr_fields = ['description', 'variant', 'page_title', 'meta_keywords', 'meta_description', 'lang_code'];
				// подготовим допустимые поля для таблицы product_feature_variant_descriptions
				foreach ($arr_fields as $item) {
					foreach ($data as $k => $v) {
						if ($k == $item && mb_strlen($v) > 0) {
							$field_query[$k] = $v;
						}
					}
				}
				// соберём WHERE для запроса
				if (isset($data['external_id'])) {
					$variant_id = db_get_field('SELECT variant_id FROM ?:product_feature_variant_descriptions LIKE ?s', $data['external_id']);
					if (!$variant_id) {
						$this->error = true;
						$this->error_text = 'не удалось найти variant_id по переданному external_id в таблице product_feature_variant_descriptions';
						$this->status_code = 400;
						return $response;					
					}
					$where_query = 'WHERE variant_id = ' . $variant_id;
				} elseif (isset($data['variant_id'])) {
					$where_query = 'WHERE variant_id = ' . $data['variant_id'];
				} else {
					$this->error = true;
					$this->error_text = 'нет ни одного идентификатора для запроса к product_feature_variant_descriptions(variant_id, external_id)';
					$this->status_code = 400;
					return $response;
				}
				// запрос к таблице product_feature_variant_descriptions
				db_query('UPDATE ?:product_feature_variant_descriptions SET ?u ' . $where_query, $field_query);
			}
		} catch (Exception $e) {
			$this->error_text = $e->getMessage();
			$this->error = true;
			$this->status_code = Response::STATUS_INTERNAL_SERVER_ERROR;
		}		
	}
	
	private function create_variants() {
		$response = [];
		try {
			foreach ($this->data['values'] as $data) {
				if ($this->module_oprions['ee_2bstock_check_unique'] == 'Y' && db_get_field('SELECT variant_id FROM ?:product_feature_variant_descriptions WHERE variant LIKE ?s', $data['variant'])) {
					continue;
				}
				$field_query = [];
				// query product_feature_variants
				$arr_fields = ['external_id', 'feature_id', 'url', 'color', 'position'];
				// подготовим допустимые поля для таблицы product_feature_variants
				foreach ($arr_fields as $item) {
					foreach ($data as $k => $v) {
						if ($k == $item && mb_strlen($v) > 0) {
							$field_query[$k] = $v;
						}
					}
				}
				// запрос к таблице product_feature_variants
				$variant_id = db_query("INSERT INTO ?:product_feature_variants ?e", $field_query);
				// query product_feature_variant_descriptions
				$arr_fields = ['description', 'variant', 'page_title', 'meta_keywords', 'meta_description', 'lang_code'];
				// подготовим допустимые поля для таблицы product_feature_variant_descriptions
				foreach ($arr_fields as $item) {
					foreach ($data as $k => $v) {
						if ($k == $item && mb_strlen($v) > 0) {
							$field_query[$k] = $v;
						}
					}
				}
				$field_query['variant_id'] = $feature_id;
				// запрос к таблице product_feature_variant_descriptions
				db_query("INSERT INTO ?:product_feature_variant_descriptions ?e", $field_query);				
			}
		} catch (Exception $e) {
			$this->error_text = $e->getMessage();
			$this->error = true;
			$this->status_code = Response::STATUS_INTERNAL_SERVER_ERROR;
		}		
	}
	
	private function dell_variants() {
		$response = [];
		try {
			db_query("DELETE FROM ?:product_feature_variants WHERE variant_id = ?i", $this->data['variant_id']);
			db_query("DELETE FROM ?:product_feature_variant_descriptions WHERE variant_id = ?i", $this->data['variant_id']);
			db_query("DELETE FROM ?:product_features_values WHERE variant_id = ?i", $this->data['variant_id']);
		} catch (Exception $e) {
			$this->error_text = $e->getMessage();
			$this->error = true;
			$this->status_code = Response::STATUS_INTERNAL_SERVER_ERROR;
		}		
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
			foreach ($this->data['values'] as $data) {
				$field_query = [];
				if (isset($data['feature_id']) && isset($data['external_id']) && mb_strlen($data['external_id']) > 5) { // (если используем в системе тип id int то, проверяем если в БД у этого объекта external_id пустое то заполняем его значением из запроса)
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
			}
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
			foreach ($this->data['values'] as $data) {
				if ($this->module_oprions['ee_2bstock_check_unique'] == 'Y' && db_get_field('SELECT feature_id FROM ?:product_features_descriptions WHERE internal_name LIKE ?s', $data['internal_name'])) {
					continue;
				}				
				$field_query = [];
				// query product_features
				$arr_fields = ['external_id', 'company_id', 'feature_type', 'parent_id', 'display_on_product', 'display_on_catalog', 'display_on_header', 'status', 'comparison' ,'position'];
				// подготовим допустимые поля для таблицы product_features
				foreach ($arr_fields as $item) {
					foreach ($data as $k => $v) {
						if ($k == $item && mb_strlen($v) > 0) {
							$field_query[$k] = $v;
						}
					}
				}
				// запрос к таблице product_features
				$feature_id = db_query("INSERT INTO ?:product_features ?e", $field_query);
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
				$field_query['feature_id'] = $feature_id;
				// запрос к таблице product_features_descriptions
				db_query("INSERT INTO ?:product_features_descriptions ?e", $field_query);
			}
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
			db_query("DELETE FROM ?:product_features WHERE feature_id = ?i", $this->data['feature_id']);
			db_query("DELETE FROM ?:product_features_descriptions WHERE feature_id = ?i", $this->data['feature_id']);
			$variant_ids = db_get_fields("SELECT variant_id FROM ?:product_feature_variants WHERE feature_id = ?i", $this->data['feature_id']);
			db_query("DELETE FROM ?:product_features_values WHERE feature_id = ?i", $this->data['feature_id']);
			if (!empty($variant_ids)) {
				db_query("DELETE FROM ?:product_features_values WHERE variant_id IN (?n)", $variant_ids);
				db_query("DELETE FROM ?:product_feature_variants WHERE variant_id IN (?n)", $variant_ids);
				db_query("DELETE FROM ?:product_feature_variant_descriptions WHERE variant_id IN (?n)", $variant_ids);
				foreach ($variant_ids as $variant_id) {
					fn_delete_image_pairs($variant_id, 'feature_variant');
				}
			}
		} catch (Exception $e) {
			$this->error_text = $e->getMessage();
			$this->error = true;
			$this->status_code = Response::STATUS_INTERNAL_SERVER_ERROR;
		}		
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
			default:
				$response = 'get';				
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