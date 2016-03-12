<?php

require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_ProcessAuthorRepairs.php');

/**
 * @group support
 * @group processAuthor
 */
class suppSugarRepairsProcessAuthorRepairsTest extends Sugar_PHPUnit_Framework_TestCase
{

    protected $reportIDs = array();

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $sql_setup = array();

        //Create bean
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '38c90c70-7788-13a2-668d-513e2b8df5e1';
        $bean->new_with_id = true;
        $bean->name = 'Example Record';
        $bean->prj_status = "ACTIVE";
        $bean->save();

        // working record
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_event_definition` (`id`,`deleted`,`prj_id`,`evn_status`,`evn_type`,`evn_module`,`evn_criteria`)
            VALUES ('9ff025b6-e576-11e5-9261-fe497468edid',0,'9ff025b6-e576-11e5-9261-fe49746prjid','ACTIVE','START','Accounts','[{\"expType\":\"MODULE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Industry is equal to Apparel\",\"expValue\":\"Apparel\",\"expOperator\":\"equals\",\"expModule\":\"Accounts\",\"expField\":\"industry\"},{\"expType\":\"LOGIC\",\"expLabel\":\"OR\",\"expValue\":\"OR\"},{\"expType\":\"USER_ROLE\",\"expLabel\":\"Supervisor has not role Administrator\",\"expValue\":\"is_admin\",\"expOperator\":\"not_equals\",\"expField\":\"supervisor\"}]');
        ";
        // $sql_setup[] = "
        //     INSERT INTO `pmse_project` (`id`,`name`,`deleted`,`prj_status`,`prj_module`)
        //     VALUES ('9ff025b6-e576-11e5-9261-fe49746prjid','Test Working Record',0,'ACTIVE','Accounts');
        // ";
        //Create bean
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '9ff025b6-e576-11e5-9261-fe49746prjid';
        $bean->new_with_id = true;
        $bean->name = 'Test Working Record';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('9ff025b6-e576-11e5-9261-fe497468afid',0,'9ff025b6-e576-11e5-9261-fe49746prjid','9ff025b6-e576-11e5-9261-fe497468edid');
        ";
        //broken records
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_event_definition` (`id`,`deleted`,`prj_id`,`evn_status`,`evn_type`,`evn_module`,`evn_criteria`)
            VALUES ('38047c8e-e58c-11e5-9261-fe497468edid',0,'46d69d50-e58c-11e5-9261-fe49746prjid','ACTIVE','START','Accounts','[{\"expType\":\"MODULE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Industry is equal to nonexistantvalue56\",\"expValue\":\"nonexistantvalue56\",\"expOperator\":\"equals\",\"expModule\":\"Accounts\",\"expField\":\"industry\"},{\"expType\":\"LOGIC\",\"expLabel\":\"OR\",\"expValue\":\"OR\"},{\"expType\":\"USER_ROLE\",\"expLabel\":\"Supervisor has not role Administrator\",\"expValue\":\"is_admin\",\"expOperator\":\"not_equals\",\"expField\":\"supervisor\"}]');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_event_definition` (`id`,`deleted`,`prj_id`,`evn_status`,`evn_type`,`evn_module`,`evn_criteria`) 
            VALUES ('3c8704ca-e58c-11e5-9261-fe497468edid',0,'46d69d51-e58c-11e5-9261-fe49746prjid','ACTIVE','START','Accounts','[{\"expType\":\"MODULE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Industry is equal to nonexistantvalue56\",\"expValue\":\"nonexistantvalue56\",\"expOperator\":\"equals\",\"expModule\":\"member_of\",\"expField\":\"industry\"},{\"expType\":\"LOGIC\",\"expLabel\":\"OR\",\"expValue\":\"OR\"},{\"expType\":\"USER_ROLE\",\"expLabel\":\"Supervisor has not role Administrator\",\"expValue\":\"is_admin\",\"expOperator\":\"not_equals\",\"expField\":\"supervisor\"}]');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_event_definition` (`id`,`deleted`,`prj_id`,`evn_status`,`evn_type`,`evn_module`,`evn_criteria`) 
            VALUES ('4290f060-e58c-11e5-9261-fe497468edid',0,'46d69d52-e58c-11e5-9261-fe49746prjid','ACTIVE','START','Accounts','[{\"expType\":\"MODULE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Lead Source is equal to nonexistantvalue56\",\"expValue\":\"nonexistantvalue56\",\"expOperator\":\"equals\",\"expModule\":\"contacts\",\"expField\":\"lead_source\"},{\"expType\":\"LOGIC\",\"expLabel\":\"OR\",\"expValue\":\"OR\"},{\"expType\":\"USER_ROLE\",\"expLabel\":\"Supervisor has not role Administrator\",\"expValue\":\"is_admin\",\"expOperator\":\"not_equals\",\"expField\":\"supervisor\"}]');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_event_definition` (`id`,`deleted`,`prj_id`,`evn_status`,`evn_type`,`evn_module`,`evn_criteria`) 
            VALUES ('46d69d50-e58c-11e5-9261-fe497468edid',0,'46d69d53-e58c-11e5-9261-fe49746prjid','ACTIVE','START','Accounts','[{\"expType\":\"MODULE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"nonexistantfield56 is equal to 3\",\"expValue\":\"3\",\"expOperator\":\"equals\",\"expModule\":\"Accounts\",\"expField\":\"nonexistantfield56_c\"},{\"expType\":\"LOGIC\",\"expLabel\":\"OR\",\"expValue\":\"OR\"},{\"expType\":\"USER_ROLE\",\"expLabel\":\"Supervisor has not role Administrator\",\"expValue\":\"is_admin\",\"expOperator\":\"not_equals\",\"expField\":\"supervisor\"}]');
        ";

        //Create bean
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '46d69d50-e58c-11e5-9261-fe49746prjid';
        $bean->new_with_id = true;
        $bean->name = 'Test DD Field Missing Value';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        //Create bean
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '46d69d51-e58c-11e5-9261-fe49746prjid';
        $bean->new_with_id = true;
        $bean->name = 'Test Self Related DD Field Missing Value';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        //Create bean
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '46d69d52-e58c-11e5-9261-fe49746prjid';
        $bean->new_with_id = true;
        $bean->name = 'Test Related DD Field Missing Value';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        //Create bean
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '46d69d53-e58c-11e5-9261-fe49746prjid';
        $bean->new_with_id = true;
        $bean->name = 'Test Field doesnt Exist';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        // $sql_setup[] = "
        //     INSERT INTO `pmse_project` (`id`,`name`,`deleted`,`prj_status`,`prj_module`)
        //     VALUES ('46d69d50-e58c-11e5-9261-fe49746prjid','Test DD Field Missing Value',0,'ACTIVE','Accounts');
        // ";
        // $sql_setup[] = "
        //     INSERT INTO `pmse_project` (`id`,`name`,`deleted`,`prj_status`,`prj_module`)
        //     VALUES ('46d69d51-e58c-11e5-9261-fe49746prjid','Test Self Related DD Field Missing Value',0,'ACTIVE','Accounts');
        // ";
        // $sql_setup[] = "
        //     INSERT INTO `pmse_project` (`id`,`name`,`deleted`,`prj_status`,`prj_module`)
        //     VALUES ('46d69d52-e58c-11e5-9261-fe49746prjid','Test Related DD Field Missing Value',0,'ACTIVE','Accounts');
        // ";
        // $sql_setup[] = "
        //     INSERT INTO `pmse_project` (`id`,`name`,`deleted`,`prj_status`,`prj_module`)
        //     VALUES ('46d69d53-e58c-11e5-9261-fe49746prjid','Test Field doesnt Exist',0,'ACTIVE','Accounts');
        // ";

        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('8236146e-e58e-11e5-9261-fe497468afid',0,'46d69d50-e58c-11e5-9261-fe49746prjid','38047c8e-e58c-11e5-9261-fe497468edid');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('87a549d8-e58e-11e5-9261-fe497468afid',0,'46d69d51-e58c-11e5-9261-fe49746prjid','3c8704ca-e58c-11e5-9261-fe497468edid');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('8b0b2fde-e58e-11e5-9261-fe497468afid',0,'46d69d52-e58c-11e5-9261-fe49746prjid','4290f060-e58c-11e5-9261-fe497468edid');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('8e736524-e58e-11e5-9261-fe497468afid',0,'46d69d53-e58c-11e5-9261-fe49746prjid','46d69d50-e58c-11e5-9261-fe497468edid');
        ";

        foreach ($sql_setup as $q_setup) {
            $res = $GLOBALS['db']->query($q_setup);
        }

    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        $sql_teardown = array();

        $sql_teardown[] = "
            DELETE FROM pmse_bpm_event_definition
            WHERE id in ('9ff025b6-e576-11e5-9261-fe497468edid','38047c8e-e58c-11e5-9261-fe497468edid','3c8704ca-e58c-11e5-9261-fe497468edid','4290f060-e58c-11e5-9261-fe497468edid','46d69d50-e58c-11e5-9261-fe497468edid')
        ";
        $sql_teardown[] = "
            DELETE FROM pmse_project
            WHERE id in ('9ff025b6-e576-11e5-9261-fe49746prjid','46d69d50-e58c-11e5-9261-fe49746prjid','46d69d51-e58c-11e5-9261-fe49746prjid','46d69d52-e58c-11e5-9261-fe49746prjid','46d69d53-e58c-11e5-9261-fe49746prjid','38c90c70-7788-13a2-668d-513e2b8df5e1')
        ";
        $sql_teardown[] = "
            DELETE FROM pmse_bpmn_flow
            WHERE id in ('9ff025b6-e576-11e5-9261-fe497468afid','8236146e-e58e-11e5-9261-fe497468afid','87a549d8-e58e-11e5-9261-fe497468afid','8b0b2fde-e58e-11e5-9261-fe497468afid','8e736524-e58e-11e5-9261-fe497468afid')
        ";

        foreach ($sql_teardown as $q_teardown) {
            $res = $GLOBALS['db']->query($q_teardown);
        }
    }

    /**
     * Test for setting the new criteria
     * @covers supp_ProcessAuthorRepairs::setEventDefinition
     */
    public function testSetEventDefinition()
    {
        $eventId = "9ff025b6-e576-11e5-9261-fe497468edid";
        $new_evn_criteria = '[{"expType":"MODULE","expSubtype":"DropDown","expLabel":"Industry is equal to "Other"","expValue":"Other","expOperator":"equals","expModule":"Accounts","expField":"industry"}]';

        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $results = $supp_ProcessAuthorRepairsTest->setEventDefinition($eventId, $new_evn_criteria);

        // should return true
        $this->assertTrue($results);

        $sql = "
            SELECT evn_criteria 
            FROM pmse_bpm_event_definition
            WHERE id = '$eventId'
        ";
        $returnedCriteria = html_entity_decode($GLOBALS['db']->getOne($sql));

        // should return updated criteria
        $this->assertEquals($new_evn_criteria, $returnedCriteria);
    }

    /**
     * Test for disabling a process author definition
     * @covers supp_Repairs::disablePADefinition
     */
    public function testDisablePADefinition()
    {
        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $supp_ProcessAuthorRepairsTest->disablePADefinition("38c90c70-7788-13a2-668d-513e2b8df5e1");

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "38c90c70-7788-13a2-668d-513e2b8df5e1");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);
    }

    /**
     * Test for fixing start criteria
     * @covers supp_ProcessAuthorRepairs::repairEventCriteria
     */
    public function testRepairEventCriteria()
    {
        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $supp_ProcessAuthorRepairsTest->repairEventCriteria();

        // 4 broken records should be issues
        $this->assertGreaterThanOrEqual(4, count($supp_ProcessAuthorRepairsTest->foundIssues));

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "46d69d50-e58c-11e5-9261-fe49746prjid");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "46d69d51-e58c-11e5-9261-fe49746prjid");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "46d69d52-e58c-11e5-9261-fe49746prjid");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "46d69d53-e58c-11e5-9261-fe49746prjid");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "9ff025b6-e576-11e5-9261-fe49746prjid");
        $this->assertEquals("ACTIVE", $paDefinition->prj_status);
    }
}
