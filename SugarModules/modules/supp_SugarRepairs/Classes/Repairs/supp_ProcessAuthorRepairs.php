<?php

require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_Repairs.php');

class supp_ProcessAuthorRepairs extends supp_Repairs
{
    protected $loggerTitle = "ProcessAuthor";

    public $foundIssues = array();

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Sets a process author definition event criteria
     * @param string $eventId pmse_bpm_event_definition record id
     * @param string $new_evn_criteria new json and html encoded criteria data
     * @return boolean update query result
     */
    public function setEventDefinition($eventId, $new_evn_criteria)
    {
        $results = false;
        $sql = "
            UPDATE pmse_bpm_event_definition
            SET evn_criteria = '$new_evn_criteria'
            WHERE id = '$eventId'
        ";
        if (!$this->isTesting) {
            $this->logChange("-> Updating PA Event Criteria '{$eventId}' to: '{$new_evn_criteria}'");
            $results = $this->updateQuery($sql);
        } else {
            $this->logChange("-> Will update PA Definition Event Criteria '{$eventId}' to: '{$new_evn_criteria}'");
        }

        return $results;
    }

    /**
     * Sets a process author definition action fields
     * @param string $actionId pmse_bpm_activity_definition record id
     * @param string $new_action_fields new json and html encoded criteria data
     * @return boolean update query result
     */
    public function setActionDefinition($actionId, $new_action_fields)
    {
        $results = false;
        $sql = "
            UPDATE pmse_bpm_activity_definition
            SET act_fields = '$new_action_fields'
            WHERE id = '$actionId'
        ";
        if (!$this->isTesting) {
            $this->logChange("-> Updating PA Action Definition '{$actionId}' to: '{$new_action_fields}'");
            $results = $this->updateQuery($sql);
        } else {
            $this->logChange("-> Will update PA Action Definition '{$actionId}' to: '{$new_action_fields}'");
        }

        return $results;
    }

    /**
     * Sets a process author business rule definition
     * @param string $ruleId pmse_business_rules record id
     * @param string $new_rst_source_Definition new json and html encoded definition data
     * @return boolean update query result
     */
    public function setBusinessRuleDefinition($ruleId, $new_rst_source_Definition)
    {
        $results = false;
        $sql = "
            UPDATE pmse_business_rules
            SET rst_source_Definition = '$new_rst_source_Definition'
            WHERE id = '$ruleId'
        ";
        if (!$this->isTesting) {
            $this->logChange("-> Updating PA Business Rule '{$ruleId}' to: '{$new_rst_source_Definition}'");
            $results = $this->updateQuery($sql);
        } else {
            $this->logChange("-> Will update PA Business Rule '{$ruleId}' to: '{$new_rst_source_Definition}'");
        }

        return $results;
    }

    /**
     * Validates a Process Author field still exists
     * @param string $base_module Module to check field on
     * @param string $field Name of field
     * @param array $paDef Process Author Definition info (id, name)
     * @return mixed $type false if field doesnt exist; string of field type
     */
    public function validatePAFieldExists($base_module, $field, $paDef)
    {
        // Check if field exists
        $type = $this->getFieldType($base_module, $field);
        $this->log("-> Checking $base_module :: $field");
        if ($type == false) {
            $this->logAction("-> PA Definition '{$paDef['name']}' ({$paDef['id']}) is utilizing a deleted or missing field on {$base_module} / {$field}. You should review this definition.");
            $this->foundIssues[$paDef['id']] = "Deleted or Missing field: {$base_module} / {$field}";
            $this->disablePADefinition($paDef['id']);
            return false;
        }
        return $type;
    }

    /**
     * Validates a Process Author dropdown field has a valid option list
     * @param string $type Field Type
     * @param string $base_module Module to check field on
     * @param string $field Name of field
     * @param array $paDef Process Author Definition info (id, name)
     * @return mixed $listKeys false if list doesnt exist or field not a dropdown; array of listkeys
     */
    public function validatePAOptionListExists($type, $base_module, $field, $paDef)
    {
        // Validate dropdown or multiselect option list exsists
        if (in_array($type, array('enum', 'multienum'))) {
            $listKeys = $this->getFieldOptionKeys($base_module, $field);

            if ($listKeys == false) {
                $this->logAction("-> PA Definition '{$paDef['name']}' ({$paDef['id']}) has a field ({$base_module} / {$field}) with a deleted or missing dropdown list. You should review this definition or field.");
                $this->foundIssues[$paDef['id']] = "Deleted or Missing dropdown list: {$base_module} / {$field}";
                $this->disablePADefinition($paDef['id']);
                return false;
            }
            return $listKeys;
        }
        return false;
    }

    /**
     * Validates a Process Author dropdown field has a valid option list
     * @param string $selectedKey Dropdown value selected in definition
     * @param array $listKeys Dropdown options available
     * @param string $fieldString JSON string of the definition
     * @param array $paDef Process Author Definition info (id, name)
     * @return mixed $new_field_string false if selected value doesnt exist in list and cant be fixed; string of new JSON definition
     */
    public function validatePASelectedKey($selectedKey, $listKeys, $fieldString, $paDef)
    {
        // Validate selected key is in list
        if (!in_array($selectedKey, $listKeys)) {
            // Selected key is not in the list, try to fix
            $testKey = $this->getValidLanguageKeyName($selectedKey);
            if ($testKey === false || !in_array($testKey, $listKeys)) {
                $this->logAction("-> The converted key for '{$selectedKey}' is missing in the Process Author Definition '{$paDef['name']}' ({$paDef['id']}). This will need to be manually corrected.");
                $this->foundIssues[$paDef['id']] = "Selected Key does not exist in dropdown list: {$selectedKey}";
                $this->disablePADefinition($paDef['id']);
                return false;
            }

            //try to fix the key if it was updated in the lang repair script
            if ($testKey !== $selectedKey) {
                $new_field_string = str_replace($selectedKey, $testKey, $fieldString);
                return $new_field_string;
            }
        }
        return true;
    }

    /**
     * Repairs or disables Process Author Definitions 
     * with various issues in the criteria
     */
    public function repairEventCriteria()
    {
        $this->foundIssues = array();
        $sql = "
            SELECT 
                p.id as prj_id, p.name, p.prj_module, ed.id as event_id, ed.evn_criteria
            FROM pmse_bpm_event_definition ed
                JOIN pmse_project p
                    ON
                        p.id = ed.prj_id AND
                        p.prj_status = 'ACTIVE' AND
                        p.deleted = '0'
                JOIN pmse_bpmn_flow f
                    ON
                        f.flo_element_origin = ed.id AND
                        f.prj_id = ed.prj_id AND
                        f.deleted = '0'
            WHERE ed.deleted = '0' 
                AND ed.evn_status = 'ACTIVE'
                AND ed.evn_criteria <> '' 
                AND ed.evn_criteria IS NOT NULL
        ";
        $result = $GLOBALS['db']->query($sql);
        while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
            $this->log("Processing PA Definition '{$row['name']}' ({$row['prj_id']}) Action ({$row['event_id']})...");

            $paDef = array(
                'id' => $row['prj_id'],
                'name' => $row['name']
                );

            $new_evn_criteria = $row['evn_criteria'];
            $eventArray = json_decode(html_entity_decode($row['evn_criteria']));

            if ($eventArray==false || $eventArray == null) {
                $this->logAction("-> PA Definition '{$row['name']}' Event Criteria failed to process::".$this->getJSONLastError());
                continue;
            }

            foreach ($eventArray as $event) {
                // skip anything thats not a module criteria like "LOGIC" and "USER_ROLE"
                if ($event->expType != "MODULE") {
                    continue;
                }

                $base_module = $event->expModule;
                $field = $event->expField;
                
                // process author expModule will sometimes be the module we are in
                // and sometimes will be a related module
                if ($row['prj_module'] != $base_module) {
                    $base_module = $this->getRelatedModuleName($row['prj_module'], $base_module);
                }

                // Check if field exists
                $type = $this->validatePAFieldExists($base_module, $field, $paDef);
                if ($type === false) continue;

                // Validate dropdown or multiselect option list exsists
                $listKeys = $this->validatePAOptionListExists($type, $base_module, $field, $paDef);
                if ($listKeys === false) continue;

                $selectedKey = $event->expValue;
                
                // Validate selected key is in list
                $new_evn_criteria = $this->validatePASelectedKey($selectedKey, $listKeys, $new_evn_criteria, $paDef);

                if ($new_evn_criteria !== $row['evn_criteria']) {
                    $results = $this->setEventDefinition($row['event_id'], $new_evn_criteria);
                    if (!$results == true) {
                        $this->logAction("-> Failed to update Event Criteria ID: {$row['event_id']} for PA Definition: {$row['prj_id']}. This will have to be fixed manaully.");
                    }
                }
            }
        }
        $foundIssuesCount = count($this->foundIssues);
        $this->log("Found {$foundIssuesCount} bad PA Definition criteria.");
    }

    /**
     * Repairs or disables Process Author Definitions 
     * with various issues in the activities
     */
    public function repairActivities()
    {
        $this->foundIssues = array();
        $sql = "
            SELECT 
                p.id as prj_id, p.name, p.prj_module, a.act_script_type, a.act_task_type, 
                ad.id as activity_id, ad.act_field_module, ad.act_fields, ad.act_required_fields
            FROM pmse_bpm_activity_definition ad
                JOIN pmse_bpmn_activity a
                    ON
                        a.id = ad.id AND
                        a.deleted = '0'
                JOIN pmse_project p
                    ON
                        p.id = a.prj_id AND
                        p.prj_status = 'ACTIVE' AND
                        p.deleted = '0'
                JOIN pmse_bpmn_flow f
                    ON
                        f.flo_element_origin = a.id AND
                        f.prj_id = a.prj_id AND
                        f.deleted = '0'
            WHERE ad.deleted = '0' 
                AND (
                    (a.act_script_type IN ('CHANGE_FIELD','ADD_RELATED_RECORD') AND ad.act_fields <> '' AND ad.act_fields IS NOT NULL) OR
                    (a.act_task_type = 'USERTASK' AND ad.act_required_fields <> '' AND ad.act_required_fields IS NOT NULL)
                )
        ";
        $result = $GLOBALS['db']->query($sql);
        while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
            $this->log("Processing PA Definition '{$row['name']}' ({$row['prj_id']}) Action ({$row['activity_id']})...");

            $paDef = array(
                'id' => $row['prj_id'],
                'name' => $row['name']
                );

            // Action Required Fields are for Activity Forms, act_task_type = 'USERTASK'
            if($row['act_task_type'] == "USERTASK"){
                $action_required_fields = $new_action_required_fields = json_decode(html_entity_decode(base64_decode($row['act_required_fields'])));

                if ($action_required_fields==false || $action_required_fields == null) {
                    $this->logAction("-> PA Definition '{$row['name']}' Activity Required Fields failed to process::".$this->getJSONLastError());
                    continue;
                }

                foreach ($action_required_fields as $field) {

                    $base_module = $row['prj_module'];

                    // Check if field exists
                    $type = $this->validatePAFieldExists($base_module, $field, $paDef);
                    if ($type === false) continue;

                    // Validate dropdown or multiselect option list exsists
                    $listKeys = $this->validatePAOptionListExists($type, $base_module, $field, $paDef);
                    if ($listKeys === false) continue;
                }

            // Action Fields are for Actions, act_script_type = 'CHANGE_FIELD' or 'ADD_RELATED_RECORD'
            }elseif($row['act_script_type'] == "CHANGE_FIELD" || $row['act_script_type'] == "ADD_RELATED_RECORD"){
                $action_fields = $new_action_fields = $row['act_fields'];
                $actionsArray = json_decode(html_entity_decode($action_fields));

                if ($actionsArray==false || $actionsArray == null) {
                    $this->logAction("-> PA Definition '{$row['name']}' Action Fields failed to process::".$this->getJSONLastError());
                    continue;
                }

                foreach ($actionsArray as $action) {

                    $base_module = $row['act_field_module'];
                    $field = $action->field;
                    
                    // process author expModule will sometimes be the module we are in
                    // and sometimes will be a related module
                    if ($row['prj_module'] != $base_module) {
                        $base_module = $this->getRelatedModuleName($row['prj_module'], $base_module);
                    }

                    // Check if field exists
                    $type = $this->validatePAFieldExists($base_module, $field, $paDef);
                    if ($type === false) continue;

                    // Validate dropdown or multiselect option list exsists
                    $listKeys = $this->validatePAOptionListExists($type, $base_module, $field, $paDef);
                    if ($listKeys === false) continue;

                    $selectedKey = $action->value;

                    // Validate selected key is in list
                    $new_action_fields = $this->validatePASelectedKey($selectedKey, $listKeys, $new_action_fields, $paDef);

                    if ($new_action_fields !== $row['act_fields']) {
                        $results = $this->setActionDefinition($row['activity_id'], $new_action_fields);
                        if (!$results == true) {
                            $this->logAction("-> Failed to update Action Fields ID: {$row['activity_id']} for PA Definition: {$row['prj_id']}. This will have to be fixed manaully.");
                        }
                    }
                }
            // Unknown / unhadled action type
            }else{
                $this->logAction("-> PA Definition '{$row['name']}' has an action with an unknown type: '{$row['act_task_type']}/{$row['act_script_type']}'. Please review this definition manually.");
            }            
        }
        $foundIssuesCount = count($this->foundIssues);
        $this->log("Found {$foundIssuesCount} bad PA Definition Actions.");
    }

    /**
     * Repairs or disables Process Author Definitions 
     * with various issues in related business rules
     */
    public function repairBusinessRules()
    {
        $this->foundIssues = array();
        $sql = "
            SELECT 
                p.id as prj_id, p.name, p.prj_module, a.act_script_type, a.act_task_type, 
                ad.id as activity_id, ad.act_field_module, ad.act_fields,
                br.rst_source_Definition
            FROM pmse_bpm_activity_definition ad
                JOIN pmse_bpmn_activity a
                    ON
                        a.id = ad.id AND
                        a.deleted = '0'
                JOIN pmse_project p
                    ON
                        p.id = a.prj_id AND
                        p.prj_status = 'ACTIVE' AND
                        p.deleted = '0'
                JOIN pmse_business_rules br
                    ON
                        br.id = ad.act_fields AND
                        br.deleted = '0'
                JOIN pmse_bpmn_flow f
                    ON
                        f.flo_element_origin = a.id AND
                        f.prj_id = a.prj_id AND
                        f.deleted = '0'
            WHERE ad.deleted = '0' 
                AND a.act_script_type = 'BUSINESS_RULE'
                AND a.act_task_type = 'SCRIPTTASK'
        ";
        $result = $GLOBALS['db']->query($sql);
        while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
            $this->log("Processing PA Definition '{$row['name']}' ({$row['prj_id']}) Action ({$row['activity_id']})...");

            $paDef = array(
                'id' => $row['prj_id'],
                'name' => $row['name']
                );

            $new_rst_source_Definition = $row['rst_source_Definition'];
            $brDefinition = json_decode(html_entity_decode($new_rst_source_Definition));

            if ($brDefinition==false || $brDefinition == null) {
                $this->logAction("-> PA Definition '{$row['name']}' Business Rule Criteria failed to process::".$this->getJSONLastError());
                continue;
            }

            foreach($brDefinition->ruleset as $rule_record){

                // conditions
                foreach ($rule_record->conditions as $condition) {

                    $base_module = $condition->variable_module;
                    $field = $condition->variable_name;
                    
                    // process author module will sometimes be the module we are in
                    // and sometimes will be a related module
                    if ($row['prj_module'] != $base_module) {
                        $base_module = $this->getRelatedModuleName($row['prj_module'], $base_module);
                    }

                    // Check if field exists
                    $type = $this->validatePAFieldExists($base_module, $field, $paDef);
                    if ($type === false) continue;

                    // Validate dropdown or multiselect option list exsists
                    $listKeys = $this->validatePAOptionListExists($type, $base_module, $field, $paDef);
                    if ($listKeys === false) continue;

                    foreach($condition->value as $value){
                        $selectedKey = $value->expValue;

                        // Validate selected key is in list
                        $new_rst_source_Definition = $this->validatePASelectedKey($selectedKey, $listKeys, $new_rst_source_Definition, $paDef);
                    }
                }

                // conclusions
                foreach ($rule_record->conclusions as $conclusions) {

                    if($conclusions->conclusion_type == "return"){
                        // return types can have multiple fields...

                        foreach($conclusions->value as $value){

                            if($value->expType != "VARIABLE") continue;

                            $base_module = $value->expModule;
                            $field = $value->expValue;
                            
                            // process author module will sometimes be the module we are in
                            // and sometimes will be a related module
                            if ($row['prj_module'] != $base_module) {
                                $base_module = $this->getRelatedModuleName($row['prj_module'], $base_module);
                            }

                            // Check if field exists
                            $type = $this->validatePAFieldExists($base_module, $field, $paDef);
                            if ($type === false) continue;

                            // Validate dropdown or multiselect option list exsists
                            $listKeys = $this->validatePAOptionListExists($type, $base_module, $field, $paDef);
                            if ($listKeys === false) continue;
                        }

                    }else{

                        $base_module = $conclusions->variable_module;
                        $field = $conclusions->conclusion_value;
                        
                        // process author module will sometimes be the module we are in
                        // and sometimes will be a related module
                        if ($row['prj_module'] != $base_module) {
                            $base_module = $this->getRelatedModuleName($row['prj_module'], $base_module);
                        }

                        // Check if field exists
                        $type = $this->validatePAFieldExists($base_module, $field, $paDef);
                        if ($type === false) continue;

                        // Validate dropdown or multiselect option list exsists
                        $listKeys = $this->validatePAOptionListExists($type, $base_module, $field, $paDef);
                        if ($listKeys === false) continue;

                        foreach($conclusions->value as $value){
                            $selectedKey = $value->expValue;

                            // Validate selected key is in list
                            $new_rst_source_Definition = $this->validatePASelectedKey($selectedKey, $listKeys, $new_rst_source_Definition, $paDef);
                        }
                    }
                }
            }

            if ($new_rst_source_Definition !== $row['rst_source_Definition']) {
                $results = $this->setBusinessRuleDefinition($brDefinition->id, $new_rst_source_Definition);
                if (!$results == true) {
                    $this->logAction("-> Failed to update Business Rule Criteria ID: {$brDefinition->id} for PA Definition: {$row['prj_id']}. This will have to be fixed manaully.");
                }
            }
        }
    }

    /**
     * Executes the ProcessAuthor repairs
     * @param array $args
     */
    public function execute(array $args)
    {
        if (!$this->isEnt() && !$this->isUlt()) {
            $this->log('Repair ignored as it does not apply to this Edition.');
            return false;
        }

        if (version_compare($GLOBALS['sugar_version'], '7.6', '<')) {
            $this->log('Repair ignored as it does not apply to this version.');
            return false;
        }

        //check for testing an other repair generic params
        parent::execute($args);

        $stamp = time();

        if ($this->backupTable('pmse_bpm_event_definition', $stamp)
            && $this->backupTable('pmse_bpm_activity_definition', $stamp)
            && $this->backupTable('pmse_business_rules', $stamp)
        ) {
            $this->repairEventCriteria();
            $this->repairActivities();
            $this->repairBusinessRules();
        }
    }
}
