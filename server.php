<?php

$_SERVER['SERVER_ADDR']='localhost';
$_SERVER['REQUEST_SCHEME']='http';
if(PHP_SAPI ==='cli'){
	$bind=$_SERVER['SERVER_ADDR'].':83';
	echo 'server listen on: '.$bind;

	`explorer.exe "$_SERVER[REQUEST_SCHEME]://$bind/"`;
	`php -S $bind server.php`;
	return 0;
}

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);



// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a ManaPHP
// application without having installed a "real" web server software here.
if ($uri !== '/' && file_exists(__DIR__.'/Public'.$uri)) {
    return false;
}
$_GET['_url']=$uri;
require_once __DIR__.'/Public/index.php';
