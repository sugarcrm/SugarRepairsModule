<?php
// Copyright 2016 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
$manifest = array(
    'built_in_version' => '7.6.1.0',
    'acceptable_sugar_versions' => array(
        'regex_matches' => array(
            1 => "6\.5\.*.*",
            2 => "6\.7\.*.*",
            3 => "7\.5\.*.*",
            4 => "7\.6\.*.*",
            5 => "7\.7\.*.*",
            6 => "7\.8\.*.*",
        ),
    ),
    'acceptable_sugar_flavors' => array(
        1 => 'PRO',
        2 => 'CORP',
        3 => 'ENT',
        4 => 'ULT',
    ),
    'readme' => '',
    'key' => 'supp',
    'author' => 'Sugar Support',
    'description' => 'Sugar Repairs Module',
    'icon' => '',
    'is_uninstallable' => true,
    'name' => 'Sugar Repairs',
    'published_date' => '2017-02-14 19:27:01',
    'type' => 'module',
    'version' => '1.8',
    'remove_tables' => 'prompt',
);

$installdefs = array(
    'id' => 'Repairs',
    'beans' => array(
        0 => array(
            'module' => 'supp_SugarRepairs',
            'class' => 'supp_SugarRepairs',
            'path' => 'modules/supp_SugarRepairs/supp_SugarRepairs.php',
            'tab' => true,
        ),
    ),
    'layoutdefs' => array(),
    'relationships' => array(),
    'image_dir' => '<basepath>/icons',
    'copy' => array(
        0 => array(
            'from' => '<basepath>/SugarModules/modules/supp_SugarRepairs',
            'to' => 'modules/supp_SugarRepairs',
        ),
        1 => array(
            'from' => '<basepath>/copy/custom/tests/modules/supp_SugarRepairs',
            'to' => 'custom/tests/modules/supp_SugarRepairs',
        ),
    ),
    'language' => array(
        0 => array(
            'from' => '<basepath>/SugarModules/language/application/en_us.lang.php',
            'to_module' => 'application',
            'language' => 'en_us',
        ),
    ),
    'post_execute' => array(
        0 => '<basepath>/post_execute/0.php',
    ),
);

?>
