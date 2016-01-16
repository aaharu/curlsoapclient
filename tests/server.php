<?php
require_once __DIR__ . '/bootstrap.php';

ini_set('date.timezone', 'Asia/Tokyo');
ini_set('soap.wsdl_cache_enabled', 0);

function_exists('getallheaders') && error_log(var_export(getallheaders(), true));

if (@$_GET['auth'] === 'basic') {
    if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] !== 'hoge') {
        header('WWW-Authenticate: Basic realm="Test Realm"');
        header('HTTP/1.0 401 Unauthorized');
        exit();
    }
} elseif (@$_GET['auth'] === 'digest') {
    $realm = 'Restricted area';
    if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Digest realm="'.$realm.'",qop="auth",nonce="'.uniqid().'",opaque="'.md5($realm).'"');
        exit();
    }
    // through digest validation
}

$redirect = (int) @$_GET['redirect'];
if ($redirect > 0) {
    --$redirect;
    header("Location: http://localhost:8000/tests/server.php?redirect=$redirect");
    exit();
}

if (isset($_GET['503'])) {
    header('HTTP/1.1 503 Service Temporarily Unavailable');
    exit();
}

if (isset($_GET['400'])) {
    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: text/plain');
    echo 'bodybody';
    exit();
}

if (isset($_GET['300'])) {
    header('HTTP/1.1 300');
    exit();
}

$location = @$_GET['location'];
if ($location) {
    header("Location: $location");
    exit();
}

$usleep = (int) @$_GET['usleep'];
if ($usleep > 0) {
    usleep($usleep);
}

function test($x)
{
    return $x;
}

function testFault()
{
    throw new SoapFault('test', 'message');
}

$server = new \SoapServer(null, array('uri' => 'http://test-uri/'));
$server->addFunction('test');
$server->addFunction('testFault');
$server->handle();
