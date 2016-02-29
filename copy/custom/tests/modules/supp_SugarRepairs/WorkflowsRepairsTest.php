<?php

require_once ('modules/supp_SugarRepairs/Classes/Repairs/supp_WorkflowRepairs.php');

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

    private function setupTestWorkflow() {
    }

    private function teardownTestWorkflow() {

    }
}