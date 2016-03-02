<?php

global $sugar_config;

if ($sugar_config['dbconfig']['db_type'] == 'mysql') {
    $GLOBALS['db']->query("ALTER TABLE supp_sugarrepairs MODIFY value_before LONGTEXT");
    $GLOBALS['db']->query("ALTER TABLE supp_sugarrepairs MODIFY value_after LONGTEXT");
}
