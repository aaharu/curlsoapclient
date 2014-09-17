<?php
require_once __DIR__ . '/bootstrap.php';

function test($x)
{
    return $x;
}

$server = new \SoapServer(null, array('uri' => "http://test-uri/"));
$server->addFunction("test");
$server->handle();
