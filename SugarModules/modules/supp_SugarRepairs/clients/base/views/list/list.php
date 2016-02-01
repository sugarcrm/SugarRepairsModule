<?php
$module_name = 'supp_SugarRepairs';
$_module_name = 'supp_sugarrepairs';
$viewdefs[$module_name] = 
array (
  'base' => 
  array (
    'view' => 
    array (
      'list' => 
      array (
        'panels' => 
        array (
          0 => 
          array (
            'label' => 'LBL_PANEL_DEFAULT',
            'fields' => 
            array (
              0 => 
              array (
                'name' => 'name',
                'label' => 'LBL_SUBJECT',
                'link' => true,
                'default' => true,
                'enabled' => true,
              ),
              1 => 
              array (
                'name' => 'cycle_id',
                'label' => 'LBL_CYCLE_ID',
                'enabled' => true,
                'default' => true,
              ),
              2 => 
              array (
                'name' => 'type',
                'label' => 'LBL_TYPE',
                'enabled' => true,
                'default' => true,
              ),
              3 => 
              array (
                'name' => 'target_type',
                'label' => 'LBL_TARGET_TYPE',
                'enabled' => true,
                'default' => true,
              ),
              4 => 
              array (
                'name' => 'status',
                'label' => 'LBL_STATUS',
                'default' => true,
                'enabled' => true,
              ),
              5 => 
              array (
                'name' => 'date_modified',
                'enabled' => true,
                'default' => true,
              ),
              6 => 
              array (
                'name' => 'date_entered',
                'enabled' => true,
                'default' => true,
              ),
            ),
          ),
        ),
      ),
    ),
  ),
);
