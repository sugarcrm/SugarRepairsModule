<?php

require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_Repairs.php');

class supp_ProcessAuthorRepairs extends supp_Repairs
{
    protected $loggerTitle = "ProcessAuthor";

    public $foundStartCriteriaIssues = array();

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Sets a process author definition start event criteria
     * @param string $eventId pmse_bpm_event_definition record id
     * @param string $new_evn_criteria new json and html encoded criteria data
     * @return boolean update query result
     */
    public function setEventDefinition($eventId, $new_evn_criteria)
    {

        // $new_evn_criteria = html_entity_decode($new_evn_criteria);

        $results = false;
        $sql = "
            UPDATE pmse_bpm_event_definition
            SET evn_criteria = '$new_evn_criteria'
            WHERE id = '$eventId'
        ";
        if (!$this->isTesting) {
            $this->logChange("-> Updating PA Start Criteria '{$eventId}' to: '{$new_evn_criteria}'");
            $results = $this->updateQuery($sql);
        } else {
            $this->logChange("-> Will update PA Definition Start Event '{$eventId}' to: '{$new_evn_criteria}'");
        }

        return $results;

    }

    public function repairStartCriteria()
    {
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
                AND ed.evn_type = 'START' 
                AND ed.evn_criteria <> '' 
                AND ed.evn_criteria IS NOT NULL
        ";
        $result = $GLOBALS['db']->query($sql);
        while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
            $this->log("Processing PA Definition '{$row['name']}' ({$row['prj_id']}) Action ({$row['event_id']})...");
            $new_evn_criteria = $row['evn_criteria'];
            $eventArray = json_decode(html_entity_decode($row['evn_criteria']));

            if ($eventArray==false || $eventArray == null) {
                $this->logAction("-> PA Definition '{$row['name']}' Start Criteria failed to process::".$this->getJSONLastError());
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

                $type = $this->getFieldType($base_module, $field);

                $this->log("-> Checking $base_module :: $field");

                if ($type == false) {
                    $this->logAction("-> PA Definition '{$row['name']}' ({$row['prj_id']}) has an action ({$row['event_id']}) with a deleted or missing field on {$base_module} / {$field}. You should review this definition.");
                    $this->foundStartCriteriaIssues[$row['event_id']] = $row['event_id'];
                    $this->disablePADefinition($row['prj_id']);
                    continue;
                }

                if (in_array($type, array('enum', 'multienum'))) {
                    $listKeys = $this->getFieldOptionKeys($base_module, $field);

                    if ($listKeys == false) {
                        $this->logAction("-> PA Definition '{$row['name']}' ({$row['prj_id']}) has a field ({$base_module} / {$field}) with a deleted or missing dropdown list. You should review this definition or field.");
                        $this->foundStartCriteriaIssues[$row['event_id']] = $row['event_id'];
                        $this->disablePADefinition($row['prj_id']);
                        continue;
                    }

                    $selectedKey = $event->expValue;
                    $issue = false;
                    if (!in_array($selectedKey, $listKeys)) {
                        $this->foundStartCriteriaIssues[$row['event_id']] = $row['event_id'];
                        $issue = true;
                    }

                    if ($issue) {
                        $testKey = $this->getValidLanguageKeyName($selectedKey);
                        //try to fix the key if it was updated in the lang repair script
                        if ($testKey !== $selectedKey) {
                            if (in_array($testKey, $listKeys)) {
                                $issue = false;
                                $new_evn_criteria = str_replace($selectedKey, $testKey, $new_evn_criteria);

                                if (!$this->isTesting) {
                                    $this->logChange("-> PA Definition '{$row['name']}' ({$row['prj_id']}) has an action ({$row['event_id']}) with an invalid key '{$selectedKey}' that was updated to '{$testKey}'. Allowed keys for {$base_module} / {$field} are: " . print_r($listKeys, true));
                                } else {
                                    $this->logChange("-> PA Definition '{$row['name']}' ({$row['prj_id']}) has an action ({$row['event_id']}) with an invalid key '{$selectedKey}' that will be updated to '{$testKey}'. Allowed keys for {$base_module} / {$field} are: " . print_r($listKeys, true));
                                }
                            }
                        }
                    }

                    if ($issue) {
                        $this->logAction("-> PA Definition '{$row['name']}' ({$row['prj_id']}) has a Start Criteria ({$row['event_id']}) with an invalid key '{$selectedKey}'. You should review this definition. Allowed keys for {$base_module} / {$field} are: " . print_r($listKeys, true));
                        $this->foundStartCriteriaIssues[$row['event_id']] = $row['event_id'];
                        $this->disablePADefinition($row['prj_id']);
                    }

                    if ($new_evn_criteria !== $row['evn_criteria']) {
                        $results = $this->setEventDefinition($row['event_id'], $new_evn_criteria);
                        if (!$results == true) {
                            $this->logAction("-> Failed to update Start Criteria ID: {$row['event_id']} for PA Definition: {$row['prj_id']}. This will have to be fixed manaully.");
                        }
                    }
                }
            }
        }
        $foundIssuesCount = count($this->foundStartCriteriaIssues);
        $this->log("Found {$foundIssuesCount} bad PA Definition criteria.");
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
            && $this->backupTable('pmse_bpm_related_dependency', $stamp)
        ) {
            $this->repairStartCriteria();
        }
    }
}
