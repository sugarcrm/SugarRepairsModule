<?php

//error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_WARNING);
error_reporting(E_ALL);
$_REQUEST = array(
    'goto' => 'SilentInstall' ,
    'cli'=>'true',
    'instance_url' => 'http://localhost'
);

require('install.php');
