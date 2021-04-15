<?
use Tygh\Registry;
use Tygh\Api\Response;
use Tygh\Api\Request;
use Tygh\Addons\EE2bstock\EE2bstock;

$module_oprions = Registry::get('addons.ee_2bstock');

$auth['user'] = $_SERVER['PHP_AUTH_USER'];
$auth['api_key'] = $_SERVER['PHP_AUTH_PW'];

$_REQUEST = array_merge(json_decode(file_get_contents('php://input'), true), $_REQUEST);
$_REQUEST['CONTENT_TYPE'] = $_SERVER['CONTENT_TYPE'];
$_REQUEST['HTTP_ACCEPT'] = $_SERVER['HTTP_ACCEPT'];
$_REQUEST['PHP_AUTH_PW'] = $auth['api_key'];
$_REQUEST['PHP_AUTH_USER'] = $auth['user'];
$_REQUEST = filter_input_array(INPUT_REQUEST, array_map('trim', $_REQUEST));

$log_path = DIR_ROOT . '/app/addons/ee_2bstock/Tygh/Addons/EE2bstock/logs/last_request.txt';
file_put_contents($log_path, var_export($_REQUEST, true), LOCK_EX);

if ($module_oprions['ee_2bstock_https'] == 'Y' && !defined('HTTPS')) {
	$resp = ['error' => 403, 'text_error' => 'Разрешен только HTTPS протокол магазина.', 'comments' => ['REQUEST'=> $_REQUEST]];	
	$status_response = STATUS_FORBIDDEN;	
}

if (!isset($auth['user']) && !isset($auth['user']) && !$error) {
	$resp = ['error' => 401, 'text_error' => 'Нет данных для авторизации.', 'comments' => ['REQUEST'=> $_REQUEST]];	
	$status_response = Response::STATUS_UNAUTHORIZED;
} else {
	$user_data = fn_get_api_user($auth['user'], $auth['api_key']);
	if (isset($user_data['status']) && $user_data['status'] == 'A' || ($module_oprions['ee_2bstock_admin'] == 'Y' && $user_data['user_type'] == 'A')) {
		$request = new Request();	
		$content_type = $request->getContentType();
		$accept_type = $request->getAcceptType();
		$method = $request->getMethod();
		unset($request);
		if ($content_type != 'application/json') {
			$resp = ['error' => 400, 'text_error' => 'Неверный формат запроса.', 'comments' => ['REQUEST'=> $_REQUEST]];
			$status_response = Response::STATUS_BAD_REQUEST;	
		} else {		
			$EE2bstock = new EE2bstock();
			if (isset($mode) && isset($action) && method_exists($EE2bstock, $mode . '_' . $action)) {
				$func_name = $mode . '_' . $action;
				$resp = $EE2bstock->$func_name($_REQUEST, $method);
				$status_response = $resp['status_code'];
			} elseif (isset($mode) && method_exists($EE2bstock, $mode)) {
				$resp = $EE2bstock->$mode($_REQUEST, $method);
				$status_response = $resp['status_code'];
			} else {
				$resp = ['error' => 403, 'text_error' => 'Необходимо указать сущность(пример: ' . fn_url('index.php') . '2bstock/features) и передать достаточные POST параметры.', 'comments' => ['REQUEST'=> $_REQUEST]];	
				$status_response = Response::STATUS_FORBIDDEN;				
			}
		}		
	} else {
		$resp = ['error' => 401, 'text_error' => 'Нет допустимых прав для пользователя ' . $auth['user'], 'comments' => ['REQUEST'=> $_REQUEST]];	
		$status_response = Response::STATUS_UNAUTHORIZED;	
	}

}

if ($module_oprions['ee_2bstock_error_log'] == 'Y' && isset($resp['error'])) {
	file_put_contents(__DIR__ . '/logs/errors.txt', PHP_EOL . date("Y-m-d H:i:s") . PHP_EOL . 'Ошибки:' . PHP_EOL . var_export($resp, true) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

if ($module_oprions['ee_2bstock_full_log'] == 'Y') {
	file_put_contents(__DIR__ . '/logs/errors.txt', PHP_EOL . date("Y-m-d H:i:s") . PHP_EOL . 'SERVER: ' . PHP_EOL .  var_export($_SERVER) . PHP_EOL . 'Ответ-Запрос:' . PHP_EOL . var_export($resp, true) . PHP_EOL, FILE_APPEND | LOCK_EX);	
}

echo json_encode($resp);
$response = new Response($status_response);
$response->send();		
exit;
