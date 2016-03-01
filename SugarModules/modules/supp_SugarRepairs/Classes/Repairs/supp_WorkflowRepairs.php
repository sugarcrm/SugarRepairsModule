<?php

require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_Repairs.php');

class supp_WorkflowRepairs extends supp_Repairs
{
    protected $loggerTitle = "Workflow";

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

        $foundIssues = 0;
        while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
            $field = $row['field'];
            $value = $row['value'];
            $base_module = $row['base_module'];
            $type = $this->getFieldType($base_module, $field);
            $seed_object = BeanFactory::getBean('WorkFlow', $row['WorkFlow']);
            //For workflow actions that affect related modules
            if(isset($row['rel_module']) && !empty($row['rel_module'])) {
                $rel_module = $seed_object->get_rel_module($row['rel_module']);
                $base_module = $rel_module;
                $type = $this->getFieldType($rel_module, $field);
            }
            //for workflows that create related modules
            if(isset($row['action_module']) && !empty($row['action_module'])) {
                $action_module = $seed_object->get_rel_module($row['action_module']);
                $base_module = $action_module;
                $type = $this->getFieldType($action_module, $field);
            }

            if ($type == false) {
                $this->log("Workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has an action ({$row['workflow_actionsID']}) with a deleted or missing field on {$base_module} / {$field}");
                $this->disableWorkflow($row['workflow_id']);
            }

            if (in_array($type, array('enum', 'multienum'))) {
                $listKeys = $this->getFieldOptionKeys($base_module, $field);
                $selectedKeys = unencodeMultienum($value);

                $modifiedSelectedKeys = $selectedKeys;
                foreach ($selectedKeys as $id => $selectedKey) {
                    $issue = false;
                    if (!in_array($selectedKey, $listKeys)) {
                        $foundIssues++;
                        $issue = true;
                    }

                    if ($issue) {
                        $testKey = $this->getValidLanguageKeyName($selectedKey);
                        //try to fix the key if it was updated in the lang repair script
                        if ($testKey !== $selectedKey) {
                            if (in_array($testKey, $listKeys)) {
                                $issue = false;
                                $modifiedSelectedKeys[$id] = $testKey;
                                if (!$this->isTesting) {
                                    $this->log("Workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has an action ({$row['workflow_actionsID']}) with an invalid key '{$selectedKey}' that was updated to '{$testKey}'. Allowed keys for {$base_module} / {$field} are: " . print_r($listKeys, true));
                                } else {
                                    $this->log("Workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has an action ({$row['workflow_actionsID']}) with an invalid key '{$selectedKey}' that will be updated to '{$testKey}'. Allowed keys for {$base_module} / {$field} are: " . print_r($listKeys, true));
                                }
                            }
                        }
                    }

                    if ($issue) {
                        $this->log("Workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has an action ({$row['workflow_actionsID']}) with an invalid key '{$selectedKey}'. Allowed keys for {$base_module} / {$field} are: " . print_r($listKeys, true));
                        $this->disableWorkflow($row['workflow_id']);
                    }
                }

                if ($modifiedSelectedKeys !== $selectedKeys) {

                    //dont use encodeMultienumValue(), for some reason expressions dont use the outer ^ chars
                    $from = implode('^,^', $selectedKeys);
                    $to = implode('^,^', $modifiedSelectedKeys);
                    if (!$this->isTesting) {
                        $workFlowAction = BeanFactory::getBean('WorkFlowActions', $row['workflow_actionsID']);

                        if ($workFlowAction) {
                            $workFlowAction->value = $to;
                            $workFlowAction->save();
                        }
                    } else {
                        $this->log("Will update workFlowActions '{$row['workflow_actionsID']}' from: '{$from}' to: '{$to}'");
                    }

                }
            }
        }

        $this->log("Found {$foundIssues} bad workflow actions.");
    }

    /**
     * Repairs field level issues in the Workflows Expressions table
     */
    public function repairExpressions()
    {
        $sql = "SELECT workflow.id AS workflow_id, workflow.name AS workflow_name, expressions.id AS expression_id, expressions.lhs_module, expressions.lhs_field, expressions.rhs_value, expressions.exp_type FROM workflow INNER JOIN workflow_triggershells ON workflow_triggershells.parent_id = workflow.id INNER JOIN expressions on expressions.parent_id = workflow_triggershells.id WHERE workflow.deleted = 0 AND expressions.deleted = 0 AND workflow_triggershells.deleted = 0";

        $result = $GLOBALS['db']->query($sql);

        $foundIssues = 0;
        while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
            $leftModule = $row['lhs_module'];
            $leftField = $row['lhs_field'];
            $rightValue = $row['rhs_value'];
            $type = $this->getFieldType($leftModule, $leftField);

            if ($type == false) {
                $this->log("Workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has an expression ({$row['expression_id']}) with a deleted or missing field on {$leftModule} / {$leftField}");
                $this->disableWorkflow($row['workflow_id']);
            }

            if ($type && $type !== $row['exp_type']) {
                $this->log("Workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has an expression ({$row['expression_id']}) that has a mismatched field type of {$row['exp_type']} / {$type} for {$leftModule} / {$leftField}");
                $this->disableWorkflow($row['workflow_id']);
            }

            if (in_array($row['exp_type'], array('enum', 'multienum')) && in_array($type, array('enum', 'multienum'))) {
                $listKeys = $this->getFieldOptionKeys($leftModule, $leftField);
                $selectedKeys = unencodeMultienum($rightValue);

                $modifiedSelectedKeys = $selectedKeys;
                foreach ($selectedKeys as $id => $selectedKey) {
                    $issue = false;
                    if (!in_array($selectedKey, $listKeys)) {
                        $foundIssues++;
                        $issue = true;
                    }

                    if ($issue) {
                        $testKey = $this->getValidLanguageKeyName($selectedKey);
                        //try to fix the key if it was updated in the lang repair script
                        if ($testKey !== $selectedKey) {
                            if (in_array($testKey, $listKeys)) {
                                $issue = false;
                                $modifiedSelectedKeys[$id] = $testKey;
                                if (!$this->isTesting) {
                                    $this->log("Workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has an expression ({$row['expression_id']}) with an invalid key '{$selectedKey}' that was updated to '{$testKey}'. Allowed keys for {$leftModule} / {$leftField} are: " . print_r($listKeys, true));
                                } else {
                                    $this->log("Workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has an expression ({$row['expression_id']}) with an invalid key '{$selectedKey}' that will be updated to '{$testKey}'. Allowed keys for {$leftModule} / {$leftField} are: " . print_r($listKeys, true));
                                }
                            }
                        }
                    }

                    if ($issue) {
                        $this->log("Workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has an expression ({$row['expression_id']}) with an invalid key '{$selectedKey}'. Allowed keys for {$leftModule} / {$leftField} are: " . print_r($listKeys, true));
                        $this->disableWorkflow($row['workflow_id']);
                    }
                }

                if ($modifiedSelectedKeys !== $selectedKeys) {

                    //dont use encodeMultienumValue(), for some reason expressions dont use the outer ^ chars
                    $from = implode('^,^', $selectedKeys);
                    $to = implode('^,^', $modifiedSelectedKeys);
                    if (!$this->isTesting) {
                        $expression = BeanFactory::getBean('Expressions', $row['expression_id']);

                        if ($expression) {
                            $expression->rhs_value = $to;
                            $expression->save();
                        }

                        if (!empty($expression->parent_id)) {
                            $workflowTriggerShell = BeanFactory::getBean('WorkFlowTriggerShells', $expression->parent_id);
                            $workflowTriggerShell->save();
                        }

                        if (!empty($workflowTriggerShell->parent_id)) {
                            $workflow = BeanFactory::getBean('WorkFlow', $workflowTriggerShell->parent_id);
                            $workflow->save();
                        }

                    } else {
                        $this->log("Will update expression '{$row['expression_id']}' from: '{$from}' to: '{$to}'");
                    }

                }
            }
        }

        $this->log("Found {$foundIssues} bad workflow expressions.");
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
