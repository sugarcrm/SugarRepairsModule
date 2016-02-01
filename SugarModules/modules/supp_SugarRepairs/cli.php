<?php

/**
 * Command line utility for the sugar repair module
 */

define('sugarEntry', true);
chdir('../../');
require_once('include/entryPoint.php');

global $current_user;

if (empty($current_user) || empty($current_user->id)) {
    $current_user = new User();
    $current_user->getSystemUser();
}

$sugarRepairs = BeanFactory::newBean('supp_SugarRepairs');

$sugarRepairs->repairLanguages();
$sugarRepairs->repairTeamSets();