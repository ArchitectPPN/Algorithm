<?php

require_once __DIR__ . '/SocketParams.php';
require_once __DIR__ . '/SocketSelectServer.php';

$params = new \Redis\NetworkProgramming\PHP\Select\SocketParams();
$params->setAddr('127.0.0.1')
    ->setPort(8887)
    ->setBacklog(5)
    ->setMaxClients(10);

$socketSev = new \Redis\NetworkProgramming\PHP\Select\SocketSelectServer($params);
$socketSev->boot();