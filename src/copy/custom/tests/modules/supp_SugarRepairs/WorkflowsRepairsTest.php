<?php
// Copyright 2017 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_WorkflowRepairs.php');

/**
 * @group support
 * @group workflow
 */
class suppSugarRepairsWorkflowRepairsTest extends Sugar_PHPUnit_Framework_TestCase
{
    protected $workflowData = array();

    public function setUp()
    {
        parent::setUp();
        SugarTestHelper::setUp("current_user");
        $GLOBALS['current_user']->is_admin = 1;
        $GLOBALS['app_list_strings'] = return_app_list_strings_language('en_us');
        $this->workflowData();
    }

    public function tearDown()
    {
        $this->teardownTestWorkflow();
        parent::tearDown();
    }

    /**
     * Test for disabling workflows with issues in expressions
     */
    public function testRepairWorkflow()
    {
        $this->setupTestWorkflow();
        $workflowRepairs = new supp_WorkflowRepairs();
        $workflowRepairs->execute(array('test' => false));

        foreach ($this->workflowData as $index => $data) {
            if (stristr($index, "Expression_Test") !== false) {
                switch ($data['result']) {
                    case 'changed':
                        $expression = BeanFactory::getBean('Expressions', $data['id'] . 'expression1');
                        $this->assertEquals($data['result_value'], $expression->rhs_value);
                        break;
                    case 'disabled':
                        $workflow = BeanFactory::getBean('WorkFlow', $data['id']);
                        $this->assertEquals(0, $workflow->status);
                        break;
                }
            }

            if (stristr($index, "Action_Test") !== false) {
                switch ($data['result']) {
                    case 'changed':
                        $workFlowAction = BeanFactory::getBean('WorkFlowActions', $data['id'] . 'wfa1');
                        $this->assertEquals($data['result_value'], $workFlowAction->value, 'Record: ' . $workFlowAction->id);
                        break;
                    case 'disabled':
                        $workflow = BeanFactory::getBean('WorkFlow', $data['id']);
                        $this->assertEquals(0, $workflow->status);
                        break;
                }
            }
        }
        $this->teardownTestWorkflow();
    }

    private function workflowData()
    {
        $this->workflowData = array(
            'Expression_Test1' => array(
                'id' => 'WF1',
                'name' => 'Parentheses test 1',
                'expression_value' => '(Education)',
                'expression_value2' => 'Finance',
                'expression_field' => 'industry',
                'action_type' => 'update',
                'result' => 'changed',
                'result_value' => 'Education'
            ),
            'Expression_Test2' => array(
                'id' => 'WF2',
                'name' => 'No Match Test 1',
                'expression_value' => 'SHOULDNOTMATCH',
                'expression_value2' => 'Finance',
                'expression_field' => 'industry',
                'action_type' => 'update',
                'result' => 'disabled'
            ),
            'Expression_Test3' => array(
                'id' => 'WF3',
                'name' => 'Deleted Field Test 1',
                'expression_value' => 'Education',
                'expression_value2' => 'Finance',
                'expression_field' => 'NOFIELD',
                'action_type' => 'update',
                'result' => 'disabled'
            ),

            'Action_Test1' => array(
                'id' => 'WF4',
                'name' => 'Parentheses test 2',
                'expression_value' => '(Education)',
                'expression_value2' => 'Finance',
                'expression_field' => 'industry',
                'action_type' => 'update',
                'result' => 'changed',
                'result_value' => 'Education'
            ),
            'Action_Test2' => array(
                'id' => 'WF5',
                'name' => 'No Match Test 2',
                'expression_value' => 'SHOULDNOTMATCH',
                'expression_value2' => 'Finance',
                'expression_field' => 'industry',
                'action_type' => 'update',
                'result' => 'disabled'
            ),
            'Action_Test3' => array(
                'id' => 'WF6',
                'name' => 'Deleted Field Test 2',
                'expression_value' => 'Education',
                'expression_value2' => 'Finance',
                'expression_field' => 'NOFIELD',
                'action_type' => 'update',
                'result' => 'disabled'
            ),
            'Action_Test4' => array(
                'id' => 'WF7',
                'name' => 'Related Field Test',
                'expression_value' => '(Dead)',
                'expression_value2' => 'New',
                'expression_field' => 'status',
                'action_type' => 'related',
                'result' => 'changed',
                'result_value' => 'Dead'
            ),
            'Action_Test5' => array(
                'id' => 'WF8',
                'name' => 'New Module Field test',
                'expression_value' => '(Product)',
                'expression_value2' => 'New',
                'expression_field' => 'type',
                'action_type' => 'new',
                'result' => 'changed',
                'result_value' => 'Product'
            ),
            'Action_Test6' => array(
                'id' => 'WF9',
                'name' => 'Related Field Test / No Matching Key',
                'expression_value' => 'NOMATCH',
                'expression_value2' => 'New',
                'expression_field' => 'status',
                'action_type' => 'related',
                'result' => 'disabled'
            ),
            'Action_Test7' => array(
                'id' => 'WF10',
                'name' => 'New Module test / Deleted Field',
                'expression_value' => 'Product',
                'expression_value2' => 'New',
                'expression_field' => 'NOFIELD',
                'action_type' => 'related',
                'result' => 'disabled'
            ),

        );
    }

    private function setupTestWorkflow()
    {
        foreach ($this->workflowData as $index => $data) {
            $workflow = new WorkFlow();
            $workflow->id = $data['id'];
            $workflow->new_with_id = true;
            $workflow->name = $data['name'];
            $workflow->base_module = 'Accounts';
            $workflow->status = 1;
            $workflow->type = 'Normal';
            $workflow->fire_order = 'alerts_actions';
            $workflow->record_type = 'All';
            $workflow->save();

            $workflow_triggershell = new WorkFlowTriggerShell();
            $workflow_triggershell->id = $data['id'] . 'wfts1';
            $workflow_triggershell->new_with_id = true;
            $workflow_triggershell->field = $data['expression_field'];
            $workflow_triggershell->type = 'compare_specific';
            $workflow_triggershell->frame_type = 'Primary';
            $workflow_triggershell->eval = " ( !(\$focus->fetched_row['{$data['expression_field']}'] ==  '{$data['expression_value']}' )) && (isset(\$focus->{$data['expression_field']}) && \$focus->{$data['expression_field']} ==  '{$data['expression_value']}')";
            $workflow_triggershell->parent_id = $data['id'];
            $workflow_triggershell->rel_module_type = 'any';
            $workflow_triggershell->show_past = 0;
            $workflow_triggershell->save();

            $expression = new Expression();
            $expression->id = $data['id'] . 'expression1';
            $expression->new_with_id = true;
            $expression->lhs_field = $data['expression_field'];
            $expression->lhs_module = 'Accounts';
            $expression->operator = 'Equals';
            $expression->rhs_value = $data['expression_value'];
            $expression->parent_id = $data['id'] . 'wfts1';
            $expression->exp_type = 'enum';
            $expression->parent_type = 'future_trigger';
            $expression->save();

            switch ($data['action_type']) {
                case 'update':
                    $workflow_actionshell = new WorkFlowActionShell();
                    $workflow_actionshell->id = $data['id'] . "wfas1";
                    $workflow_actionshell->new_with_id = true;
                    $workflow_actionshell->action_type = $data['action_type'];
                    $workflow_actionshell->parent_id = $data['id'];
                    $workflow_actionshell->rel_module_type = 'all';
                    $workflow_actionshell->save();

                    $workflow_actions = new WorkFlowAction();
                    $workflow_actions->id = $data['id'] . 'wfa1';
                    $workflow_actions->new_with_id = true;
                    $workflow_actions->field = $data['expression_field'];
                    $workflow_actions->value = $data['expression_value'];
                    $workflow_actions->set_type = 'Basic';
                    $workflow_actions->parent_id = $data['id'] . 'wfas1';
                    $workflow_actions->save();
                    break;
                case 'related':
                    $workflow_actionshell = new WorkFlowActionShell();
                    $workflow_actionshell->id = $data['id'] . "wfas2";
                    $workflow_actionshell->new_with_id = true;
                    $workflow_actionshell->action_type = 'update_rel';
                    $workflow_actionshell->rel_module = 'leads';
                    $workflow_actionshell->parent_id = $data['id'];
                    $workflow_actionshell->rel_module_type = 'all';
                    $workflow_actionshell->save();

                    $workflow_actions = new WorkFlowAction();
                    $workflow_actions->id = $data['id'] . 'wfa1';
                    $workflow_actions->new_with_id = true;
                    $workflow_actions->field = $data['expression_field'];
                    $workflow_actions->value = $data['expression_value'];
                    $workflow_actions->set_type = 'Basic';
                    $workflow_actions->parent_id = $data['id'] . 'wfas2';
                    $workflow_actions->save();
                    break;
                case 'new':
                    $workflow_actionshell = new WorkFlowActionShell();
                    $workflow_actionshell->id = $data['id'] . "wfas3";
                    $workflow_actionshell->new_with_id = true;
                    $workflow_actionshell->action_type = 'new';
                    $workflow_actionshell->action_module = 'cases';
                    $workflow_actionshell->parent_id = $data['id'];
                    $workflow_actionshell->rel_module_type = 'all';
                    $workflow_actionshell->save();

                    $workflow_actions = new WorkFlowAction();
                    $workflow_actions->id = $data['id'] . 'wfa1';
                    $workflow_actions->new_with_id = true;
                    $workflow_actions->field = $data['expression_field'];
                    $workflow_actions->value = $data['expression_value'];
                    $workflow_actions->set_type = 'Basic';
                    $workflow_actions->parent_id = $data['id'] . 'wfas3';
                    $workflow_actions->save();

                    $workflow_actions = new WorkFlowAction();
                    $workflow_actions->id = $data['id'] . 'wfa2';
                    $workflow_actions->new_with_id = true;
                    $workflow_actions->field = 'name';
                    $workflow_actions->value = 'Test';
                    $workflow_actions->set_type = 'Basic';
                    $workflow_actions->parent_id = $data['id'] . 'wfas3';
                    $workflow_actions->save();
                    break;
            }
        }
    }


    private function teardownTestWorkflow()
    {
        foreach ($this->workflowData as $index => $data) {
            $sql = "DELETE FROM workflow WHERE id LIKE '{$data['id']}%'";
            $GLOBALS['db']->query($sql);
            $sql = "DELETE FROM workflow_actions WHERE id LIKE '{$data['id']}%'";
            $GLOBALS['db']->query($sql);
            $sql = "DELETE FROM workflow_actionshells WHERE id LIKE '{$data['id']}%'";
            $GLOBALS['db']->query($sql);
            $sql = "DELETE FROM workflow_triggershells WHERE id LIKE '{$data['id']}%'";
            $GLOBALS['db']->query($sql);
            $sql = "DELETE FROM expressions WHERE id LIKE '{$data['id']}%'";
            $GLOBALS['db']->query($sql);
        }
    }
}
