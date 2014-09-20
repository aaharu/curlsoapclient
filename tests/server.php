<?php
require_once __DIR__ . '/bootstrap.php';

ini_set('date.timezone', 'Asia/Tokyo');
ini_set('soap.wsdl_cache_enabled', 0);

function_exists('getallheaders') && error_log(var_export(getallheaders(), true));

$redirect = (int) @$_GET['redirect'];
if ($redirect > 0) {
    --$redirect;
    header("Location: http://localhost:8000/tests/server.php?redirect=$redirect");
    exit();
}

function test($x)
{
    return $x;
}

$server = new \SoapServer(null, array('uri' => "http://test-uri/"));
$server->addFunction("test");
$server->handle();
