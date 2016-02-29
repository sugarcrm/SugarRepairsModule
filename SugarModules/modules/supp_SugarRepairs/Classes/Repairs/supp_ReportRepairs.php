<?php

require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_Repairs.php');
require_once('modules/Reports/templates/templates_reports.php');

class supp_ReportRepairs extends supp_Repairs
{
    protected $loggerTitle = "Reports";

    function __construct()
    {
        parent::__construct();
    }

    /**
     * Iterates the filter
     * @param $filters
     */
    public function repairFilters(&$filters, $report)
    {
        for ($i = 0; $i < count($filters) - 1; $i++) {
            if (isset($filters[$i]['operator'])) {
                $this->repairFilters($filters[$i], $report);
            }
            else {

                $key = $report->module;
                if (isset($filters[$i]['table_key']) && $filters[$i]['table_key'] !== 'self') {
                    $key = $filters[$i]['table_key'];
                }

                $type = $this->getFieldType($key, $filters[$i]['name']);

                if ($type == false) {
                    $this->log("Report '{$report->name}' ({$report->id}) has a filter with a deleted or missing field on {$key} / {$filters[$i]['name']}");
                    //$this->disableWorkflow($row['workflow_id']);
                }

            }
        }
    }


    /**
     * Removes duplicate teams in a team set
     */
    public function repairReports()
    {
        $sql = "SELECT id FROM saved_reports where id = '1062c902-7938-ccb8-6d4f-56d486c7b9b8' and deleted = 0";

        $result = $GLOBALS['db']->query($sql);

        $foundIssues = 0;
        $jsonObj = getJSONobj();
        while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
            $report = BeanFactory::getBean('Reports', $row['id']);

            $content = $jsonObj->decode(html_entity_decode($report->content, ENT_COMPAT, 'UTF-8'));




            if (isset($content['filters_def']) && !empty($content['filters_def'])) {
                //echo $row['id'];

                //$filterContentsArray = array();
                //getFlatListFilterContents($content['filters_def']['Filter_1'], $filterContentsArray);

                $this->repairFilters($content['filters_def']['Filter_1'], $report);

                print_r($content['filters_def']);
                die();
            }
            //print_r($content);
            //die();
        }
//        while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
//
//            $leftModule = $row['lhs_module'];
//            $leftField = $row['lhs_field'];
//            $rightValue = $row['rhs_value'];
//            $type = $this->getFieldType($leftModule, $leftField);
//
//            if ($type == false) {
//                $this->log("Workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has an expression ({$row['expression_id']}) with a deleted or missing field on {$leftModule} / {$leftField}");
//                $this->disableWorkflow($row['workflow_id']);
//            }
//
//            if ($type && $type !== $row['exp_type']) {
//                $this->log("Workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has an expression ({$row['expression_id']}) that has a mismatched field type of {$row['exp_type']} / {$type} for {$leftModule} / {$leftField}");
//                $this->disableWorkflow($row['workflow_id']);
//            }
//
//            if (in_array($row['exp_type'], array('enum', 'multienum')) && in_array($type, array('enum', 'multienum'))) {
//                $listKeys = $this->getFieldOptionKeys($leftModule, $leftField);
//                $selectedKeys = unencodeMultienum($rightValue);
//
//                $modifiedSelectedKeys = $selectedKeys;
//                foreach ($selectedKeys as $id => $selectedKey) {
//                    $issue = false;
//                    if (!in_array($selectedKey, $listKeys)) {
//                        $foundIssues++;
//                        $issue = true;
//                    }
//
//                    if ($issue) {
//                        $testKey = $this->getValidLanguageKeyName($selectedKey);
//                        //try to fix the key if it was updated in the lang repair script
//                        if ($testKey !== $selectedKey) {
//                            if (in_array($testKey, $listKeys)) {
//                                $issue = false;
//                                $modifiedSelectedKeys[$id] = $testKey;
//                                if (!$this->isTesting) {
//                                    $this->log("Workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has an expression ({$row['expression_id']}) with an invalid key '{$selectedKey}' that was updated to '{$testKey}'. Allowed keys for {$leftModule} / {$leftField} are: " . print_r($listKeys, true));
//                                } else {
//                                    $this->log("Workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has an expression ({$row['expression_id']}) with an invalid key '{$selectedKey}' that will be updated to '{$testKey}'. Allowed keys for {$leftModule} / {$leftField} are: " . print_r($listKeys, true));
//                                }
//                            }
//                        }
//                    }
//
//                    if ($issue) {
//                        $this->log("Workflow '{$row['workflow_name']}' ({$row['workflow_id']}) has an expression ({$row['expression_id']}) with an invalid key '{$selectedKey}'. Allowed keys for {$leftModule} / {$leftField} are: " . print_r($listKeys, true));
//                        $this->disableWorkflow($row['workflow_id']);
//                    }
//                }
//
//                if ($modifiedSelectedKeys !== $selectedKeys) {
//
//                    //dont use encodeMultienumValue(), for some reason expressions dont use the outer ^ chars
//                    $from = implode('^,^', $selectedKeys);
//                    $to = implode('^,^', $modifiedSelectedKeys);
//                    if (!$this->isTesting) {
//                        $expression = BeanFactory::retrieveBean('Expressions', $row['expression_id']);
//
//                        if ($expression) {
//                            $expression->rhs_value = $to;
//                            $expression->save();
//                        }
//
//                        if (!empty($expression->parent_id)) {
//                            $workflowTriggerShell = BeanFactory::retrieveBean('WorkFlowTriggerShells', $expression->parent_id);
//                            $workflowTriggerShell->save();
//                        }
//
//                        if (!empty($workflowTriggerShell->parent_id)) {
//                            $workflow = BeanFactory::retrieveBean('WorkFlow', $workflowTriggerShell->parent_id);
//                            $workflow->save();
//                        }
//
//                    } else {
//                        $this->log("Will update expression '{$row['expression_id']}' from: '{$from}' to: '{$to}'");
//                    }
//
//                }
//            }
//        }

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

        //check for testing an other reapir generic params
        parent::execute($args);

        $stamp = time();

        if (
        $this->backupTable('saved_reports', $stamp)
        ) {
            $this->repairReports();
        }

    }

}
