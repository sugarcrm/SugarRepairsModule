<?php
// Copyright 2016 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
$module_name = 'supp_SugarRepairs';
$_object_name = 'supp_sugarrepairs';
$viewdefs[$module_name]['QuickCreate'] = array(
    'templateMeta' => array('maxColumns' => '2',
        'widths' => array(
            array('label' => '10', 'field' => '30'),
            array('label' => '10', 'field' => '30')
        ),
    ),


    'panels' => array(
        'default' =>
            array(

                array(

                    array(
                        'name' => $_object_name . '_number',
                        'type' => 'readonly',
                    ),
                    'assigned_user_name',
                ),

                array(
                    'priority',
                    array('name' => 'team_name', 'displayParams' => array('display' => true)),
                ),

                array(
                    'status',
                    'resolution',
                ),

                array(
                    array('name' => 'name', 'displayParams' => array('size' => 60)),
                ),

                array(
                    'description',
                ),
            ),

    ),

);
?>
