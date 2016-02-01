<?php

/*
 * Your installation or use of this SugarCRM file is subject to the applicable
 * terms available at
 * http://support.sugarcrm.com/06_Customer_Center/10_Master_Subscription_Agreements/.
 * If you do not agree to all of the applicable terms or do not have the
 * authority to bind the entity as an authorized representative, then do not
 * install or use this SugarCRM file.
 *
 * Copyright (C) SugarCRM Inc. All rights reserved.
 */

$dictionary['supp_SugarRepairs'] = array(
    'table' => 'supp_sugarrepairs',
    'audited' => true,
    'activity_enabled' => false,
    'duplicate_merge' => true,
    'fields' => array (
        'cycle_id' =>
            array (
                'required' => false,
                'name' => 'cycle_id',
                'vname' => 'LBL_CYCLE_ID',
                'type' => 'varchar',
                'massupdate' => false,
                'default' => '',
                'no_default' => false,
                'comments' => '',
                'help' => '',
                'importable' => 'true',
                'duplicate_merge' => 'enabled',
                'duplicate_merge_dom_value' => '1',
                'audited' => false,
                'reportable' => true,
                'unified_search' => false,
                'merge_filter' => 'disabled',
                'full_text_search' =>
                    array (
                        'boost' => '0',
                        'enabled' => false,
                    ),
                'calculated' => false,
                'len' => '36',
                'size' => '20',
            ),
        'target_type' =>
            array (
                'required' => false,
                'name' => 'target_type',
                'vname' => 'LBL_TARGET_TYPE',
                'type' => 'enum',
                'massupdate' => true,
                'default' => 'File',
                'no_default' => false,
                'comments' => '',
                'help' => '',
                'importable' => 'true',
                'duplicate_merge' => 'enabled',
                'duplicate_merge_dom_value' => '1',
                'audited' => false,
                'reportable' => true,
                'unified_search' => false,
                'merge_filter' => 'disabled',
                'calculated' => false,
                'len' => 100,
                'size' => '20',
                'options' => 'target_type_list',
                'dependency' => false,
            ),
        'target' =>
            array (
                'required' => false,
                'name' => 'target',
                'vname' => 'LBL_TARGET',
                'type' => 'varchar',
                'massupdate' => false,
                'default' => '',
                'no_default' => false,
                'comments' => '',
                'help' => '',
                'importable' => 'true',
                'duplicate_merge' => 'enabled',
                'duplicate_merge_dom_value' => '1',
                'audited' => false,
                'reportable' => true,
                'unified_search' => false,
                'merge_filter' => 'disabled',
                'full_text_search' =>
                    array (
                        'boost' => '0',
                        'enabled' => false,
                    ),
                'calculated' => false,
                'len' => '255',
                'size' => '20',
            ),
        'value_before' =>
            array (
                'required' => false,
                'name' => 'value_before',
                'vname' => 'LBL_VALUE_BEFORE',
                'type' => 'text',
                'massupdate' => false,
                'default' => '',
                'no_default' => false,
                'comments' => '',
                'help' => '',
                'importable' => 'true',
                'duplicate_merge' => 'enabled',
                'duplicate_merge_dom_value' => '1',
                'audited' => false,
                'reportable' => true,
                'unified_search' => false,
                'merge_filter' => 'disabled',
                'full_text_search' =>
                    array (
                        'boost' => '0',
                        'enabled' => false,
                    ),
                'calculated' => false,
                'size' => '20',
                'studio' => 'visible',
                'rows' => '4',
                'cols' => '20',
            ),
        'type' =>
            array (
                'name' => 'type',
                'vname' => 'LBL_TYPE',
                'type' => 'enum',
                'options' => 'supp_sugarrepairs_type_dom',
                'len' => 100,
                'comment' => 'The type of issue (ex: issue, feature)',
                'merge_filter' => 'disabled',
                'sortable' => true,
                'duplicate_on_record_copy' => 'always',
                'required' => false,
                'massupdate' => true,
                'default' => '',
                'no_default' => false,
                'comments' => 'The repair type',
                'help' => '',
                'importable' => 'true',
                'duplicate_merge' => 'enabled',
                'duplicate_merge_dom_value' => '1',
                'audited' => false,
                'reportable' => true,
                'unified_search' => false,
                'calculated' => false,
                'size' => '20',
                'dependency' => false,
            ),
        'value_after' =>
            array (
                'required' => false,
                'name' => 'value_after',
                'vname' => 'LBL_VALUE_AFTER',
                'type' => 'text',
                'massupdate' => false,
                'default' => '',
                'no_default' => false,
                'comments' => '',
                'help' => '',
                'importable' => 'true',
                'duplicate_merge' => 'enabled',
                'duplicate_merge_dom_value' => '1',
                'audited' => false,
                'reportable' => true,
                'unified_search' => false,
                'merge_filter' => 'disabled',
                'full_text_search' =>
                    array (
                        'boost' => '0',
                        'enabled' => false,
                    ),
                'calculated' => false,
                'size' => '20',
                'studio' => 'visible',
                'rows' => '4',
                'cols' => '20',
            ),
    ),
    'acls' =>
        array (
            'SugarACLAdministration' => true,
        ),
    'relationships' => array(),
    'optimistic_locking' => true,
    'unified_search' => true,
);

if (!class_exists('VardefManager')){
  require_once 'include/SugarObjects/VardefManager.php';
}
VardefManager::createVardef('supp_SugarRepairs','supp_SugarRepairs', array('basic','assignable','issue'));