<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
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
$module_name = 'supp_SugarRepairs';
$object_name = 'supp_SugarRepairs';
$_module_name = 'supp_sugarrepairs';
$_object_name = 'supp_sugarrepairs';
$popupMeta = array('moduleMain' => $module_name,
						'varName' => $object_name,
						'orderBy' => $_module_name . '.name',
						'whereClauses' => 
							array('name' => $_module_name. '.name', 
									$_object_name . '_number' => $_module_name. '.'. $_object_name.'_number'),
						    'searchInputs'=> array($_module_name . '_number', 'name', 'priority','status'),
							
						);
