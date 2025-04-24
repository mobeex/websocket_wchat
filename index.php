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

	echo "[" . date('Y-m-d H:i:s') . "] Mensagem recebida deeeeee {$frame->fd}: {$frame->data}\n";
	$data = json_decode($frame->data);

	if ($data->evento == "wsConversa") {
		if ($data->action == "refreshOn" || $data->action == "refreshOnComplementar" || $data->action == "refreshOff") {
			$parametros = [
				'evento'        => $data->evento,
				'action'        => $data->action,
				'wabaID'        => $data->wabaID,
				'phoneNumberID' => $data->phoneNumberID,
				'userID'        => $data->userID,
				'usuario'       => $data->usuario,
				'maxIDB'		=> $data->maxIDB
			];
			$url = 'https://www.megalochat.com/sandbox/apis/consumida/publica/index.php?evento=wsConversa&action=' . $data->action . '&wabaID=' . $data->wabaID . '&phoneNumberID=' . $data->phoneNumberID . '&usuario=' . $data->usuario . '&userID=' . $data->userID . '&maxIDB=' . $data->maxIDB;
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 0);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($ch);
			curl_close($ch);
			$server->push($frame->fd, $response);
		} elseif ($data->action == "getAllMessages") {
			$parametros = [
				'evento' => 'wsConversa',
				'action' => 'getAllMessages',
				'wabaID' => $data->wabaID,
				'phoneNumberID' => $data->phoneNumberID,
				'userID' => $data->userID,
				'usuario' => $data->usuario,
				'provedorID' => $data->provedorID,
				'chatID' => $data->chatID,
				'sessao' => $data->sessao,
				'maxIDB' => $data->maxIDB,
			];

			$url = 'https://www.megalochat.com/sandbox/apis/consumida/publica/index.php?' .
				'evento=wsConversa' .
				'&API=getAllMessages' .
				'&action=getAllMessages' .
				'&wabaID=' . $data->wabaID .
				'&phoneNumberID=' . $data->phoneNumberID .
				'&userID=' . $data->userID .
				'&usuario=' . $data->usuario .
				'&provedorID=' . $data->provedorID .
				'&chatID=' . $data->chatID .
				'&sessao=' . $data->sessao .
				'&maxIDB=' . $data->maxIDB;
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($ch);
			curl_close($ch);
			$RESPONSE = json_decode($response, true);
			$RESPONSE['evento'] = "wsConversa";
			$RESPONSE['action'] = "getAllMessagesResponse";
			$response = json_encode($RESPONSE, JSON_UNESCAPED_UNICODE);
			$server->push($frame->fd, $response);
		} elseif ($data->action == "etiquetar_conversa") {
			$url = 'https://www.megalochat.com/sandbox/apis/consumida/publica/index.php?' .
				'action=' . 'etiquetar_conversa' . '&' .
				'classeDoElemento=' . urlencode($data->classeDoElemento) .  '&' .
				'evento=' . 'wsConversa' .  '&' .
				'phoneNumberID=' . $data->phoneNumberID .  '&' .
				'userID=' . $data->userID .  '&' .
				'userNome=' . $data->userNome .  '&' .
				'usuario=' . $data->usuario .  '&' .
				'wabaID=' . $data->wabaID;
			// GET:
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($ch);
			$err = curl_error($ch);
			curl_close($ch);
			$server->push($frame->fd, $response); // => Envia a mensagem para os usuarios
			echo "conexão {$from->resourceId}: usuario {$data->usuario} (wabaID {$data->wabaID}), recebeu o response " . $response . $err . "\nAgent {$data->userNome} (userID {$data->userID}), quer acessar etiquetas da conversa, conforme action recebido de valor igual a {$data->action}, com os seguintes dados: {$data->classeDoElemento}. \n\n";
		}
		if ($data->action == "hide") {
			$response = FASTcUrlPOST($frame->data);
			$server->push($frame->fd, $response); // => Envia a mensagem para os usuarios
		} elseif ($data->action == "pin") {
			$response = FASTcUrlPOST($frame->data);
			$server->push($frame->fd, $response); // => Envia a mensagem para os usuarios
		} elseif ($data->action == "unpin") {
			$response = FASTcUrlPOST($frame->data);
			$server->push($frame->fd, $response); // => Envia a mensagem para os usuarios
		} elseif ($data->action == "read") {
			$response = FASTcUrlPOST($frame->data);
			$server->push($frame->fd, $response); // => Envia a mensagem para os usuarios
		} elseif ($data->action == "unread") {
			$response = FASTcUrlPOST($frame->data);
			$server->push($frame->fd, $response); // => Envia a mensagem para os usuarios
		} elseif ($data->action == "archived") {
			$response = FASTcUrlPOST($frame->data);
			$server->push($frame->fd, $response); // => Envia a mensagem para os usuarios
		} elseif ($data->action == "unarchived") {
			$response = FASTcUrlPOST($frame->data);
			$server->push($frame->fd, $response); // => Envia a mensagem para os usuarios
		} elseif ($data->action == "ocultar_templates") {
			$response = FASTcUrlPOST($frame->data);
			$server->push($frame->fd, $response); // => Envia a mensagem para os usuarios
		} elseif ($data->action == "exibir_templates") {
			$response = FASTcUrlPOST($frame->data);
			$server->push($frame->fd, $response); // => Envia a mensagem para os usuarios
		} elseif ($data->action == "devolver_atendimento_a_iara") {
			$response = FASTcUrlPOST($frame->data);
			$server->push($frame->fd, $response); // => Envia a mensagem para os usuarios
		} elseif ($data->action == "saveFlags") {
			$response = FASTcUrlPOST($frame->data);
			$server->push($frame->fd, $response); // => Envia a mensagem para os usuarios
		} elseif ($data->action == "receiving") {
			$response = FASTcUrlPOST($frame->data);
			$server->push($frame->fd, $response); // => Envia a mensagem para os usuarios
		}
	} elseif ($data->evento == "wsGrupos") {
		if ($data->action == "delGrupo") {
			$response = FASTcUrlPOST($frame->data);
			$server->push($frame->fd, $response); // => Envia a mensagem para os usuarios
		}
		if ($data->action == "grupoAdd") {
			$response = FASTcUrlPOST($frame->data);
			$server->push($frame->fd, $response); // => Envia a mensagem para os usuarios
		}
		if ($data->action == "AddMemberToGroup") {
			$response = FASTcUrlPOST($frame->data);
			$server->push($frame->fd, $response); // => Envia a mensagem para os usuarios
		} elseif ($data->action == "saveFlags") {
			$response = FASTcUrlPOST($frame->data);
			$server->push($frame->fd, $response); // => Envia a mensagem para os usuarios
		}
	} elseif ($data->evento == "contact") {
		$url = 'https://www.megalochat.com/sandbox/apis/consumida/privada/contact_api.php';
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 300,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_POST => 1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => is_string($frame->data) ? $frame->data : json_encode($frame->data),
			CURLOPT_HTTPHEADER => [
				"Content-Type: application/json"
			],
		]);
		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
		$server->push($frame->fd, $response);
	} elseif ($data->evento == "crm") {
		if ($data->action == "update_crm_ask") {
			$url = 'https://www.megalochat.com/sandbox/apis/consumida/privada/crm_api.php';
			$curl = curl_init();
			curl_setopt_array($curl, [
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 300,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "PUT",
				CURLOPT_POSTFIELDS => is_string($frame->data) ? $frame->data : json_encode($frame->data), //CURLOPT_POSTFIELDS =>$frame->data, // <= são os dados recebidos do index.js
				CURLOPT_HTTPHEADER => [
					"Content-Type: application/json"
				],
			]);
			$response = curl_exec($curl);
			$err = curl_error($curl);
			curl_close($curl);
			$server->push($frame->fd, $response);
		}
	} elseif ($data->evento == "wsConectividade") {
		if ($data->action == "focused" || $data->action == "blured") {
			## $response = json_encode(["action_recebida_no_wss: " => $data->action]);
			## $server->push($frame->fd, $response);
		}
	} elseif ($data->evento == "message") {

		if ($data->action == "webmessaging") {
			$url = 'https://www.megalochat.com/sandbox/apis/consumida/publica/index.php'; # $url = 'https://www.megalochat.com/sandbox/apis/consumida/publica/index.php?evento=message&action=' . $data->action;
			$curl = curl_init();
			curl_setopt_array($curl, [
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_POST => 1,
				CURLOPT_CUSTOMREQUEST => "PUT",
				CURLOPT_POSTFIELDS => is_string($frame->data) ? $frame->data : json_encode($frame->data), //CURLOPT_POSTFIELDS =>$frame->data, // <= são os dados recebidos do index.js
				CURLOPT_HTTPHEADER => [
					"Content-Type: application/json"
				],
			]);
			$response = curl_exec($curl);
			$err = curl_error($curl);
			curl_close($curl);
			$server->push($frame->fd, $response);
		} else {
			foreach ($server->connections as $fd) {
				if ($server->isEstablished($fd)) {
					$server->push($fd, $frame->data);
				}
			}
		}
	} elseif ($data->evento == "start") {
		$response = FASTcUrlPOST($frame->data);
		$server->push($frame->fd, $response); // => Envia a mensagem para os usuarios
	} else {
		$response = json_encode([
			'status' => 201,
			'message' => 'Recurso criado com sucesso.',
			'data' => $frame->data
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
		$server->push($frame->fd, $response);
	}
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
