<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

// $Id: default.php 15992 2006-08-16 01:51:22Z majed $
$module_name = 'supp_SugarRepairs';
$subpanel_layout = array(
	'top_buttons' => array(
		array('widget_class' => 'SubPanelTopCreateButton'),
		array('widget_class' => 'SubPanelTopSelectButton', 'popup_module' => $module_name),
	),

	'where' => '',

	'list_fields' => array(
		'name'=>array(
	 		'vname' => 'LBL_SUBJECT',
			'widget_class' => 'SubPanelDetailViewLink',
	 		'width' => '45%',
		),
		'status'=>array(
	 		'vname' => 'LBL_STATUS',
	 		'width' => '15%',
		),
		'resolution'=>array(
	 		'vname' => 'LBL_RESOLUTION',
	 		'width' => '15%',
		),
		'priority'=>array(
	 		'vname' => 'LBL_PRIORITY',
	 		'width' => '11%',
		),
		'assigned_user_name' => array (
			'name' => 'assigned_user_name',
			'vname' => 'LBL_ASSIGNED_TO_NAME',
			'widget_class' => 'SubPanelDetailViewLink',
		 	'target_record_key' => 'assigned_user_id',
			'target_module' => 'Employees',
		),
		'edit_button'=>array(
            'vname' => 'LBL_EDIT_BUTTON',
			'widget_class' => 'SubPanelEditButton',
		 	'module' => $module_name,
	 		'width' => '4%',
		),
		'remove_button'=>array(
            'vname' => 'LBL_REMOVE',
			'widget_class' => 'SubPanelRemoveButton',
		 	'module' => $module_name,
			'width' => '5%',
		),
	),
);

?>
