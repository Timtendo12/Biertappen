<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GameController extends Controller
{
    function start() {
        //return all request info
        return array([
            'laravel_request' => request(),
            'php_request' => $_REQUEST,
            'php_get' => $_GET,
            'php_post' => $_POST,
            'php_cookie' => $_COOKIE,
            'php_server' => $_SERVER,
            'php_env' => $_ENV,
            'php_files' => $_FILES
        ]);
    }
}
