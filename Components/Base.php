<?php
require_once('../vendor/autoload.php');
require_once('../includes/preLoading.php');
require_once('../includes/const.php');
// session_start();

use \Lukiman\Cores\Controller;
use \Lukiman\Cores\Database;
use \Lukiman\Cores\Database\Config;
use \Lukiman\Cores\Request;
use \Lukiman\Cores\Exception\Base as ExceptionBase;
use \Nyholm\Psr7\Factory\Psr17Factory;
use \Psr\Http\Message\ServerRequestInterface;
use \Lukiman\Cores\Loader;

// $serviceStartTime = new \Swoole\Atomic();
$port = 33000;

$http = new swoole_http_server("127.0.0.1", $port);
// $http = new \Swoole\HTTP\Server("127.0.0.1", $port, SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);
// $ssl_dir = '/home/erik/ssl/ca/intermediate/';
/*$ssl_dir = '/etc/ssl';
$http->set([
    'ssl_cert_file' => $ssl_dir . '/certs/ssl-cert-snakeoil.pem',
    'ssl_key_file' => $ssl_dir . '/ssl-cert-snakeoil.key',
    'open_http2_protocol' => true,
]);*/

$psr17Factory = new Psr17Factory();
$serverRequestFactory = new \Ilex\SwoolePsr7\SwooleServerRequestConverter(
    $psr17Factory,
    $psr17Factory,
    $psr17Factory,
    $psr17Factory
);


//database setting variables
$dbMaxConnection = 1;
$dbVariables = array(
	'createdConnection'	=> new \Swoole\Atomic(0),
	'instances'			=> new \Swoole\Coroutine\Channel($dbMaxConnection),
	// 'instances'			=> new \SplQueue(),
);
Database::setParameters($dbVariables['instances'], $dbMaxConnection, $dbVariables['createdConnection']);
$dbConfig = new Config(Loader::Config('Swoole_Database'));
Database::setConfig($dbConfig);
// Database::populateConnectionPool($dbConfig);

$http->on("start", function ($server) use ($port /*, &$serviceStartTime*/) {
    echo "Swoole [ver." . SWOOLE_VERSION . "] http server is started at http://127.0.0.1:$port\n";
	// $serviceStartTime->set(time());
});

// $http->on("WorkerStop", function ($server, $workerId) {
	// Process::close($workerId);
// } );

$http->on("request", 'requestHandler');

function requestHandler (\Swoole\Http\Request $request, \Swoole\Http\Response $response) {
	$responseStartTime = microtime(true);
	$fullPath = $request->server['request_uri'];
	if ($fullPath == '/favicon.ico') {
		$response->header("Content-Type", "text/plain");
		$response->end("OK\n");
	} else if ($fullPath == '/whoami/' OR $fullPath == '/whoami') {
		$response->header("Content-Type", "text/plain");
		$response->end(getServerStatus());
		echo getStats($fullPath, $responseStartTime);
	} else {
		$psr7Request = $GLOBALS['serverRequestFactory']->createFromSwoole($request);
		
		$path = explode('/', $fullPath);
		if (empty($path[0])) array_shift($path);
		if (end($path) == '') array_pop($path);
		
		$_path = $path;
		foreach ($path as $k => $v) $path[$k] = ucwords(strtolower($v));
		$class = implode('\\', $path);
		
		$retVal = null;
		$action = '';
		$_param = '';
		$params = array();
		while (!Controller::exists($class) AND !empty($class)) {
			if (!empty($action)) array_unshift($params, $_param);
			$action = array_pop($path);
			$_param = array_pop($_path);
			$class = implode('\\', $path);
		}
		
		try {
			if (empty($class)) {
				throw new ExceptionBase('Handler not found!'); //error
			}
			Controller\Base::set_action($action);
			$convertedReq = new Request($psr7Request);
			$ctrl = Controller::load($class);
			$retVal = $ctrl->execute($action, $params, $convertedReq);

			$resHeaders = $ctrl->getHeaders();
			foreach($resHeaders as $k => $v) $response->header($k, $v);
			$response->end($retVal);
		} catch (ExceptionBase $e) {
			$response->status(404);
			$response->end($e->getMessage());
			echo 'Error: ' . $e->getMessage();
		}
		echo getStats($fullPath, $responseStartTime);
	}
};

$http->start();

function getStats(string $fullPath, float $responseStartTime) {
	return "$fullPath (" . \Swoole\Coroutine::getuid() . ") :" . (microtime(true) - $responseStartTime) . ' ' . Database::getstats() . "\n";
}

function getServerStatus() {
	$stats =  $GLOBALS['http']->stats();
	return "Service run at port {$GLOBALS['port']} for " . (time() - $stats['start_time']) . " seconds.\n" . ExceptionBase::getStats() . "\n" . print_r($stats, true);
}
