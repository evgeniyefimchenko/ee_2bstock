<?php
if (!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Registry;
use Tygh\Addons\EE2bstock\EE2bstock;

function fn_ee_2bstock_install() {
	$db_name = Registry::get("config.db_name");
	$external_id = false;
	$external_id = db_get_field('SELECT 101 FROM INFORMATION_SCHEMA.COLUMNS WHERE `table_name` = "?:product_features" AND `table_schema` = "' . $db_name . '" AND `column_name` = "external_id"'); 	
	if (!$external_id) {
		db_query('ALTER TABLE `?:product_features` ADD `external_id` varchar(255) NULL DEFAULT NULL');	
	}
	$external_id = false;
	$external_id = db_get_field('SELECT 101 FROM INFORMATION_SCHEMA.COLUMNS WHERE `table_name` = "?:product_feature_variants" AND `table_schema` = "' . $db_name . '" AND `column_name` = "external_id"'); 	
	if (!$external_id) {
		db_query('ALTER TABLE `?:product_feature_variants` ADD `external_id` varchar(255) NULL DEFAULT NULL');	
	}
}

function fn_ee_2bstock_uninstall() {
	return true;
}

function fn_ee_2bstock_get_trigger_url() {
	return 'Используйте следующий адрес для запроса: ' . fn_url('index.php') . '2bstock';
}

function fn_ee_2bstock_get_last_request() {
	$log_path = __DIR__ . '/Tygh/Addons/EE2bstock/logs/last_request.txt';
	if (file_exists($log_path)) {
		return file_get_contents($log_path);
	} else {
		return 'Нет запросов.';
	}	
}