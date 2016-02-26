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
$options = getopt("r:t:tp:");

if (isset($options['r'])) {
    if ($options['r'] == 'lang') {
        $sugarRepairs->repairLanguages($options);
    } else if ($options['r'] == 'team') {
        $sugarRepairs->repairTeamSets($options);
    } else {
        echo "Invalid repair type. Please refer to the documentation.\n";
    }
} else {
    echo "Please specify a repair type.\n";
}