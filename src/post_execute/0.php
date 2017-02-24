<?php
// Copyright 2017 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

//Adjust db columns to store more data
if ($GLOBALS['sugar_config']['dbconfig']['db_type'] == 'mysql') {
    $GLOBALS['db']->query("ALTER TABLE supp_sugarrepairs MODIFY value_before LONGTEXT");
    $GLOBALS['db']->query("ALTER TABLE supp_sugarrepairs MODIFY value_after LONGTEXT");
}

//Hack for installation to 6.7
if (version_compare($GLOBALS['sugar_version'], '6.8', '<') && version_compare($GLOBALS['sugar_version'], '6.7', '>=')) {
    $files = array(
        'custom/application/Ext/Include/modules.ext.php',
        sugar_cached('file_map.php')
    );

    foreach ($files as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
}


