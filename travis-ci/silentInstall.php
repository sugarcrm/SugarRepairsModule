<?php

error_reporting(0);
$_REQUEST = array('goto' => 'SilentInstall' , 'cli'=>'true');
require('install.php');
