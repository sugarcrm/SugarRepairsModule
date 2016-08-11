<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
// Copyright 2016 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
/*********************************************************************************
 * $Id$
 * Description:  Defines the English language pack for the base application.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

require_once('include/Dashlets/DashletGeneric.php');
require_once('modules/supp_SugarRepairs/supp_SugarRepairs.php');

class supp_SugarRepairsDashlet extends DashletGeneric { 
    function supp_SugarRepairsDashlet($id, $def = null) {
		global $current_user, $app_strings;
		require('modules/supp_SugarRepairs/metadata/dashletviewdefs.php');

        parent::DashletGeneric($id, $def);

        if(empty($def['title'])) $this->title = translate('LBL_HOMEPAGE_TITLE', 'supp_SugarRepairs');

        $this->searchFields = $dashletData['supp_SugarRepairsDashlet']['searchFields'];
        $this->columns = $dashletData['supp_SugarRepairsDashlet']['columns'];

        $this->seedBean = new supp_SugarRepairs();        
    }
}
