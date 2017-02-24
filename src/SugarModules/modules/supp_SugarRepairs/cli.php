<?php
// Copyright 2017 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

/**
 * Command line utility for the sugar repair module
 */

define('sugarEntry', true);

if (defined('SUGAR_SHADOW_TEMPLATEPATH')) {
    chdir(SUGAR_SHADOW_TEMPLATEPATH);
} else {
    chdir('../../');
}

require_once('include/entryPoint.php');

global $current_user;

if (empty($current_user) || empty($current_user->id)) {
    $current_user = new User();
    $current_user->getSystemUser();
}

$sugarRepairs = BeanFactory::newBean('supp_SugarRepairs');

$longopts = array("repair:", "test::");

if (!isset($options)) {
    $options = getopt('', $longopts);
}

if (isset($options['repair'])) {
    if ($options['repair'] == 'lang') {
        $sugarRepairs->repairLanguages($options);
    } else if ($options['repair'] == 'team') {
        $sugarRepairs->repairTeamSets($options);
    } else if ($options['repair'] == 'workflow') {
        $sugarRepairs->repairWorkflows($options);
    } else if ($options['repair'] == 'report') {
        $sugarRepairs->repairReports($options);
    } else if ($options['repair'] == 'vardef') {
        $sugarRepairs->repairVardefs($options);
    } else if ($options['repair'] == 'emailAddresses') {
        $sugarRepairs->repairEmailAddresses($options);
    } else if ($options['repair'] == 'processAuthor') {
        $sugarRepairs->repairProcessAuthor($options);
    } else if ($options['repair'] == 'forecasts') {
        $longopts[] = "timeperiod_id:";
        $options = getopt('', $longopts);
        if (isset($options['timeperiod_id'])) {
            $sugarRepairs->repairForecasts($options);
        } else {
            echo "Please specify the timeperiod_id parameter.\n";
        }
    } else if ($options['repair'] == 'metadata') {
        $sugarRepairs->repairMetadata($options);
    } else {
        echo "Invalid repair type. Please refer to the documentation.\n";
    }
} else {
    echo "Please specify a repair type.\n";
}
