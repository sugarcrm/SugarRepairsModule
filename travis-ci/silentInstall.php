<?php

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_WARNING);

$_REQUEST = array(
    'goto' => 'SilentInstall' ,
    'cli'=>'true',
    'instance_url' => 'http://localhost'
);

require('install.php');
