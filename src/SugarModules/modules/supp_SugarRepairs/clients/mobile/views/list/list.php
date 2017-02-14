<?php
$module_name = 'supp_SugarRepairs';
$_module_name = 'supp_sugarrepairs';
$viewdefs[$module_name] =
    array(
        'mobile' =>
            array(
                'view' =>
                    array(
                        'list' =>
                            array(
                                'panels' =>
                                    array(
                                        0 =>
                                            array(
                                                'label' => 'LBL_PANEL_DEFAULT',
                                                'fields' =>
                                                    array(
                                                        0 =>
                                                            array(
                                                                'name' => 'supp_sugarrepairs_number',
                                                                'width' => '5',
                                                                'label' => 'LBL_NUMBER',
                                                                'link' => true,
                                                                'default' => true,
                                                                'enabled' => true,
                                                            ),
                                                        1 =>
                                                            array(
                                                                'name' => 'name',
                                                                'width' => '32',
                                                                'label' => 'LBL_SUBJECT',
                                                                'link' => true,
                                                                'default' => true,
                                                                'enabled' => true,
                                                            ),
                                                        2 =>
                                                            array(
                                                                'name' => 'status',
                                                                'width' => '10',
                                                                'label' => 'LBL_STATUS',
                                                                'default' => true,
                                                                'enabled' => true,
                                                            ),
                                                        3 =>
                                                            array(
                                                                'name' => 'priority',
                                                                'width' => '10',
                                                                'label' => 'LBL_PRIORITY',
                                                                'default' => true,
                                                                'enabled' => true,
                                                            ),
                                                        4 =>
                                                            array(
                                                                'name' => 'resolution',
                                                                'width' => '10',
                                                                'label' => 'LBL_RESOLUTION',
                                                                'default' => true,
                                                                'enabled' => true,
                                                            ),
                                                        5 =>
                                                            array(
                                                                'name' => 'assigned_user_name',
                                                                'width' => '9',
                                                                'label' => 'LBL_ASSIGNED_USER_NAME',
                                                                'default' => true,
                                                                'enabled' => true,
                                                            ),
                                                    ),
                                            ),
                                    ),
                            ),
                    ),
            ),
    );
