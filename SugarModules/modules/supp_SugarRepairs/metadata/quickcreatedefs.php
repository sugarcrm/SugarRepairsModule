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
$module_name = 'supp_SugarRepairs';
$_object_name = 'supp_sugarrepairs';
$viewdefs[$module_name]['QuickCreate'] = array(
    'templateMeta' => array('maxColumns' => '2', 
                            'widths' => array(
                                            array('label' => '10', 'field' => '30'), 
                                            array('label' => '10', 'field' => '30')
                                            ),                                                                                                                                    
                                            ),
                                            
                                            
 'panels' =>array (
  'default' => 
  array (
    
    array (
      
      array (
        'name' => $_object_name . '_number',
        'type' => 'readonly',
      ),
      'assigned_user_name',
    ),
    
    array (
      'priority',
      array('name'=>'team_name', 'displayParams'=>array('display'=>true)),
    ),
    
    array (
      'status',
      'resolution',
    ),

    array (
      array('name'=>'name', 'displayParams'=>array('size'=>60)),
    ),
    
    array (
      'description',
    ),
  ),
                                                    
),
                        
);
?>