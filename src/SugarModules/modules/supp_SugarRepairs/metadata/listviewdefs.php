<?php
// Copyright 2017 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

$module_name = 'supp_SugarRepairs';
$OBJECT_NAME = 'SUPP_SUGARREPAIRS';
$listViewDefs [$module_name] =
    array(
        'NAME' =>
            array(
                'width' => '32%',
                'label' => 'LBL_SUBJECT',
                'default' => true,
                'link' => true,
            ),
        'TYPE' =>
            array(
                'type' => 'enum',
                'sortable' => true,
                'default' => true,
                'label' => 'LBL_TYPE',
                'width' => '10%',
            ),
        'STATUS' =>
            array(
                'width' => '10%',
                'label' => 'LBL_STATUS',
                'default' => true,
            ),
        'PRIORITY' =>
            array(
                'width' => '10%',
                'label' => 'LBL_PRIORITY',
                'default' => true,
            ),
        'CYCLE_ID' =>
            array(
                'type' => 'varchar',
                'default' => true,
                'label' => 'LBL_CYCLE_ID',
                'width' => '10%',
            ),
        'TARGET_TYPE' =>
            array(
                'type' => 'enum',
                'default' => true,
                'label' => 'LBL_TARGET_TYPE',
                'width' => '10%',
            ),
        'TARGET' =>
            array(
                'type' => 'varchar',
                'default' => true,
                'label' => 'LBL_TARGET',
                'width' => '10%',
            ),
        'DATE_ENTERED' =>
            array(
                'type' => 'datetime',
                'label' => 'LBL_DATE_ENTERED',
                'width' => '10%',
                'default' => true,
            ),
    );
?>
