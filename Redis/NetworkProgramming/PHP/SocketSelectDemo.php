<?php

set_time_limit(0);
ob_implicit_flush();

$address = '127.0.0.1';
$port = 8887;
$max_clients = 10;
$clients = [];
echo "start" . PHP_EOL;
$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);

if (!socket_bind($sock, $address, $port)) {
    echo "Could not bind to address $address:$port\n";
    exit;
}

if (!socket_listen($sock, 5)) {
    echo "Could not set up socket listener\n";
    exit;
}

echo "Server started on $address:$port\n";

$clients[] = $sock;

while (true) {
    $read = $clients;
    $write = null;
    $except = null;

    $num_changed_sockets = socket_select($read, $write, $except, 0, 10);

    if ($num_changed_sockets === false) {
        echo "Socket select failed\n";
        break;
    }

    if ($num_changed_sockets > 0) {
        if (in_array($sock, $read)) {
            $new_client = socket_accept($sock);
            if ($new_client) {
                $clients[] = $new_client;
                echo "New client connected\n";
            }
            $key = array_search($sock, $read);
            unset($read[$key]);
        }

        foreach ($read as $client_sock) {
            $data = @socket_read($client_sock, 1024, PHP_NORMAL_READ);
            if ($data === false) {
                $key = array_search($client_sock, $clients);
                unset($clients[$key]);
                socket_close($client_sock);
                echo "Client disconnected\n";
                continue;
            }

            $data = trim($data);

            if (!empty($data)) {
                echo "Received message: $data\n";
                foreach ($clients as $send_sock) {
                    if ($send_sock == $sock || $send_sock == $client_sock) {
                        continue;
                    }
                    socket_write($send_sock, $data . "\n");
                }
            }
        }
    }
}

socket_close($sock);
