<?php

require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_Repairs.php');

class supp_WorkflowRepairs extends supp_Repairs
{
    protected $loggerTitle = "Workflows";
    protected $foundExpressionIssues = array();
    protected $foundActionIssues = array();

    function __construct()
    {
        parent::__construct();
    }

    /**
     * Repairs field level issues in the Workflows Actions table
     */
    public function repairActions()
    {
        $sql = "SELECT workflow.id AS workflow_id, workflow.base_module, workflow_actions.field, workflow_actions.value,
                workflow.name as workflow_name, workflow_actionshells.rel_module, workflow_actionshells.action_module,
                workflow_actions.id as workflow_actionsID
                FROM workflow_actions
                    INNER JOIN workflow_actionshells ON workflow_actionshells.id = workflow_actions.parent_id
                    INNER JOIN workflow ON workflow.id = workflow_actionshells.parent_id
                WHERE workflow.deleted=0 AND workflow_actions.deleted=0 AND workflow_actionshells.deleted=0";
        $result = $GLOBALS['db']->query($sql);

        while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
            $this->log("Processing workflow '{$row['workflow_name']}' ({$row['workflow_id']}) Action ({$row['workflow_actionsID']})...");

            $field = $row['field'];
            $value = $row['value'];
            $base_module = $row['base_module'];
            $type = $this->getFieldType($base_module, $field);
            $seed_object = BeanFactory::getBean('WorkFlow', $row['workflow_id']);
            //For workflow actions that affect related modules
            if (isset($row['rel_module']) && !empty($row['rel_module'])) {
                $rel_module = $seed_object->get_rel_module($row['rel_module']);
                $base_module = $rel_module;
                $type = $this->getFieldType($rel_module, $field);
            }
            //for workflows that create related modules
            if (isset($row['action_module']) && !empty($row['action_module'])) {
                $action_module = $seed_object->get_rel_module($row['action_module']);
                $base_module = $action_module;
                $type = $this->getFieldType($action_module, $field);
            }

            if ($type == false) {
                $this->logAction("-> Workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has an action ({$row['workflow_actionsID']}) with a deleted or missing field on {$base_module} / {$field}. You should review this workflow.");
                $this->foundActionIssues[$row['workflow_actionsID']] = $row['workflow_actionsID'];
                $this->disableWorkflow($row['workflow_id']);
                continue;
            }

            if (in_array($type, array('enum', 'multienum'))) {
                $listKeys = $this->getFieldOptionKeys($base_module, $field);

                if ($listKeys == false) {
                    $this->logAction("-> Workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has a field ({$base_module} / {$field}) with a deleted or missing dropdown list. You should review this workflow or field.");
                    $this->foundActionIssues[$row['workflow_actionsID']] = $row['workflow_actionsID'];
                    $this->disableWorkflow($row['workflow_id']);
                    continue;
                }

                $selectedKeys = unencodeMultienum($value);

                $modifiedSelectedKeys = $selectedKeys;
                foreach ($selectedKeys as $id => $selectedKey) {
                    $issue = false;
                    if (!in_array($selectedKey, $listKeys)) {
                        $this->foundActionIssues[$row['workflow_actionsID']] = $row['workflow_actionsID'];
                        $issue = true;
                    }

                    if ($issue) {
                        $testKey = $this->getValidLanguageKeyName($selectedKey);

                        if ($testKey === false) {
                            $this->logAction("-> The converted key for '{$selectedKey}' in the workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has an action ({$row['workflow_actionsID']}) that will be empty. This will need to be manually corrected.");
                            $this->foundActionIssues[$row['workflow_actionsID']] = $row['workflow_actionsID'];
                            $this->disableWorkflow($row['workflow_id']);
                            continue;
                        }

                        //try to fix the key if it was updated in the lang repair script
                        if ($testKey !== $selectedKey) {
                            if (in_array($testKey, $listKeys)) {
                                $issue = false;
                                $modifiedSelectedKeys[$id] = $testKey;
                                if (!$this->isTesting) {
                                    $this->logChange("-> Workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has an action ({$row['workflow_actionsID']}) with an invalid key '{$selectedKey}' that was updated to '{$testKey}'. Allowed keys for {$base_module} / {$field} are: " . print_r($listKeys, true));
                                } else {
                                    $this->logChange("-> Workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has an action ({$row['workflow_actionsID']}) with an invalid key '{$selectedKey}' that will be updated to '{$testKey}'. Allowed keys for {$base_module} / {$field} are: " . print_r($listKeys, true));
                                }
                            }
                        }
                    }

                    if ($issue) {
                        $this->logAction("-> Workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has an action ({$row['workflow_actionsID']}) with an invalid key '{$selectedKey}'. . You should review this workflow. Allowed keys for {$base_module} / {$field} are: " . print_r($listKeys, true));
                        $this->foundActionIssues[$row['workflow_actionsID']] = $row['workflow_actionsID'];
                        $this->disableWorkflow($row['workflow_id']);
                    }
                }

                if ($modifiedSelectedKeys !== $selectedKeys) {

                    //dont use encodeMultienumValue(), for some reason expressions dont use the outer ^ chars
                    $from = implode('^,^', $selectedKeys);
                    $to = implode('^,^', $modifiedSelectedKeys);
                    if (!$this->isTesting) {
                        $this->logChange("-> Updating workFlowActions '{$row['workflow_actionsID']}' from: '{$from}' to: '{$to}'");
                        $workFlowAction = BeanFactory::getBean('WorkFlowActions', $row['workflow_actionsID']);
                        if ($workFlowAction) {
                            $workFlowAction->value = $to;
                            $workFlowAction->save();
                        }
                    } else {
                        $this->logChange("-> Will update workFlowActions '{$row['workflow_actionsID']}' from: '{$from}' to: '{$to}'");
                    }
                }
            }
        }

        $foundIssuesCount = count($this->foundActionIssues);
        $this->log("Found {$foundIssuesCount} bad workflow actions.");
    }

    /**
     * Repairs field level issues in the Workflows Expressions table
     */
    public function repairExpressions()
    {
        $sql = "SELECT workflow.id AS workflow_id, workflow.name AS workflow_name, expressions.id AS expression_id, expressions.lhs_module, expressions.lhs_field, expressions.rhs_value, expressions.exp_type FROM workflow INNER JOIN workflow_triggershells ON workflow_triggershells.parent_id = workflow.id INNER JOIN expressions on expressions.parent_id = workflow_triggershells.id WHERE workflow.deleted = 0 AND expressions.deleted = 0 AND workflow_triggershells.deleted = 0";

        $result = $GLOBALS['db']->query($sql);

        while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
            $this->log("Processing workflow '{$row['workflow_name']}' ({$row['workflow_id']}) Expression ({$row['expression_id']})...");
            $leftModule = $row['lhs_module'];
            $leftField = $row['lhs_field'];
            $rightValue = $row['rhs_value'];
            $type = $this->getFieldType($leftModule, $leftField);

            if ($type == false) {
                $this->logAction("-> Workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has an expression ({$row['expression_id']}) with a deleted or missing field on {$leftModule} / {$leftField}. You should review this workflow.");
                $this->foundExpressionIssues[$row['expression_id']] = $row['expression_id'];
                $this->disableWorkflow($row['workflow_id']);
                continue;
            }

            if (in_array($row['exp_type'], array('enum', 'multienum')) && in_array($type, array('enum', 'multienum'))) {
                $listKeys = $this->getFieldOptionKeys($leftModule, $leftField);

                if ($listKeys == false) {
                    $this->logAction("-> Workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has a field ({$leftModule} / {$leftField}) with a deleted or missing dropdown list. You should review this workflow or field.");
                    $this->foundExpressionIssues[$row['expression_id']] = $row['expression_id'];
                    $this->disableWorkflow($row['workflow_id']);
                    continue;
                }

                $selectedKeys = unencodeMultienum($rightValue);

                $modifiedSelectedKeys = $selectedKeys;
                foreach ($selectedKeys as $id => $selectedKey) {
                    $issue = false;
                    if (!in_array($selectedKey, $listKeys)) {
                        $this->foundExpressionIssues[$row['expression_id']] = $row['expression_id'];
                        $issue = true;
                    }

                    if ($issue) {
                        $testKey = $this->getValidLanguageKeyName($selectedKey);

                        if ($testKey === false) {
                            $this->logAction("-> The converted key for '{$selectedKey}' in the workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has an expression ({$row['expression_id']}) that will be empty. This will need to be manually corrected.");
                            $this->foundExpressionIssues[$row['expression_id']] = $row['expression_id'];
                            $this->disableWorkflow($row['workflow_id']);
                            continue;
                        }
                            //try to fix the key if it was updated in the lang repair script
                        if ($testKey !== $selectedKey) {
                            if (in_array($testKey, $listKeys)) {
                                $issue = false;
                                $modifiedSelectedKeys[$id] = $testKey;
                                if (!$this->isTesting) {
                                    $this->logChange("-> Workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has an expression ({$row['expression_id']}) with an invalid key '{$selectedKey}' that was updated to '{$testKey}'. Allowed keys for {$leftModule} / {$leftField} are: " . print_r($listKeys, true));
                                } else {
                                    $this->logChange("-> Workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has an expression ({$row['expression_id']}) with an invalid key '{$selectedKey}' that will be updated to '{$testKey}'. Allowed keys for {$leftModule} / {$leftField} are: " . print_r($listKeys, true));
                                }
                            }
                        }
                    }

                    if ($issue) {
                        $this->logAction("-> Workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has an expression ({$row['expression_id']}) with an invalid key '{$selectedKey}'. You should review this workflow. Allowed keys for {$leftModule} / {$leftField} are: " . print_r($listKeys, true));
                        $this->foundExpressionIssues[$row['expression_id']] = $row['expression_id'];
                        $this->disableWorkflow($row['workflow_id']);
                    }
                }

                if ($modifiedSelectedKeys !== $selectedKeys) {

                    //dont use encodeMultienumValue(), for some reason expressions dont use the outer ^ chars
                    $from = implode('^,^', $selectedKeys);
                    $to = implode('^,^', $modifiedSelectedKeys);
                    if (!$this->isTesting) {

                        $this->logChange("-> Updating expression '{$row['expression_id']}' from: '{$from}' to: '{$to}'");
                        $expression = BeanFactory::getBean('Expressions', $row['expression_id']);

                        if ($expression) {
                            $expression->rhs_value = $to;
                            $expression->save();
                        }

                    } else {
                        $this->logChange("-> Will update expression '{$row['expression_id']}' from: '{$from}' to: '{$to}'");
                    }
                }
            }
        }

        $foundIssuesCount = count($this->foundExpressionIssues);
        $this->log("Found {$foundIssuesCount} bad workflow expressions.");
    }

    /**
     * Executes the TeamSet repairs
     * @param array $args
     */
    public function execute(array $args)
    {
        if ($this->isCE()) {
            $this->log('Repair ignored as it does not apply to CE');
            return false;
        }

        //check for testing an other repair generic params
        parent::execute($args);

        $stamp = time();

        if (
            $this->backupTable('workflow', $stamp)
            && $this->backupTable('workflow_actions', $stamp)
            && $this->backupTable('workflow_actionshells', $stamp)
            && $this->backupTable('workflow_alerts', $stamp)
            && $this->backupTable('workflow_alertshells', $stamp)
            && $this->backupTable('workflow_schedules', $stamp)
            && $this->backupTable('workflow_triggershells', $stamp)
            && $this->backupTable('expressions', $stamp)
        ) {
            $this->repairExpressions();
            $this->repairActions();
        }

        if (!$this->isTesting) {
            $this->runRebuildWorkflow();
        }
    }

}
