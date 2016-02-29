<?php

require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_WorkflowRepairs.php');

/**
 * @group support
 * @group workflow
 */
class suppSugarRepairsWorkflowRepairsTest extends Sugar_PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        SugarTestHelper::setUp("current_user");
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Test for disabling workflows with issues in expressions
     */
    public function testRepairWorkflowExpressions()
    {

    }

    private function setupTestWorkflow()
    {
        //$workflow = BeanFactory::newBean("WorkFlow");
        $workflow = new WorkFlow();
        $workflow->id = 'UNITTEST1';
        $workflow->new_with_id = true;
        $workflow->name = 'UNIT TEST RECORD';
        $workflow->base_module = 'Accounts';
        $workflow->status = 1;
        $workflow->type = 'Normal';
        $workflow->fire_order = 'alerts_actions';
        $workflow->record_type = 'All';
        $workflow->save();

        $workflow_triggershell = new WorkFlowTriggerShell();
        $workflow_triggershell->id = 'wfts1';
        $workflow_triggershell->new_with_id = true;
        $workflow_triggershell->field = 'industry';
        $workflow_triggershell->type = 'compare_specific';
        $workflow_triggershell->frame_type = 'Primary';
        //$workflow_triggershell->eval=" ( !(\$focus->fetched_row['industry'] ==  'Education' )) && (isset(\$focus->industry) && \$focus->industry ==  'Education')";
        $workflow_triggershell->parent_id = 'UNITTEST1';
        $workflow_triggershell->rel_module_type = 'any';
        $workflow_triggershell->show_past = 0;
        $workflow_triggershell->save();

        $expression = new Expression();
        $expression->id = 'EXPR1';
        $expression->new_with_id = true;
        $expression->lhs_field = 'industry';
        $expression->lhs_module = 'Accounts';
        $expression->operator = 'Equals';
        $expression->rhs_value = 'Education';
        $expression->parent_id = 'wfts1';
        $expression->exp_type = 'enum';
        $expression->parent_type = 'future_trigger';
        $expression->save();


        $workflow_actionshell = new WorkFlowActionShell();
        $workflow_actionshell->id = "wfas1";
        $workflow_actionshell->new_with_id = true;
        $workflow_actionshell->action_type = 'update';
        $workflow_actionshell->parent_id = 'UNITTEST1';
        $workflow_actionshell->rel_module_type = 'all';
        $workflow_actionshell->save();

        $workflow_actionshell = new WorkFlowActionShell();
        $workflow_actionshell->id = "wfas2";
        $workflow_actionshell->new_with_id = true;
        $workflow_actionshell->action_type = 'update_rel';
        $workflow_actionshell->rel_module = 'leads';
        $workflow_actionshell->parent_id = 'UNITTEST1';
        $workflow_actionshell->rel_module_type = 'all';
        $workflow_actionshell->save();

        $workflow_actionshell = new WorkFlowActionShell();
        $workflow_actionshell->id = "wfas3";
        $workflow_actionshell->new_with_id = true;
        $workflow_actionshell->action_type = 'new';
        $workflow_actionshell->actionModule = 'cases';
        $workflow_actionshell->parent_id = 'UNITTEST1';
        $workflow_actionshell->rel_module_type = 'all';
        $workflow_actionshell->save();

        $workflow_actionshell = new WorkFlowActionShell();
        $workflow_actionshell->id = "wfas4";
        $workflow_actionshell->new_with_id = true;
        $workflow_actionshell->action_type = 'update';
        $workflow_actionshell->parent_id = 'UNITTEST1';
        $workflow_actionshell->rel_module_type = 'all';
        $workflow_actionshell->save();

        $workflow_actions = new WorkFlowAction();
        $workflow_actions->id = 'wfa1';
        $workflow_actions->new_with_id = true;
        $workflow_actions->field = 'industry';
        $workflow_actions->value = 'Finance';
        $workflow_actions->set_type = 'Basic';
        $workflow_actions->parent_id = 'wfas1';
        $workflow_actions->save();

        $workflow_actions = new WorkFlowAction();
        $workflow_actions->id = 'wfa2';
        $workflow_actions->new_with_id = true;
        $workflow_actions->field = 'status';
        $workflow_actions->value = 'Dead';
        $workflow_actions->set_type = 'Basic';
        $workflow_actions->parent_id = 'wfas2';
        $workflow_actions->save();

        $workflow_actions = new WorkFlowAction();
        $workflow_actions->id = 'wfa3';
        $workflow_actions->new_with_id = true;
        $workflow_actions->field = 'name';
        $workflow_actions->value = 'Test';
        $workflow_actions->set_type = 'Basic';
        $workflow_actions->parent_id = 'wfas3';
        $workflow_actions->save();

        $workflow_actions = new WorkFlowAction();
        $workflow_actions->id = 'wfa4';
        $workflow_actions->new_with_id = true;
        $workflow_actions->field = 'type';
        $workflow_actions->value = 'Product';
        $workflow_actions->set_type = 'Basic';
        $workflow_actions->parent_id = 'wfas3';
        $workflow_actions->save();

        $workflow_actions = new WorkFlowAction();
        $workflow_actions->id = 'wfa5';
        $workflow_actions->new_with_id = true;
        $workflow_actions->field = 'Description';
        $workflow_actions->value = 'Test';
        $workflow_actions->set_type = 'Basic';
        $workflow_actions->parent_id = 'wfas4';
        $workflow_actions->save();
    }


    private function teardownTestWorkflow()
    {

    }
}