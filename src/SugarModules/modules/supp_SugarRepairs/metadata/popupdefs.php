<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
// Copyright 2016 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
$module_name = 'supp_SugarRepairs';
$object_name = 'supp_SugarRepairs';
$_module_name = 'supp_sugarrepairs';
$_object_name = 'supp_sugarrepairs';
$popupMeta = array('moduleMain' => $module_name,
    'varName' => $object_name,
    'orderBy' => $_module_name . '.name',
    'whereClauses' =>
        array('name' => $_module_name . '.name',
            $_object_name . '_number' => $_module_name . '.' . $_object_name . '_number'),
    'searchInputs' => array($_module_name . '_number', 'name', 'priority', 'status'),

);
