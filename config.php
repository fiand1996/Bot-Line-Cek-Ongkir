<?php

debug_backtrace() || die ("<h2> Akses Ditolak! </ h2> Anda tidak dapat mengakses file ini secara langsung.");

session_start();

define("DB", [
    "DB_HOST_NAME" => "localhost",
    "DB_USER_NAME" => "root",
    "DB_PASSWORD"  => "root",
    "DB_DATABASE"  => "dbname"
]);

define("ONGKIR", [
    "RAJAONGKIR_KEY"       => "your_api_key",
    "RAJAONGKIR_AKUN_TIPE" => "starter",
    "LINE_ACCESS_TOKEN"   => "your_access_token",
    "CHANNEL_SECRET"   => "your_channel_secret",
]);

define("SITE_CONF", [
    "DEBUG"      => TRUE
]);

SITE_CONF['DEBUG'] == TRUE ? error_reporting(E_ALL) : error_reporting(0);


spl_autoload_register(function($class) {
    require_once 'classes/class.' . $class . '.php';
});

require_once('functions/all_function.php');
