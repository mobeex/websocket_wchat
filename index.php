<?php

use Swoole\WebSocket\Server;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;

// Criando servidor WebSocket com SSL
$server = new Server("0.0.0.0", 9502, SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);

// Configurar SSL
$server->set([
	'ssl_cert_file' => '/etc/letsencrypt/live/megalochat.com/fullchain.pem',
	'ssl_key_file' => '/etc/letsencrypt/live/megalochat.com/privkey.pem',
	'daemonize' => false,
	'log_file' => __DIR__ . '/swoole.log',
]);
echo "[" . date('Y-m-d H:i:s') . "] Servidor WebSocket iniciado na porta 9502 com SSL (WSS)\n";

$server->on("open", function (Server $server, Request $request) {
	echo "[" . date('Y-m-d H:i:s') . "] Nova conexão: {$request->fd}\n";
});

$server->on("message", function (Server $server, Frame $frame) {
	$response = json_encode([
		'status' => 201,
		'message' => 'Recurso criado com sucesso.',
		'data' => $frame->data
	], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
	$server->push($frame->fd, $response);
});

$server->on("close", function (Server $server, int $fd) {
	echo "[" . date('Y-m-d H:i:s') . "] Conexão fechada: {$fd}\n";
});

$server->start();


function encaminharParaApi($data)
{
	$command = 'php /home/megalochat/public_html/sandbox/apis/consumida/privada/index.php ' . escapeshellarg(json_encode($data));
	$output = shell_exec($command);
	// $output conterá a saída do script
}

function urlPublicAPI($data)
{
	// Garante que $data->classeDoElemento seja uma string (mesmo que vazia)
	$classString = isset($data->classeDoElemento) ? $data->classeDoElemento : ''; # $classString = $data->classeDoElemento;
	$classes = explode(" ", $classString);
	foreach ($classes as $classe) {
		if (strlen($classe) >= 2) {
			switch (substr($classe, 0, 1)) {
				case 'P':
					$provedorID = substr($classe, 1, 15);
					break;
				case 'C':
					$chatID = substr($classe, 1, 15);
					break;
				case 'S':
					$sessao = substr($classe, 1, 15);
					break;
				case 'F':
					$flagID = substr($classe, 1, 15);
					break;
				case 'U':
					$userID = substr($classe, 1, 15);
					break;
				case 'G':
					$deptoID = substr($classe, 1, 15);
					break;
				case 'R':
					$registro = substr($classe, 1, 15);
					break;
				default:
					$elemento = $classe;
			}
		}
	}
	$url = 'https://www.megalochat.com/sandbox/apis/consumida/publica/index.php?' .
		'api=' . $data->action . '&' .
		'elemento=' . $elemento .  '&' .
		'provedorID=' . $provedorID .  '&' .
		'chatID=' . $chatID .  '&' .
		'sessao=' . $sessao .  '&' .
		'registro=' . $registro .  '&' .
		'flagID=' . $flagID .  '&' .
		'deptoID=' . $deptoID .  '&' .
		'evento=' . $data->evento .  '&' .
		'wabaID=' . $data->wabaID .  '&' .
		'phoneNumberID=' . $data->phoneNumberID .  '&' .
		'userID=' . $data->userID .  '&' .
		'userNome=' . $data->userNome .  '&' .
		'usuario=' . $data->usuario .  '&' .
		'ip=' . $data->ip .  '&' .
		'dispositivo=' . $data->dispositivo /* .  '&' .
	'userAgent=' . urlencode($data->userAgent) */;
	return $url;
}


function cUrlGET($url)
{
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$response = curl_exec($ch);
	if ($err = curl_error($ch)) {
		$response = $err;
	}
	curl_close($ch);
	return $response;
}


function FASTcUrlPOST($data)
{
	$url = 'https://www.megalochat.com/sandbox/apis/consumida/publica/index.php';
	$curl = curl_init();
	curl_setopt_array($curl, [
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 1,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_POSTFIELDS => is_string($data) ? $data : json_encode($data), // <= são os dados recebidos de wchat
		CURLOPT_HTTPHEADER => [
			"Content-Type: application/json"
		],
	]);
	$response = curl_exec($curl);
	$err = curl_error($curl);
	curl_close($curl);
	return $response;
}
