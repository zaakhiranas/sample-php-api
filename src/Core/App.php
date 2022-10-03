<?php

declare(strict_types=1);
require_once ROOT_PATH . '/vendor/autoload.php';
set_exception_handler('\Config\ErrorHandler::handleError');
set_exception_handler('\Config\ErrorHandler::handleException');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PATCH, POST, DELETE, OPTIONS');
header('Access-Control-Max-Age: 600');
header('Access-Control-Allow-Headers: Origin, Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type:application/json;charset=utf-8');
