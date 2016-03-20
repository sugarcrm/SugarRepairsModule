<?php

//error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_WARNING);

error_reporting(E_ALL);
date_default_timezone_set('America/New_York');
ini_set('memory_limit','1024M');
set_time_limit(600);

$_REQUEST = array(
    'goto' => 'SilentInstall' ,
    'cli'=>'true',
    'instance_url' => 'http://localhost'
);

require('install.php');
