<?
use Tygh\Registry;
use Tygh\Api\Response;
use Tygh\Api\Request;
use Tygh\Addons\EE2bstock\EE2bstock;
/**
* admin@makeshop.pro
* Ml3179C572b3Jfo03R20GYr9z0l7I23X
*/
$module_oprions = Registry::get('addons.ee_2bstock');
$log_path = DIR_ROOT . '/app/addons/ee_2bstock/Tygh/Addons/EE2bstock/logs/';

$request = new Request();	
$content_type = $request->getContentType();
$accept_type = $request->getAcceptType();
$method = $request->getMethod();
unset($request);

$auth['user'] = $_SERVER['PHP_AUTH_USER'] ? $_SERVER['PHP_AUTH_USER'] : $_REQUEST['user'];
$auth['api_key'] = $_SERVER['PHP_AUTH_PW'] ? $_SERVER['PHP_AUTH_PW'] : $_REQUEST['api_key'];

$_REQUEST['CONTENT_TYPE'] = $content_type;
$_REQUEST['HTTP_ACCEPT'] = $_SERVER['HTTP_ACCEPT'];
$_REQUEST['PHP_AUTH_PW'] = $auth['api_key'];
$_REQUEST['PHP_AUTH_USER'] = $auth['user'];
$_REQUEST['method'] = $method;

//die(var_export($_REQUEST, true));

file_put_contents($log_path . '/last_request.txt', 'Дата: ' . date("Y-m-d H:i:s") . '<br/><br/>', LOCK_EX);

if ($module_oprions['ee_2bstock_https'] == 'Y' && !defined('HTTPS')) {
	$resp = ['error' => true, 'status_code' => 403, 'text_error' => 'Разрешен только HTTPS протокол магазина.', 'response' => ['REQUEST'=> $_REQUEST]];	
	$status_response = Response::STATUS_FORBIDDEN;	
}

if (!isset($auth['user']) && !isset($auth['user']) && !$error) {
	$resp = ['error' => true, 'status_code' => 401, 'text_error' => 'Нет данных для авторизации.', 'response' => ['REQUEST'=> $_REQUEST]];	
	$status_response = Response::STATUS_UNAUTHORIZED;
} else {
	$user_data = fn_get_api_user($auth['user'], $auth['api_key']);
	if (isset($user_data['status']) && $user_data['status'] == 'A' || ($module_oprions['ee_2bstock_admin'] == 'Y' && $user_data['user_type'] == 'A')) {
		if ($content_type != 'application/json' && $module_oprions['ee_2bstock_full_log'] != 'Y') {
			$resp = ['error' => true, 'status_code' => 400, 'text_error' => 'Неверный формат запроса(content_type).', 'response' => ['REQUEST'=> $_REQUEST]];
			$status_response = Response::STATUS_BAD_REQUEST;			
		} else {
			$EE2bstock = new EE2bstock($_REQUEST, $method);
			if (!$EE2bstock->error) {
				if (isset($mode) && isset($action) && method_exists($EE2bstock, $mode . '_' . $action)) {
					$func_name = $mode . '_' . $action;
					$resp = $EE2bstock->$func_name();
					$status_response = $resp['status_code'];
				} elseif (isset($mode) && method_exists($EE2bstock, $mode)) {
					$resp = $EE2bstock->$mode();
					$status_response = $resp['status_code'];
				} else {
					$resp = ['error' => true, 'status_code' => 403, 'text_error' => 'Необходимо указать сущность(пример: ' . fn_url('index.php') . '2bstock/features) и передать достаточные POST параметры.', 'response' => ['REQUEST'=> $_REQUEST]];	
					$status_response = Response::STATUS_FORBIDDEN;				
				}
			} else {
				$resp = $EE2bstock->response;
				$status_response = $resp['status_code'];
			}
		}		
	} else {
		$resp = ['error' => true, 'status_code' => 401, 'text_error' => 'Нет допустимых прав для пользователя ' . $auth['user'], 'response' => ['REQUEST'=> $_REQUEST]];	
		$status_response = Response::STATUS_UNAUTHORIZED;	
	}

}

if ($module_oprions['ee_2bstock_error_log'] == 'Y' && isset($resp['error'])) {
	file_put_contents($log_path . 'errors.txt', '<br/>' . date("Y-m-d H:i:s") . '<br/>' . 'Ошибки:' . '<br/>' . var_export($resp, true) . '<br/>', FILE_APPEND | LOCK_EX);
}

if ($module_oprions['ee_2bstock_full_log'] == 'Y') { // Если установлена запись полного лога, то идёт вывод на экран всего что пришло в ответе после обработки
	echo json_encode($resp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	file_put_contents($log_path . '/full_logs.txt', '<br/>' . date("Y-m-d H:i:s") . '<br/>' . 'SERVER: ' . '<br/>' .  var_export($_SERVER, true) . '<br/>' . 'Ответ-Запрос:' . '<br/>' . var_export($resp, true) . '<br/>', FILE_APPEND | LOCK_EX);	
}

if (array_search($resp['status_code'], [Response::STATUS_OK, Response::STATUS_CREATED, Response::STATUS_NO_CONTENT]) !== false && $module_oprions['ee_2bstock_full_log'] != 'Y') {
	echo json_encode($resp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

if ($resp['error'] === true) {
	unset($resp['response']);
} else {
	unset($resp['error']); unset($resp['error_text']);
}

file_put_contents($log_path . '/last_request.txt', '<b>Запрос:</b>' . '<br/>' . var_export($_REQUEST, true) . '<br/><br/>' . '<b>Ответ:</b>' . '<br/>' . var_export($resp, true), FILE_APPEND | LOCK_EX);

$response = new Response($resp['status_code']);
$response->send();
die;
