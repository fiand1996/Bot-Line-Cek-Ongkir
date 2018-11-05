<?php

require_once('config.php');

$content = file_get_contents("php://input");
$update = json_decode($content);

$line = new Line($update);

$line->cekuser();
$line->check_command();
