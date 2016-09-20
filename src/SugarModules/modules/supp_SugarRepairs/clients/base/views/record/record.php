<?php
$module_name = 'supp_SugarRepairs';
$_module_name = 'supp_sugarrepairs';
$viewdefs[$module_name] = 
array (
  'base' => 
  array (
    'view' => 
    array (
      'record' => 
      array (
        'panels' => 
        array (
          0 => 
          array (
            'name' => 'panel_header',
            'label' => 'LBL_RECORD_HEADER',
            'header' => true,
            'fields' => 
            array (
              0 => 
              array (
                'name' => 'picture',
                'type' => 'avatar',
                'width' => 42,
                'height' => 42,
                'dismiss_label' => true,
                'readonly' => true,
              ),
              1 => 'name',
              2 => 
              array (
                'name' => 'favorite',
                'label' => 'LBL_FAVORITE',
                'type' => 'favorite',
                'readonly' => true,
                'dismiss_label' => true,
              ),
              3 => 
              array (
                'name' => 'follow',
                'label' => 'LBL_FOLLOW',
                'type' => 'follow',
                'readonly' => true,
                'dismiss_label' => true,
              ),
            ),
          ),
          1 => 
          array (
            'name' => 'panel_body',
            'label' => 'LBL_RECORD_BODY',
            'columns' => 2,
            'labelsOnTop' => true,
            'placeholders' => true,
            'newTab' => false,
            'panelDefault' => 'expanded',
            'fields' => 
            array (
              0 => 'status',
              1 => 
              array (
                'name' => 'priority',
                'comment' => 'An indication of the priorty of the issue',
                'label' => 'LBL_PRIORITY',
              ),
              2 => 
              array (
                'name' => 'type',
              ),
              3 => 
              array (
                'name' => 'target_type',
                'label' => 'LBL_TARGET_TYPE',
              ),
              4 => 
              array (
                'name' => 'cycle_id',
                'label' => 'LBL_CYCLE_ID',
              ),
              5 => 
              array (
                'name' => 'target',
                'label' => 'LBL_TARGET',
              ),
              6 => 
              array (
                'name' => 'description',
                'span' => 12,
              ),
              7 => 
              array (
                'name' => 'value_before',
                'studio' => 'visible',
                'label' => 'LBL_VALUE_BEFORE',
                'span' => 12,
              ),
              8 => 
              array (
                'name' => 'value_after',
                'studio' => 'visible',
                'label' => 'LBL_VALUE_AFTER',
                'span' => 12,
              ),
              9 => 
              array (
                'name' => 'date_entered_by',
                'readonly' => true,
                'inline' => true,
                'type' => 'fieldset',
                'label' => 'LBL_DATE_ENTERED',
                'fields' => 
                array (
                  0 => 
                  array (
                    'name' => 'date_entered',
                  ),
                  1 => 
                  array (
                    'type' => 'label',
                    'default_value' => 'LBL_BY',
                  ),
                  2 => 
                  array (
                    'name' => 'created_by_name',
                  ),
                ),
              ),
              10 => 
              array (
                'name' => 'date_modified_by',
                'readonly' => true,
                'inline' => true,
                'type' => 'fieldset',
                'label' => 'LBL_DATE_MODIFIED',
                'fields' => 
                array (
                  0 => 
                  array (
                    'name' => 'date_modified',
                  ),
                  1 => 
                  array (
                    'type' => 'label',
                    'default_value' => 'LBL_BY',
                  ),
                  2 => 
                  array (
                    'name' => 'modified_by_name',
                  ),
                ),
              ),
            ),
          ),
        ),
        'templateMeta' => 
        array (
          'useTabs' => false,
        ),
      ),
    ),
  ),
);
