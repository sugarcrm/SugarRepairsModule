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

        $repairs = new supp_ProcessAuthorRepairs();
        if (!$repairs->isEnt() && !$repairs->isUlt()) {
            return;
        }

        $sql_setup = array();

        // bean for disabling definition
        // testDisablePADefinition
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '38c90c70-7788-13a2-668d-513e2b8df5e1';
        $bean->new_with_id = true;
        $bean->name = 'Example Record';
        $bean->prj_status = "ACTIVE";
        $bean->save();

        // event test records
        // testSetEventDefinition
        // testRepairEventCriteria (false positive test)
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '9ff025b6-e576-11e5-9261-fe49746prjid';
        $bean->new_with_id = true;
        $bean->name = 'Test Working Record for Start Event';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_bpm_event_definition` (`id`,`deleted`,`prj_id`,`evn_status`,`evn_type`,`evn_module`,`evn_criteria`)
            VALUES ('9ff025b6-e576-11e5-9261-fe497468edid',0,'9ff025b6-e576-11e5-9261-fe49746prjid','ACTIVE','START','Accounts','[{\"expType\":\"MODULE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Industry is equal to Apparel\",\"expValue\":\"Apparel\",\"expOperator\":\"equals\",\"expModule\":\"Accounts\",\"expField\":\"industry\"},{\"expType\":\"LOGIC\",\"expLabel\":\"OR\",\"expValue\":\"OR\"},{\"expType\":\"USER_ROLE\",\"expLabel\":\"Supervisor has not role Administrator\",\"expValue\":\"is_admin\",\"expOperator\":\"not_equals\",\"expField\":\"supervisor\"}]');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_related_dependency` (`id`, `evn_id`,`deleted`,`prj_id`,`evn_status`,`evn_type`,`evn_module`,`evn_criteria`)
            VALUES ('7d8a4f58-131e-11e6-bb94-80f85ad93479', '9ff025b6-e576-11e5-9261-fe497468edid',0,'9ff025b6-e576-11e5-9261-fe49746prjid','ACTIVE','START','Accounts','[{\"expType\":\"MODULE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Industry is equal to Apparel\",\"expValue\":\"Apparel\",\"expOperator\":\"equals\",\"expModule\":\"Accounts\",\"expField\":\"industry\"},{\"expType\":\"LOGIC\",\"expLabel\":\"OR\",\"expValue\":\"OR\"},{\"expType\":\"USER_ROLE\",\"expLabel\":\"Supervisor has not role Administrator\",\"expValue\":\"is_admin\",\"expOperator\":\"not_equals\",\"expField\":\"supervisor\"}]');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('9ff025b6-e576-11e5-9261-fe497468afid',0,'9ff025b6-e576-11e5-9261-fe49746prjid','9ff025b6-e576-11e5-9261-fe497468edid');
        ";

        // testRepairEventCriteria
        // dropdown missing a value in event criteria
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '46d69d50-e58c-11e5-9261-fe49746prjid';
        $bean->new_with_id = true;
        $bean->name = 'Test DD Field Missing Value';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_bpm_event_definition` (`id`,`deleted`,`prj_id`,`evn_status`,`evn_type`,`evn_module`,`evn_criteria`)
            VALUES ('38047c8e-e58c-11e5-9261-fe497468edid',0,'46d69d50-e58c-11e5-9261-fe49746prjid','ACTIVE','START','Accounts','[{\"expType\":\"MODULE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Industry is equal to nonexistantvalue56\",\"expValue\":\"nonexistantvalue56\",\"expOperator\":\"equals\",\"expModule\":\"Accounts\",\"expField\":\"industry\"},{\"expType\":\"LOGIC\",\"expLabel\":\"OR\",\"expValue\":\"OR\"},{\"expType\":\"USER_ROLE\",\"expLabel\":\"Supervisor has not role Administrator\",\"expValue\":\"is_admin\",\"expOperator\":\"not_equals\",\"expField\":\"supervisor\"}]');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_related_dependency` (`id`, `evn_id`,`deleted`,`prj_id`,`evn_status`,`evn_type`,`evn_module`,`evn_criteria`)
            VALUES ('8214af6e-131e-11e6-bb94-80f85ad93479', '38047c8e-e58c-11e5-9261-fe497468edid',0,'46d69d50-e58c-11e5-9261-fe49746prjid','ACTIVE','START','Accounts','[{\"expType\":\"MODULE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Industry is equal to nonexistantvalue56\",\"expValue\":\"nonexistantvalue56\",\"expOperator\":\"equals\",\"expModule\":\"Accounts\",\"expField\":\"industry\"},{\"expType\":\"LOGIC\",\"expLabel\":\"OR\",\"expValue\":\"OR\"},{\"expType\":\"USER_ROLE\",\"expLabel\":\"Supervisor has not role Administrator\",\"expValue\":\"is_admin\",\"expOperator\":\"not_equals\",\"expField\":\"supervisor\"}]');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('8236146e-e58e-11e5-9261-fe497468afid',0,'46d69d50-e58c-11e5-9261-fe49746prjid','38047c8e-e58c-11e5-9261-fe497468edid');
        ";

        // self related dropdown missing a value in event criteria
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '46d69d51-e58c-11e5-9261-fe49746prjid';
        $bean->new_with_id = true;
        $bean->name = 'Test Self Related DD Field Missing Value';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_bpm_event_definition` (`id`,`deleted`,`prj_id`,`evn_status`,`evn_type`,`evn_module`,`evn_criteria`)
            VALUES ('3c8704ca-e58c-11e5-9261-fe497468edid',0,'46d69d51-e58c-11e5-9261-fe49746prjid','ACTIVE','START','Accounts','[{\"expType\":\"MODULE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Industry is equal to nonexistantvalue56\",\"expValue\":\"nonexistantvalue56\",\"expOperator\":\"equals\",\"expModule\":\"member_of\",\"expField\":\"industry\"},{\"expType\":\"LOGIC\",\"expLabel\":\"OR\",\"expValue\":\"OR\"},{\"expType\":\"USER_ROLE\",\"expLabel\":\"Supervisor has not role Administrator\",\"expValue\":\"is_admin\",\"expOperator\":\"not_equals\",\"expField\":\"supervisor\"}]');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_related_dependency` (`id`, `evn_id`,`deleted`,`prj_id`,`evn_status`,`evn_type`,`evn_module`,`evn_criteria`)
            VALUES ('84de2a72-131e-11e6-bb94-80f85ad93479', '3c8704ca-e58c-11e5-9261-fe497468edid',0,'46d69d51-e58c-11e5-9261-fe49746prjid','ACTIVE','START','Accounts','[{\"expType\":\"MODULE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Industry is equal to nonexistantvalue56\",\"expValue\":\"nonexistantvalue56\",\"expOperator\":\"equals\",\"expModule\":\"member_of\",\"expField\":\"industry\"},{\"expType\":\"LOGIC\",\"expLabel\":\"OR\",\"expValue\":\"OR\"},{\"expType\":\"USER_ROLE\",\"expLabel\":\"Supervisor has not role Administrator\",\"expValue\":\"is_admin\",\"expOperator\":\"not_equals\",\"expField\":\"supervisor\"}]');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('87a549d8-e58e-11e5-9261-fe497468afid',0,'46d69d51-e58c-11e5-9261-fe49746prjid','3c8704ca-e58c-11e5-9261-fe497468edid');
        ";

        // related dropdown missing a value in event criteria
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '46d69d52-e58c-11e5-9261-fe49746prjid';
        $bean->new_with_id = true;
        $bean->name = 'Test Related DD Field Missing Value';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_bpm_event_definition` (`id`,`deleted`,`prj_id`,`evn_status`,`evn_type`,`evn_module`,`evn_criteria`)
            VALUES ('4290f060-e58c-11e5-9261-fe497468edid',0,'46d69d52-e58c-11e5-9261-fe49746prjid','ACTIVE','START','Accounts','[{\"expType\":\"MODULE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Lead Source is equal to nonexistantvalue56\",\"expValue\":\"nonexistantvalue56\",\"expOperator\":\"equals\",\"expModule\":\"contacts\",\"expField\":\"lead_source\"},{\"expType\":\"LOGIC\",\"expLabel\":\"OR\",\"expValue\":\"OR\"},{\"expType\":\"USER_ROLE\",\"expLabel\":\"Supervisor has not role Administrator\",\"expValue\":\"is_admin\",\"expOperator\":\"not_equals\",\"expField\":\"supervisor\"}]');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_related_dependency` (`id`, `evn_id`,`deleted`,`prj_id`,`evn_status`,`evn_type`,`evn_module`,`evn_criteria`)
            VALUES ('87e29cee-131e-11e6-bb94-80f85ad93479', '4290f060-e58c-11e5-9261-fe497468edid',0,'46d69d52-e58c-11e5-9261-fe49746prjid','ACTIVE','START','Accounts','[{\"expType\":\"MODULE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Lead Source is equal to nonexistantvalue56\",\"expValue\":\"nonexistantvalue56\",\"expOperator\":\"equals\",\"expModule\":\"contacts\",\"expField\":\"lead_source\"},{\"expType\":\"LOGIC\",\"expLabel\":\"OR\",\"expValue\":\"OR\"},{\"expType\":\"USER_ROLE\",\"expLabel\":\"Supervisor has not role Administrator\",\"expValue\":\"is_admin\",\"expOperator\":\"not_equals\",\"expField\":\"supervisor\"}]');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('8b0b2fde-e58e-11e5-9261-fe497468afid',0,'46d69d52-e58c-11e5-9261-fe49746prjid','4290f060-e58c-11e5-9261-fe497468edid');
        ";

        // non-existant field in event criteria
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '46d69d53-e58c-11e5-9261-fe49746prjid';
        $bean->new_with_id = true;
        $bean->name = 'Test Field doesnt Exist';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_bpm_event_definition` (`id`,`deleted`,`prj_id`,`evn_status`,`evn_type`,`evn_module`,`evn_criteria`)
            VALUES ('46d69d50-e58c-11e5-9261-fe497468edid',0,'46d69d53-e58c-11e5-9261-fe49746prjid','ACTIVE','START','Accounts','[{\"expType\":\"MODULE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"nonexistantfield56 is equal to 3\",\"expValue\":\"3\",\"expOperator\":\"equals\",\"expModule\":\"Accounts\",\"expField\":\"nonexistantfield56_c\"},{\"expType\":\"LOGIC\",\"expLabel\":\"OR\",\"expValue\":\"OR\"},{\"expType\":\"USER_ROLE\",\"expLabel\":\"Supervisor has not role Administrator\",\"expValue\":\"is_admin\",\"expOperator\":\"not_equals\",\"expField\":\"supervisor\"}]');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_related_dependency` (`id`, `evn_id`,`deleted`,`prj_id`,`evn_status`,`evn_type`,`evn_module`,`evn_criteria`)
            VALUES ('8c1b021a-131e-11e6-bb94-80f85ad93479', '46d69d50-e58c-11e5-9261-fe497468edid',0,'46d69d53-e58c-11e5-9261-fe49746prjid','ACTIVE','START','Accounts','[{\"expType\":\"MODULE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"nonexistantfield56 is equal to 3\",\"expValue\":\"3\",\"expOperator\":\"equals\",\"expModule\":\"Accounts\",\"expField\":\"nonexistantfield56_c\"},{\"expType\":\"LOGIC\",\"expLabel\":\"OR\",\"expValue\":\"OR\"},{\"expType\":\"USER_ROLE\",\"expLabel\":\"Supervisor has not role Administrator\",\"expValue\":\"is_admin\",\"expOperator\":\"not_equals\",\"expField\":\"supervisor\"}]');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('8e736524-e58e-11e5-9261-fe497468afid',0,'46d69d53-e58c-11e5-9261-fe49746prjid','46d69d50-e58c-11e5-9261-fe497468edid');
        ";

        // non START or END event
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = 'b25f1060-f1ef-11e5-a564-2c531c938b02';
        $bean->new_with_id = true;
        $bean->name = 'Test Record for non Start/End Event';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_bpm_event_definition` (`id`,`deleted`,`prj_id`,`evn_status`,`evn_type`,`evn_module`,`evn_criteria`)
            VALUES ('ea9db4a4-f1ef-11e5-a564-2c531c938b02',0,'b25f1060-f1ef-11e5-a564-2c531c938b02','ACTIVE','START','Accounts','');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_related_dependency` (`id`, `evn_id`,`deleted`,`prj_id`,`evn_status`,`evn_type`,`evn_module`,`evn_criteria`)
            VALUES ('8ff59e90-131e-11e6-bb94-80f85ad93479', 'ea9db4a4-f1ef-11e5-a564-2c531c938b02',0,'b25f1060-f1ef-11e5-a564-2c531c938b02','ACTIVE','START','Accounts','');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_event_definition` (`id`,`deleted`,`prj_id`,`evn_status`,`evn_type`,`evn_module`,`evn_criteria`)
            VALUES ('31366564-f1f0-11e5-a564-2c531c938b02',0,'b25f1060-f1ef-11e5-a564-2c531c938b02','ACTIVE','INTERMEDIATE','Accounts','2b9633a0-f1f0-11e5-a564-2c531c938b02');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_related_dependency` (`id`, `evn_id`,`deleted`,`prj_id`,`evn_status`,`evn_type`,`evn_module`,`evn_criteria`)
            VALUES ('92f08498-131e-11e6-bb94-80f85ad93479', '31366564-f1f0-11e5-a564-2c531c938b02',0,'b25f1060-f1ef-11e5-a564-2c531c938b02','ACTIVE','INTERMEDIATE','Accounts','2b9633a0-f1f0-11e5-a564-2c531c938b02');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('f725c6ee-f1ef-11e5-a564-2c531c938b02',0,'b25f1060-f1ef-11e5-a564-2c531c938b02','ea9db4a4-f1ef-11e5-a564-2c531c938b02');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('a0077b0e-f1f0-11e5-a564-2c531c938b02',0,'b25f1060-f1ef-11e5-a564-2c531c938b02','f725c6ee-f1ef-11e5-a564-2c531c938b02');
        ";

        // action test records
        // testSetActionDefinition
        // testRepairActivities (false positive test)
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '06082fac-ebca-11e5-a19f-342d44d047f0';
        $bean->new_with_id = true;
        $bean->name = 'Test Working Record for Set Field Action';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_bpm_activity_definition` (`id`,`deleted`,`name`,`act_field_module`,`act_fields`)
            VALUES ('f6c394c0-eb0e-11e5-b792-460e741c2f98',0,'Change Field','Accounts','[{\"name\":\"Industry\",\"field\":\"industry\",\"value\":\"Apparel\",\"type\":\"DropDown\"},{\"name\":\"Type\",\"field\":\"account_type\",\"value\":\"Analyst\",\"type\":\"DropDown\"},{\"name\":\"Website\",\"field\":\"website\",\"value\":\"test\",\"type\":\"URL\"}]');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_activity` (`id`,`name`,`deleted`,`prj_id`,`act_task_type`,`act_script_type`)
            VALUES ('f6c394c0-eb0e-11e5-b792-460e741c2f98','Change Field',0,'06082fac-ebca-11e5-a19f-342d44d047f0','SCRIPTTASK','CHANGE_FIELD');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('647e62a2-ec6c-11e5-a19f-342d44d047f0',0,'06082fac-ebca-11e5-a19f-342d44d047f0','f6c394c0-eb0e-11e5-b792-460e741c2f98');
        ";

        // testRepairActivities

        // required dropdown field on activity form
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '4a40ed2a-ebcb-11e5-a19f-342d44d047f0';
        $bean->new_with_id = true;
        $bean->name = 'required dropdown field on activity form';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_bpm_activity_definition` (`id`,`deleted`,`name`,`act_required_fields`)
            VALUES ('df2b96fe-ebcd-11e5-a19f-342d44d047f0',0,'Required Field Form','WyJpbmR1c3RyeSIsInJhdGluZyIsIm5vbmV4aXN0YW50ZmllbGQ1Nl9jIl0=');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_activity` (`id`,`name`,`deleted`,`prj_id`,`act_task_type`,`act_script_type`)
            VALUES ('df2b96fe-ebcd-11e5-a19f-342d44d047f0','Required Field Form',0,'4a40ed2a-ebcb-11e5-a19f-342d44d047f0','USERTASK','');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('501d46cc-ec6f-11e5-a19f-342d44d047f0',0,'4a40ed2a-ebcb-11e5-a19f-342d44d047f0','df2b96fe-ebcd-11e5-a19f-342d44d047f0');
        ";

        // add related record action with dropdown field that doesnt exist
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '06877022-ebcb-11e5-a19f-342d44d047f0';
        $bean->new_with_id = true;
        $bean->name = 'add related record action with dropdown field that doesnt exist';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_bpm_activity_definition` (`id`,`deleted`,`name`,`act_field_module`,`act_fields`)
            VALUES ('ff2b4dee-ebca-11e5-a19f-342d44d047f0',0,'Add Related Record','leads','[{\"name\":\"Last Name\",\"field\":\"last_name\",\"value\":\"testerson\",\"type\":\"TextField\"},{\"name\":\"Assigned to\",\"field\":\"assigned_user_id\",\"value\":\"currentuser\",\"type\":\"user\",\"label\":\"Current user\"},{\"name\":\"Fake Field\",\"field\":\"nonexistantfield56_c\",\"value\":\"\",\"type\":\"DropDown\"},{\"name\":\"Status\",\"field\":\"status\",\"value\":\"Assigned\",\"type\":\"DropDown\"},{\"name\":\"Website\",\"field\":\"website\",\"value\":\"\",\"type\":\"URL\"}]');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_activity` (`id`,`name`,`deleted`,`prj_id`,`act_task_type`,`act_script_type`)
            VALUES ('ff2b4dee-ebca-11e5-a19f-342d44d047f0','Add Related Record',0,'06877022-ebcb-11e5-a19f-342d44d047f0','SCRIPTTASK','ADD_RELATED_RECORD');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('b159c37a-ec6f-11e5-a19f-342d44d047f0',0,'06877022-ebcb-11e5-a19f-342d44d047f0','ff2b4dee-ebca-11e5-a19f-342d44d047f0');
        ";

        // add related record action with dropdown field value not in list
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '68dd07cc-ec71-11e5-a19f-342d44d047f0';
        $bean->new_with_id = true;
        $bean->name = 'add related record action with dropdown field value not in list';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_bpm_activity_definition` (`id`,`deleted`,`name`,`act_field_module`,`act_fields`)
            VALUES ('ac2e2510-ec71-11e5-a19f-342d44d047f0',0,'Add Related Record','leads','[{\"name\":\"Last Name\",\"field\":\"last_name\",\"value\":\"testerson\",\"type\":\"TextField\"},{\"name\":\"Assigned to\",\"field\":\"assigned_user_id\",\"value\":\"currentuser\",\"type\":\"user\",\"label\":\"Current user\"},{\"name\":\"Lead Source\",\"field\":\"lead_source\",\"value\":\"nonexistantvalue56\",\"type\":\"DropDown\"},{\"name\":\"Status\",\"field\":\"status\",\"value\":\"Assigned\",\"type\":\"DropDown\"},{\"name\":\"Website\",\"field\":\"website\",\"value\":\"\",\"type\":\"URL\"}]');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_activity` (`id`,`name`,`deleted`,`prj_id`,`act_task_type`,`act_script_type`)
            VALUES ('ac2e2510-ec71-11e5-a19f-342d44d047f0','Add Related Record',0,'68dd07cc-ec71-11e5-a19f-342d44d047f0','SCRIPTTASK','ADD_RELATED_RECORD');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('bbdffe84-ec71-11e5-a19f-342d44d047f0',0,'68dd07cc-ec71-11e5-a19f-342d44d047f0','ac2e2510-ec71-11e5-a19f-342d44d047f0');
        ";

        // add self-related record action with dropdown field that doesnt exist
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = 'ce60db0a-ec71-11e5-a19f-342d44d047f0';
        $bean->new_with_id = true;
        $bean->name = 'add self-related record action with dropdown field that doesnt exist';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_bpm_activity_definition` (`id`,`deleted`,`name`,`act_field_module`,`act_fields`)
            VALUES ('76106654-ec72-11e5-a19f-342d44d047f0',0,'Add Related Record','members','[{\"name\":\"Assigned to\",\"field\":\"assigned_user_id\",\"value\":\"currentuser\",\"type\":\"user\",\"label\":\"Current user\"},{\"name\":\"Fake Field\",\"field\":\"nonexistantfield56_c\",\"value\":\"Banking\",\"type\":\"DropDown\"},{\"name\":\"Name\",\"field\":\"name\",\"value\":\"test\",\"type\":\"Name\"},{\"name\":\"Website\",\"field\":\"website\",\"value\":\"\",\"type\":\"URL\"}]');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_activity` (`id`,`name`,`deleted`,`prj_id`,`act_task_type`,`act_script_type`)
            VALUES ('76106654-ec72-11e5-a19f-342d44d047f0','Add Related Record',0,'ce60db0a-ec71-11e5-a19f-342d44d047f0','SCRIPTTASK','ADD_RELATED_RECORD');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('81828b52-ec72-11e5-a19f-342d44d047f0',0,'ce60db0a-ec71-11e5-a19f-342d44d047f0','76106654-ec72-11e5-a19f-342d44d047f0');
        ";

        // add self-related record action with dropdown field value not in list
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = 'ba6b467a-ec72-11e5-a19f-342d44d047f0';
        $bean->new_with_id = true;
        $bean->name = 'add self-related record action with dropdown field value not in list';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_bpm_activity_definition` (`id`,`deleted`,`name`,`act_field_module`,`act_fields`)
            VALUES ('ca9cdf4a-ec72-11e5-a19f-342d44d047f0',0,'Add Related Record','members','[{\"name\":\"Assigned to\",\"field\":\"assigned_user_id\",\"value\":\"currentuser\",\"type\":\"user\",\"label\":\"Current user\"},{\"name\":\"Industry\",\"field\":\"industry\",\"value\":\"nonexistantvalue56\",\"type\":\"DropDown\"},{\"name\":\"Name\",\"field\":\"name\",\"value\":\"test\",\"type\":\"Name\"},{\"name\":\"Website\",\"field\":\"website\",\"value\":\"\",\"type\":\"URL\"}]');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_activity` (`id`,`name`,`deleted`,`prj_id`,`act_task_type`,`act_script_type`)
            VALUES ('ca9cdf4a-ec72-11e5-a19f-342d44d047f0','Add Related Record',0,'ba6b467a-ec72-11e5-a19f-342d44d047f0','SCRIPTTASK','ADD_RELATED_RECORD');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('d46a161e-ec72-11e5-a19f-342d44d047f0',0,'ba6b467a-ec72-11e5-a19f-342d44d047f0','ca9cdf4a-ec72-11e5-a19f-342d44d047f0');
        ";

        // change field in current module with dropdown field that doesnt exist
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '9dd09126-ec88-11e5-a19f-342d44d047f0';
        $bean->new_with_id = true;
        $bean->name = 'change field in current module with dropdown field that doesnt exist';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_bpm_activity_definition` (`id`,`deleted`,`name`,`act_field_module`,`act_fields`)
            VALUES ('ea7732fa-ec88-11e5-a19f-342d44d047f0',0,'Add Related Record','Accounts','[{\"name\":\"Fake Field\",\"field\":\"nonexistantfield56_c\",\"value\":\"Apparel\",\"type\":\"DropDown\"},{\"name\":\"Type\",\"field\":\"account_type\",\"value\":\"Analyst\",\"type\":\"DropDown\"},{\"name\":\"Website\",\"field\":\"website\",\"value\":\"test\",\"type\":\"URL\"}]');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_activity` (`id`,`name`,`deleted`,`prj_id`,`act_task_type`,`act_script_type`)
            VALUES ('ea7732fa-ec88-11e5-a19f-342d44d047f0','Add Related Record',0,'9dd09126-ec88-11e5-a19f-342d44d047f0','SCRIPTTASK','CHANGE_FIELD');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('f0816562-ec88-11e5-a19f-342d44d047f0',0,'9dd09126-ec88-11e5-a19f-342d44d047f0','ea7732fa-ec88-11e5-a19f-342d44d047f0');
        ";

        // change field in current module with dropdown field value not in list
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '04509716-ec89-11e5-a19f-342d44d047f0';
        $bean->new_with_id = true;
        $bean->name = 'change field in current module with dropdown field value not in list';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_bpm_activity_definition` (`id`,`deleted`,`name`,`act_field_module`,`act_fields`)
            VALUES ('24df04c2-ec89-11e5-a19f-342d44d047f0',0,'Add Related Record','Accounts','[{\"name\":\"Industry\",\"field\":\"industry\",\"value\":\"nonexistantvalue56\",\"type\":\"DropDown\"},{\"name\":\"Type\",\"field\":\"account_type\",\"value\":\"Analyst\",\"type\":\"DropDown\"},{\"name\":\"Website\",\"field\":\"website\",\"value\":\"test\",\"type\":\"URL\"}]');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_activity` (`id`,`name`,`deleted`,`prj_id`,`act_task_type`,`act_script_type`)
            VALUES ('24df04c2-ec89-11e5-a19f-342d44d047f0','Add Related Record',0,'04509716-ec89-11e5-a19f-342d44d047f0','SCRIPTTASK','CHANGE_FIELD');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('2c9b2e3e-ec89-11e5-a19f-342d44d047f0',0,'04509716-ec89-11e5-a19f-342d44d047f0','24df04c2-ec89-11e5-a19f-342d44d047f0');
        ";

        // change field in related module with dropdown field that doesnt exist
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '64dc5b98-ec88-11e5-a19f-342d44d047f0';
        $bean->new_with_id = true;
        $bean->name = 'change field in related module with dropdown field that doesnt exist';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_bpm_activity_definition` (`id`,`deleted`,`name`,`act_field_module`,`act_fields`)
            VALUES ('73d6b15c-ec88-11e5-a19f-342d44d047f0',0,'Add Related Record','campaign_accounts','[{\"name\":\"Description \",\"field\":\"content\",\"value\":\"test\",\"type\":\"TextArea\"},{\"name\":\"Fake Field\",\"field\":\"nonexistantfield56_c\",\"value\":\"Inactive\",\"type\":\"DropDown\"}]');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_activity` (`id`,`name`,`deleted`,`prj_id`,`act_task_type`,`act_script_type`)
            VALUES ('73d6b15c-ec88-11e5-a19f-342d44d047f0','Add Related Record',0,'64dc5b98-ec88-11e5-a19f-342d44d047f0','SCRIPTTASK','CHANGE_FIELD');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('7a2f4c80-ec88-11e5-a19f-342d44d047f0',0,'64dc5b98-ec88-11e5-a19f-342d44d047f0','73d6b15c-ec88-11e5-a19f-342d44d047f0');
        ";

        // change field in related module with dropdown field value not in list
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '4a3ec220-ec89-11e5-a19f-342d44d047f0';
        $bean->new_with_id = true;
        $bean->name = 'change field in related module with dropdown field value not in list';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_bpm_activity_definition` (`id`,`deleted`,`name`,`act_field_module`,`act_fields`)
            VALUES ('53999002-ec89-11e5-a19f-342d44d047f0',0,'Add Related Record','campaign_accounts','[{\"name\":\"Description \",\"field\":\"content\",\"value\":\"test\",\"type\":\"TextArea\"},{\"name\":\"Status\",\"field\":\"status\",\"value\":\"nonexistantvalue56\",\"type\":\"DropDown\"}]');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_activity` (`id`,`name`,`deleted`,`prj_id`,`act_task_type`,`act_script_type`)
            VALUES ('53999002-ec89-11e5-a19f-342d44d047f0','Add Related Record',0,'4a3ec220-ec89-11e5-a19f-342d44d047f0','SCRIPTTASK','CHANGE_FIELD');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('7d4f167e-ec89-11e5-a19f-342d44d047f0',0,'4a3ec220-ec89-11e5-a19f-342d44d047f0','53999002-ec89-11e5-a19f-342d44d047f0');
        ";

        // change field in self related module with dropdown field that doesnt exist
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = 'cc88007a-ec89-11e5-a19f-342d44d047f0';
        $bean->new_with_id = true;
        $bean->name = 'change field in self related module with dropdown field that doesnt exist';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_bpm_activity_definition` (`id`,`deleted`,`name`,`act_field_module`,`act_fields`)
            VALUES ('589b3654-ec8a-11e5-a19f-342d44d047f0',0,'Add Related Record','member_of','[{\"name\":\"Fake Field\",\"field\":\"nonexistantfield56_c\",\"value\":\"Apparel\",\"type\":\"DropDown\"},{\"name\":\"Type\",\"field\":\"account_type\",\"value\":\"Analyst\",\"type\":\"DropDown\"},{\"name\":\"Website\",\"field\":\"website\",\"value\":\"test\",\"type\":\"URL\"}]');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_activity` (`id`,`name`,`deleted`,`prj_id`,`act_task_type`,`act_script_type`)
            VALUES ('589b3654-ec8a-11e5-a19f-342d44d047f0','Add Related Record',0,'cc88007a-ec89-11e5-a19f-342d44d047f0','SCRIPTTASK','CHANGE_FIELD');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('738f31f4-ec8a-11e5-a19f-342d44d047f0',0,'cc88007a-ec89-11e5-a19f-342d44d047f0','589b3654-ec8a-11e5-a19f-342d44d047f0');
        ";

        // change field in self related module with dropdown field value not in list
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = 'b8ea5314-ec8a-11e5-a19f-342d44d047f0';
        $bean->new_with_id = true;
        $bean->name = 'change field in self related module with dropdown field value not in list';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_bpm_activity_definition` (`id`,`deleted`,`name`,`act_field_module`,`act_fields`)
            VALUES ('bf8bef84-ec8a-11e5-a19f-342d44d047f0',0,'Add Related Record','member_of','[{\"name\":\"Industry\",\"field\":\"industry\",\"value\":\"nonexistantvalue56\",\"type\":\"DropDown\"},{\"name\":\"Type\",\"field\":\"account_type\",\"value\":\"Analyst\",\"type\":\"DropDown\"},{\"name\":\"Website\",\"field\":\"website\",\"value\":\"test\",\"type\":\"URL\"}]');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_activity` (`id`,`name`,`deleted`,`prj_id`,`act_task_type`,`act_script_type`)
            VALUES ('bf8bef84-ec8a-11e5-a19f-342d44d047f0','Add Related Record',0,'b8ea5314-ec8a-11e5-a19f-342d44d047f0','SCRIPTTASK','CHANGE_FIELD');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('c5dc7228-ec8a-11e5-a19f-342d44d047f0',0,'b8ea5314-ec8a-11e5-a19f-342d44d047f0','bf8bef84-ec8a-11e5-a19f-342d44d047f0');
        ";

        // business rules test records
        // setBusinessRuleDefinition
        // testRepairBusinessRules (false positive test)
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '0ac139be-ed3f-11e5-94a1-736088870fb3';
        $bean->new_with_id = true;
        $bean->name = 'Test Working Record for Set Business Rules';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_business_rules` (`id`,`deleted`,`name`,`rst_module`,`rst_source_definition`)
            VALUES ('646a7084-ed3f-11e5-94a1-736088870fb3',0,'Business Rule','Accounts','{\"id\":\"646a7084-ed3f-11e5-94a1-736088870fb3\",\"base_module\":\"Accounts\",\"type\":\"single\",\"name\":\"Business Rule\",\"columns\":{\"conditions\":[{\"module\":\"Accounts\",\"field\":\"industry\"},{\"module\":\"campaign_accounts\",\"field\":\"status\"}],\"conclusions\":[\"\",\"industry\"]},\"ruleset\":[{\"id\":1,\"conditions\":[{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"Apparel\",\"expValue\":\"Apparel\"}],\"variable_name\":\"industry\",\"condition\":\"==\",\"variable_module\":\"Accounts\"},{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"Active\",\"expValue\":\"Active\"}],\"variable_name\":\"status\",\"condition\":\"==\",\"variable_module\":\"campaign_accounts\"}],\"conclusions\":[{\"value\":[{\"expType\":\"CONSTANT\",\"expSubtype\":\"boolean\",\"expLabel\":\"FALSE\",\"expValue\":false}],\"conclusion_value\":\"result\",\"conclusion_type\":\"return\"},{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"Apparel\",\"expValue\":\"Apparel\"}],\"conclusion_value\":\"industry\",\"conclusion_type\":\"variable\",\"variable_module\":\"Accounts\"}]},{\"id\":2,\"conditions\":[{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"Banking\",\"expValue\":\"Banking\"}],\"variable_name\":\"industry\",\"condition\":\"==\",\"variable_module\":\"Accounts\"}],\"conclusions\":[{\"value\":[{\"expType\":\"VARIABLE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Industry\",\"expValue\":\"industry\",\"expModule\":\"Accounts\"},{\"expType\":\"VARIABLE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Type\",\"expValue\":\"account_type\",\"expModule\":\"Accounts\"}],\"conclusion_value\":\"result\",\"conclusion_type\":\"return\"},{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"Banking\",\"expValue\":\"Banking\"}],\"conclusion_value\":\"industry\",\"conclusion_type\":\"variable\",\"variable_module\":\"Accounts\"}]}]}');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_activity_definition` (`id`,`deleted`,`name`,`act_field_module`,`act_fields`)
            VALUES ('3665150e-ed3f-11e5-94a1-736088870fb3',0,'Business Rule','Accounts','646a7084-ed3f-11e5-94a1-736088870fb3');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_activity` (`id`,`name`,`deleted`,`prj_id`,`act_task_type`,`act_script_type`)
            VALUES ('3665150e-ed3f-11e5-94a1-736088870fb3','Business Rule',0,'0ac139be-ed3f-11e5-94a1-736088870fb3','SCRIPTTASK','BUSINESS_RULE');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('3e9eab68-ed3f-11e5-94a1-736088870fb3',0,'0ac139be-ed3f-11e5-94a1-736088870fb3','3665150e-ed3f-11e5-94a1-736088870fb3');
        ";

        // business rule with condition field, current module that does not exist
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '2f6b6d54-ed51-11e5-94a1-736088870fb3';
        $bean->new_with_id = true;
        $bean->name = 'business rule with condition field that does not exist';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_business_rules` (`id`,`deleted`,`name`,`rst_module`,`rst_source_definition`)
            VALUES ('43c6cc8a-ed51-11e5-94a1-736088870fb3',0,'Business Rule','Accounts','{\"id\":\"43c6cc8a-ed51-11e5-94a1-736088870fb3\",\"base_module\":\"Accounts\",\"type\":\"single\",\"name\":\"test2\",\"columns\":{\"conditions\":[{\"module\":\"Accounts\",\"field\":\"industry\"}],\"conclusions\":[\"\"]},\"ruleset\":[{\"id\":1,\"conditions\":[{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"Biotechnology\",\"expValue\":\"Biotechnology\"}],\"variable_name\":\"nonexistantfield56_c\",\"condition\":\"==\",\"variable_module\":\"Accounts\"}],\"conclusions\":[{\"value\":[{\"expType\":\"CONSTANT\",\"expSubtype\":\"boolean\",\"expLabel\":\"TRUE\",\"expValue\":true}],\"conclusion_value\":\"result\",\"conclusion_type\":\"return\"}]}]}');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_activity_definition` (`id`,`deleted`,`name`,`act_field_module`,`act_fields`)
            VALUES ('67ca2172-ed51-11e5-94a1-736088870fb3',0,'Business Rule','Accounts','43c6cc8a-ed51-11e5-94a1-736088870fb3');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_activity` (`id`,`name`,`deleted`,`prj_id`,`act_task_type`,`act_script_type`)
            VALUES ('67ca2172-ed51-11e5-94a1-736088870fb3','Business Rule',0,'2f6b6d54-ed51-11e5-94a1-736088870fb3','SCRIPTTASK','BUSINESS_RULE');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('6f669ba4-ed51-11e5-94a1-736088870fb3',0,'2f6b6d54-ed51-11e5-94a1-736088870fb3','67ca2172-ed51-11e5-94a1-736088870fb3');
        ";

        // business rule with condition field, current module using invalid dropdown value
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '800eda32-ed5d-11e5-94a1-736088870fb3';
        $bean->new_with_id = true;
        $bean->name = 'business rule with condition field that does not exist';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_business_rules` (`id`,`deleted`,`name`,`rst_module`,`rst_source_definition`)
            VALUES ('89f53bcc-ed5d-11e5-94a1-736088870fb3',0,'Business Rule','Accounts','{\"id\":\"89f53bcc-ed5d-11e5-94a1-736088870fb3\",\"base_module\":\"Accounts\",\"type\":\"single\",\"name\":\"test2\",\"columns\":{\"conditions\":[{\"module\":\"Accounts\",\"field\":\"industry\"}],\"conclusions\":[\"\"]},\"ruleset\":[{\"id\":1,\"conditions\":[{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"nonexistantvalue56\",\"expValue\":\"nonexistantvalue56\"}],\"variable_name\":\"industry\",\"condition\":\"==\",\"variable_module\":\"Accounts\"}],\"conclusions\":[{\"value\":[{\"expType\":\"CONSTANT\",\"expSubtype\":\"boolean\",\"expLabel\":\"TRUE\",\"expValue\":true}],\"conclusion_value\":\"result\",\"conclusion_type\":\"return\"}]}]}');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_activity_definition` (`id`,`deleted`,`name`,`act_field_module`,`act_fields`)
            VALUES ('93bfc62c-ed5d-11e5-94a1-736088870fb3',0,'Business Rule','Accounts','89f53bcc-ed5d-11e5-94a1-736088870fb3');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_activity` (`id`,`name`,`deleted`,`prj_id`,`act_task_type`,`act_script_type`)
            VALUES ('93bfc62c-ed5d-11e5-94a1-736088870fb3','Business Rule',0,'800eda32-ed5d-11e5-94a1-736088870fb3','SCRIPTTASK','BUSINESS_RULE');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('9bc11498-ed5d-11e5-94a1-736088870fb3',0,'800eda32-ed5d-11e5-94a1-736088870fb3','93bfc62c-ed5d-11e5-94a1-736088870fb3');
        ";

        // business rule with conclusion field, current module, return type, that does not exist
        //{\"id\":\"37c7741d-be97-1706-0925-56ec89c09827\",\"base_module\":\"Accounts\",\"type\":\"single\",\"name\":\"conclusion\",\"columns\":{\"conditions\":[{\"module\":\"Accounts\",\"field\":\"billing_address_city\"}],\"conclusions\":[\"\",\"industry\",\"account_type\"]},\"ruleset\":[{\"id\":1,\"conditions\":[{\"value\":[{\"expType\":\"CONSTANT\",\"expSubtype\":\"string\",\"expLabel\":\"\\"test\\"\",\"expValue\":\"test\"}],\"variable_name\":\"billing_address_city\",\"condition\":\"==\",\"variable_module\":\"Accounts\"}],\"conclusions\":[{\"value\":[{\"expType\":\"VARIABLE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Industry\",\"expValue\":\"industry\",\"expModule\":\"Accounts\"},{\"expType\":\"VARIABLE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Type\",\"expValue\":\"nonexistantfield56_c\",\"expModule\":\"Accounts\"}],\"conclusion_value\":\"result\",\"conclusion_type\":\"return\"},{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"Chemicals\",\"expValue\":\"Chemicals\"}],\"conclusion_value\":\"industry\",\"conclusion_type\":\"variable\",\"variable_module\":\"Accounts\"},{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"Prospect\",\"expValue\":\"Prospect\"}],\"conclusion_value\":\"account_type\",\"conclusion_type\":\"variable\",\"variable_module\":\"Accounts\"}]}]}
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '7bf5370a-ed5f-11e5-94a1-736088870fb3';
        $bean->new_with_id = true;
        $bean->name = 'business rule with conclusion field, current module, return type, that does not exist';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_business_rules` (`id`,`deleted`,`name`,`rst_module`,`rst_source_definition`)
            VALUES ('8ae94de6-ed5f-11e5-94a1-736088870fb3',0,'Business Rule','Accounts','{\"id\":\"8ae94de6-ed5f-11e5-94a1-736088870fb3\",\"base_module\":\"Accounts\",\"type\":\"single\",\"name\":\"conclusion\",\"columns\":{\"conditions\":[{\"module\":\"Accounts\",\"field\":\"billing_address_city\"}],\"conclusions\":[\"\",\"industry\",\"account_type\"]},\"ruleset\":[{\"id\":1,\"conditions\":[{\"value\":[{\"expType\":\"CONSTANT\",\"expSubtype\":\"string\",\"expLabel\":\"\",\"expValue\":\"test\"}],\"variable_name\":\"billing_address_city\",\"condition\":\"==\",\"variable_module\":\"Accounts\"}],\"conclusions\":[{\"value\":[{\"expType\":\"VARIABLE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Industry\",\"expValue\":\"industry\",\"expModule\":\"Accounts\"},{\"expType\":\"VARIABLE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Type\",\"expValue\":\"nonexistantfield56_c\",\"expModule\":\"Accounts\"}],\"conclusion_value\":\"result\",\"conclusion_type\":\"return\"},{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"Chemicals\",\"expValue\":\"Chemicals\"}],\"conclusion_value\":\"industry\",\"conclusion_type\":\"variable\",\"variable_module\":\"Accounts\"},{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"Prospect\",\"expValue\":\"Prospect\"}],\"conclusion_value\":\"account_type\",\"conclusion_type\":\"variable\",\"variable_module\":\"Accounts\"}]}]}');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_activity_definition` (`id`,`deleted`,`name`,`act_field_module`,`act_fields`)
            VALUES ('a8de2e7a-ed5f-11e5-94a1-736088870fb3',0,'Business Rule','Accounts','8ae94de6-ed5f-11e5-94a1-736088870fb3');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_activity` (`id`,`name`,`deleted`,`prj_id`,`act_task_type`,`act_script_type`)
            VALUES ('a8de2e7a-ed5f-11e5-94a1-736088870fb3','Business Rule',0,'7bf5370a-ed5f-11e5-94a1-736088870fb3','SCRIPTTASK','BUSINESS_RULE');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('afd6f716-ed5f-11e5-94a1-736088870fb3',0,'7bf5370a-ed5f-11e5-94a1-736088870fb3','a8de2e7a-ed5f-11e5-94a1-736088870fb3');
        ";

        // business rule with conclusion field, current module, variable type, that does not exist
        //{\"id\":\"37c7741d-be97-1706-0925-56ec89c09827\",\"base_module\":\"Accounts\",\"type\":\"single\",\"name\":\"conclusion\",\"columns\":{\"conditions\":[{\"module\":\"Accounts\",\"field\":\"billing_address_city\"}],\"conclusions\":[\"\",\"industry\",\"account_type\"]},\"ruleset\":[{\"id\":1,\"conditions\":[{\"value\":[{\"expType\":\"CONSTANT\",\"expSubtype\":\"string\",\"expLabel\":\"\\"test\\"\",\"expValue\":\"test\"}],\"variable_name\":\"billing_address_city\",\"condition\":\"==\",\"variable_module\":\"Accounts\"}],\"conclusions\":[{\"value\":[{\"expType\":\"VARIABLE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Industry\",\"expValue\":\"industry\",\"expModule\":\"Accounts\"},{\"expType\":\"VARIABLE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Type\",\"expValue\":\"account_type\",\"expModule\":\"Accounts\"}],\"conclusion_value\":\"result\",\"conclusion_type\":\"return\"},{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"Chemicals\",\"expValue\":\"Chemicals\"}],\"conclusion_value\":\"industry\",\"conclusion_type\":\"variable\",\"variable_module\":\"Accounts\"},{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"Prospect\",\"expValue\":\"Prospect\"}],\"conclusion_value\":\"nonexistantfield56_c\",\"conclusion_type\":\"variable\",\"variable_module\":\"Accounts\"}]}]}
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = 'f6440928-ed5f-11e5-94a1-736088870fb3';
        $bean->new_with_id = true;
        $bean->name = 'business rule with conclusion field, current module, variable type, that does not exist';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_business_rules` (`id`,`deleted`,`name`,`rst_module`,`rst_source_definition`)
            VALUES ('09c552d6-ed60-11e5-94a1-736088870fb3',0,'Business Rule','Accounts','{\"id\":\"09c552d6-ed60-11e5-94a1-736088870fb3\",\"base_module\":\"Accounts\",\"type\":\"single\",\"name\":\"conclusion\",\"columns\":{\"conditions\":[{\"module\":\"Accounts\",\"field\":\"billing_address_city\"}],\"conclusions\":[\"\",\"industry\",\"account_type\"]},\"ruleset\":[{\"id\":1,\"conditions\":[{\"value\":[{\"expType\":\"CONSTANT\",\"expSubtype\":\"string\",\"expLabel\":\"\",\"expValue\":\"test\"}],\"variable_name\":\"billing_address_city\",\"condition\":\"==\",\"variable_module\":\"Accounts\"}],\"conclusions\":[{\"value\":[{\"expType\":\"VARIABLE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Industry\",\"expValue\":\"industry\",\"expModule\":\"Accounts\"},{\"expType\":\"VARIABLE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Type\",\"expValue\":\"account_type\",\"expModule\":\"Accounts\"}],\"conclusion_value\":\"result\",\"conclusion_type\":\"return\"},{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"Chemicals\",\"expValue\":\"Chemicals\"}],\"conclusion_value\":\"industry\",\"conclusion_type\":\"variable\",\"variable_module\":\"Accounts\"},{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"Prospect\",\"expValue\":\"Prospect\"}],\"conclusion_value\":\"nonexistantfield56_c\",\"conclusion_type\":\"variable\",\"variable_module\":\"Accounts\"}]}]}');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_activity_definition` (`id`,`deleted`,`name`,`act_field_module`,`act_fields`)
            VALUES ('169952fa-ed60-11e5-94a1-736088870fb3',0,'Business Rule','Accounts','09c552d6-ed60-11e5-94a1-736088870fb3');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_activity` (`id`,`name`,`deleted`,`prj_id`,`act_task_type`,`act_script_type`)
            VALUES ('169952fa-ed60-11e5-94a1-736088870fb3','Business Rule',0,'f6440928-ed5f-11e5-94a1-736088870fb3','SCRIPTTASK','BUSINESS_RULE');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('1df4cc8c-ed60-11e5-94a1-736088870fb3',0,'f6440928-ed5f-11e5-94a1-736088870fb3','169952fa-ed60-11e5-94a1-736088870fb3');
        ";

        // business rule with conclusion field, current module, variable type, using invalid dropdown value
        //{\"id\":\"37c7741d-be97-1706-0925-56ec89c09827\",\"base_module\":\"Accounts\",\"type\":\"single\",\"name\":\"conclusion\",\"columns\":{\"conditions\":[{\"module\":\"Accounts\",\"field\":\"billing_address_city\"}],\"conclusions\":[\"\",\"industry\",\"account_type\"]},\"ruleset\":[{\"id\":1,\"conditions\":[{\"value\":[{\"expType\":\"CONSTANT\",\"expSubtype\":\"string\",\"expLabel\":\"\\"test\\"\",\"expValue\":\"test\"}],\"variable_name\":\"billing_address_city\",\"condition\":\"==\",\"variable_module\":\"Accounts\"}],\"conclusions\":[{\"value\":[{\"expType\":\"VARIABLE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Industry\",\"expValue\":\"industry\",\"expModule\":\"Accounts\"},{\"expType\":\"VARIABLE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Type\",\"expValue\":\"account_type\",\"expModule\":\"Accounts\"}],\"conclusion_value\":\"result\",\"conclusion_type\":\"return\"},{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"Chemicals\",\"expValue\":\"Chemicals\"}],\"conclusion_value\":\"industry\",\"conclusion_type\":\"variable\",\"variable_module\":\"Accounts\"},{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"nonexistantvalue56\",\"expValue\":\"nonexistantvalue56\"}],\"conclusion_value\":\"account_type\",\"conclusion_type\":\"variable\",\"variable_module\":\"Accounts\"}]}]}
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '7222b6a2-ed60-11e5-94a1-736088870fb3';
        $bean->new_with_id = true;
        $bean->name = 'business rule with conclusion field, current module, variable type, using invalid dropdown value ';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_business_rules` (`id`,`deleted`,`name`,`rst_module`,`rst_source_definition`)
            VALUES ('7abf6440-ed60-11e5-94a1-736088870fb3',0,'Business Rule','Accounts','{\"id\":\"7abf6440-ed60-11e5-94a1-736088870fb3\",\"base_module\":\"Accounts\",\"type\":\"single\",\"name\":\"conclusion\",\"columns\":{\"conditions\":[{\"module\":\"Accounts\",\"field\":\"billing_address_city\"}],\"conclusions\":[\"\",\"industry\",\"account_type\"]},\"ruleset\":[{\"id\":1,\"conditions\":[{\"value\":[{\"expType\":\"CONSTANT\",\"expSubtype\":\"string\",\"expLabel\":\"\",\"expValue\":\"test\"}],\"variable_name\":\"billing_address_city\",\"condition\":\"==\",\"variable_module\":\"Accounts\"}],\"conclusions\":[{\"value\":[{\"expType\":\"VARIABLE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Industry\",\"expValue\":\"industry\",\"expModule\":\"Accounts\"},{\"expType\":\"VARIABLE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Type\",\"expValue\":\"account_type\",\"expModule\":\"Accounts\"}],\"conclusion_value\":\"result\",\"conclusion_type\":\"return\"},{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"Chemicals\",\"expValue\":\"Chemicals\"}],\"conclusion_value\":\"industry\",\"conclusion_type\":\"variable\",\"variable_module\":\"Accounts\"},{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"nonexistantvalue56\",\"expValue\":\"nonexistantvalue56\"}],\"conclusion_value\":\"account_type\",\"conclusion_type\":\"variable\",\"variable_module\":\"Accounts\"}]}]}');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_activity_definition` (`id`,`deleted`,`name`,`act_field_module`,`act_fields`)
            VALUES ('85811180-ed60-11e5-94a1-736088870fb3',0,'Business Rule','Accounts','7abf6440-ed60-11e5-94a1-736088870fb3');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_activity` (`id`,`name`,`deleted`,`prj_id`,`act_task_type`,`act_script_type`)
            VALUES ('85811180-ed60-11e5-94a1-736088870fb3','Business Rule',0,'7222b6a2-ed60-11e5-94a1-736088870fb3','SCRIPTTASK','BUSINESS_RULE');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('8cffbfba-ed60-11e5-94a1-736088870fb3',0,'7222b6a2-ed60-11e5-94a1-736088870fb3','85811180-ed60-11e5-94a1-736088870fb3');
        ";

        // business rule with condition field, related module that does not exist
        //{"id":"52547811-b7e9-5b7a-6247-56ec988cb1bd","base_module":"Accounts","type":"single","name":"related br","columns":{"conditions":[{"module":"campaign_accounts","field":"status"},{"module":"Accounts","field":"industry"}],"conclusions":[""]},"ruleset":[{"id":1,"conditions":[{"value":[{"expType":"CONSTANT","expSubType":"string","expLabel":"In Queue","expValue":"In Queue"}],"variable_name":"nonexistantfield56_c","condition":"==","variable_module":"campaign_accounts"},{"value":[{"expType":"CONSTANT","expSubType":"string","expLabel":"Chemicals","expValue":"Chemicals"}],"variable_name":"industry","condition":"==","variable_module":"Accounts"}],"conclusions":[{"value":[{"expType":"CONSTANT","expSubtype":"boolean","expLabel":"TRUE","expValue":true}],"conclusion_value":"result","conclusion_type":"return"}]}]}
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = 'd79ee4d6-ed67-11e5-94a1-736088870fb3';
        $bean->new_with_id = true;
        $bean->name = 'business rule with condition field, related module that does not exist';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_business_rules` (`id`,`deleted`,`name`,`rst_module`,`rst_source_definition`)
            VALUES ('df46a8c2-ed67-11e5-94a1-736088870fb3',0,'Business Rule','Accounts','{\"id\":\"df46a8c2-ed67-11e5-94a1-736088870fb3\",\"base_module\":\"Accounts\",\"type\":\"single\",\"name\":\"related br\",\"columns\":{\"conditions\":[{\"module\":\"campaign_accounts\",\"field\":\"status\"},{\"module\":\"Accounts\",\"field\":\"industry\"}],\"conclusions\":[\"\"]},\"ruleset\":[{\"id\":1,\"conditions\":[{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"In Queue\",\"expValue\":\"In Queue\"}],\"variable_name\":\"nonexistantfield56_c\",\"condition\":\"==\",\"variable_module\":\"campaign_accounts\"},{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"Chemicals\",\"expValue\":\"Chemicals\"}],\"variable_name\":\"industry\",\"condition\":\"==\",\"variable_module\":\"Accounts\"}],\"conclusions\":[{\"value\":[{\"expType\":\"CONSTANT\",\"expSubtype\":\"boolean\",\"expLabel\":\"TRUE\",\"expValue\":true}],\"conclusion_value\":\"result\",\"conclusion_type\":\"return\"}]}]}');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_activity_definition` (`id`,`deleted`,`name`,`act_field_module`,`act_fields`)
            VALUES ('e9c27a1a-ed67-11e5-94a1-736088870fb3',0,'Business Rule','Accounts','df46a8c2-ed67-11e5-94a1-736088870fb3');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_activity` (`id`,`name`,`deleted`,`prj_id`,`act_task_type`,`act_script_type`)
            VALUES ('e9c27a1a-ed67-11e5-94a1-736088870fb3','Business Rule',0,'d79ee4d6-ed67-11e5-94a1-736088870fb3','SCRIPTTASK','BUSINESS_RULE');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('f076cadc-ed67-11e5-94a1-736088870fb3',0,'d79ee4d6-ed67-11e5-94a1-736088870fb3','e9c27a1a-ed67-11e5-94a1-736088870fb3');
        ";

        // business rule with condition field, related module using invalid dropdown value
        // {"id":"52547811-b7e9-5b7a-6247-56ec988cb1bd","base_module":"Accounts","type":"single","name":"related br","columns":{"conditions":[{"module":"campaign_accounts","field":"status"},{"module":"Accounts","field":"industry"}],"conclusions":[""]},"ruleset":[{"id":1,"conditions":[{"value":[{"expType":"CONSTANT","expSubType":"string","expLabel":"In Queue","expValue":"nonexistantvalue"}],"variable_name":"status","condition":"==","variable_module":"campaign_accounts"},{"value":[{"expType":"CONSTANT","expSubType":"string","expLabel":"Chemicals","expValue":"Chemicals"}],"variable_name":"industry","condition":"==","variable_module":"Accounts"}],"conclusions":[{"value":[{"expType":"CONSTANT","expSubtype":"boolean","expLabel":"TRUE","expValue":true}],"conclusion_value":"result","conclusion_type":"return"}]}]}
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = '6376436e-ed68-11e5-94a1-736088870fb3';
        $bean->new_with_id = true;
        $bean->name = 'business rule with condition field, related module using invalid dropdown value ';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_business_rules` (`id`,`deleted`,`name`,`rst_module`,`rst_source_definition`)
            VALUES ('6902404e-ed68-11e5-94a1-736088870fb3',0,'Business Rule','Accounts','{\"id\":\"6902404e-ed68-11e5-94a1-736088870fb3\",\"base_module\":\"Accounts\",\"type\":\"single\",\"name\":\"related br\",\"columns\":{\"conditions\":[{\"module\":\"campaign_accounts\",\"field\":\"status\"},{\"module\":\"Accounts\",\"field\":\"industry\"}],\"conclusions\":[\"\"]},\"ruleset\":[{\"id\":1,\"conditions\":[{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"In Queue\",\"expValue\":\"nonexistantvalue\"}],\"variable_name\":\"status\",\"condition\":\"==\",\"variable_module\":\"campaign_accounts\"},{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"Chemicals\",\"expValue\":\"Chemicals\"}],\"variable_name\":\"industry\",\"condition\":\"==\",\"variable_module\":\"Accounts\"}],\"conclusions\":[{\"value\":[{\"expType\":\"CONSTANT\",\"expSubtype\":\"boolean\",\"expLabel\":\"TRUE\",\"expValue\":true}],\"conclusion_value\":\"result\",\"conclusion_type\":\"return\"}]}]}');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_activity_definition` (`id`,`deleted`,`name`,`act_field_module`,`act_fields`)
            VALUES ('7a801436-ed68-11e5-94a1-736088870fb3',0,'Business Rule','Accounts','6902404e-ed68-11e5-94a1-736088870fb3');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_activity` (`id`,`name`,`deleted`,`prj_id`,`act_task_type`,`act_script_type`)
            VALUES ('7a801436-ed68-11e5-94a1-736088870fb3','Business Rule',0,'6376436e-ed68-11e5-94a1-736088870fb3','SCRIPTTASK','BUSINESS_RULE');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('809c659a-ed68-11e5-94a1-736088870fb3',0,'6376436e-ed68-11e5-94a1-736088870fb3','7a801436-ed68-11e5-94a1-736088870fb3');
        ";

        // business rule with conclusion field, related module, return type, that does not exist
        // {"id":"7de80e8d-5699-5aef-2528-56ec99144830","base_module":"Accounts","type":"single","name":"related coclusion","columns":{"conditions":[{"module":"Accounts","field":"industry"}],"conclusions":["","industry"]},"ruleset":[{"id":1,"conditions":[{"value":[{"expType":"CONSTANT","expSubType":"string","expLabel":"Apparel","expValue":"Apparel"}],"variable_name":"industry","condition":"==","variable_module":"Accounts"}],"conclusions":[{"value":[{"expType":"VARIABLE","expSubtype":"DropDown","expLabel":"Status","expValue":"nonexistantfield56_c","expModule":"campaign_accounts"},{"expType":"VARIABLE","expSubtype":"DropDown","expLabel":"Type","expValue":"campaign_type","expModule":"campaign_accounts"}],"conclusion_value":"result","conclusion_type":"return"},{"value":[{"expType":"CONSTANT","expSubType":"string","expLabel":"Apparel","expValue":"Apparel"}],"conclusion_value":"industry","conclusion_type":"variable","variable_module":"Accounts"}]}]}
        $bean = BeanFactory::newBean("pmse_Project");
        $bean->id = 'c0e866a8-ed68-11e5-94a1-736088870fb3';
        $bean->new_with_id = true;
        $bean->name = 'business rule with conclusion field, related module, return type, that does not exist';
        $bean->prj_status = "ACTIVE";
        $bean->prj_module = "Accounts";
        $bean->save();

        $sql_setup[] = "
            INSERT INTO `pmse_business_rules` (`id`,`deleted`,`name`,`rst_module`,`rst_source_definition`)
            VALUES ('c85934b2-ed68-11e5-94a1-736088870fb3',0,'Business Rule','Accounts','{\"id\":\"c85934b2-ed68-11e5-94a1-736088870fb3\",\"base_module\":\"Accounts\",\"type\":\"single\",\"name\":\"related coclusion\",\"columns\":{\"conditions\":[{\"module\":\"Accounts\",\"field\":\"industry\"}],\"conclusions\":[\"\",\"industry\"]},\"ruleset\":[{\"id\":1,\"conditions\":[{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"Apparel\",\"expValue\":\"Apparel\"}],\"variable_name\":\"industry\",\"condition\":\"==\",\"variable_module\":\"Accounts\"}],\"conclusions\":[{\"value\":[{\"expType\":\"VARIABLE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Status\",\"expValue\":\"nonexistantfield56_c\",\"expModule\":\"campaign_accounts\"},{\"expType\":\"VARIABLE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Type\",\"expValue\":\"campaign_type\",\"expModule\":\"campaign_accounts\"}],\"conclusion_value\":\"result\",\"conclusion_type\":\"return\"},{\"value\":[{\"expType\":\"CONSTANT\",\"expSubType\":\"string\",\"expLabel\":\"Apparel\",\"expValue\":\"Apparel\"}],\"conclusion_value\":\"industry\",\"conclusion_type\":\"variable\",\"variable_module\":\"Accounts\"}]}]}');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_activity_definition` (`id`,`deleted`,`name`,`act_field_module`,`act_fields`)
            VALUES ('d2a0a644-ed68-11e5-94a1-736088870fb3',0,'Business Rule','Accounts','c85934b2-ed68-11e5-94a1-736088870fb3');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_activity` (`id`,`name`,`deleted`,`prj_id`,`act_task_type`,`act_script_type`)
            VALUES ('d2a0a644-ed68-11e5-94a1-736088870fb3','Business Rule',0,'c0e866a8-ed68-11e5-94a1-736088870fb3','SCRIPTTASK','BUSINESS_RULE');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpmn_flow` (`id`,`deleted`,`prj_id`,`flo_element_origin`)
            VALUES ('db853c48-ed68-11e5-94a1-736088870fb3',0,'c0e866a8-ed68-11e5-94a1-736088870fb3','d2a0a644-ed68-11e5-94a1-736088870fb3');
        ";

        // records for blacklisted functions to update
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_event_definition` (`id`,`deleted`,`prj_id`,`evn_status`,`evn_type`,`evn_module`,`evn_criteria`)
            VALUES ('43c9fefc-12f2-11e6-bb94-80f85ad93479',0,'9ff025b6-e576-11e5-9261-fe49746prjid','ACTIVE','START','Accounts','test');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_related_dependency` (`id`, `evn_id`,`deleted`,`prj_id`,`evn_status`,`evn_type`,`evn_module`,`evn_criteria`)
            VALUES ('6d544a1a-1320-11e6-bb94-80f85ad93479', '43c9fefc-12f2-11e6-bb94-80f85ad93479',0,'9ff025b6-e576-11e5-9261-fe49746prjid','ACTIVE','START','Accounts','test');
        ";

        $sql_setup[] = "
            INSERT INTO `pmse_bpm_activity_definition` (`id`,`deleted`,`name`,`act_field_module`,`act_fields`)
            VALUES ('b37672ac-12f3-11e6-bb94-80f85ad93479',0,'Change Field','Accounts','test');
        ";

        $sql_setup[] = "
            INSERT INTO `pmse_business_rules` (`id`,`deleted`,`name`,`rst_module`,`rst_source_definition`)
            VALUES ('d27a7a0e-12f3-11e6-bb94-80f85ad93479',0,'Business Rule','Accounts','test');
        ";
        $sql_setup[] = "
            INSERT INTO `pmse_bpm_activity_definition` (`id`,`deleted`,`name`,`act_field_module`,`act_fields`)
            VALUES ('e26e474c-12f3-11e6-bb94-80f85ad93479',0,'Change Field','Accounts','test');
        ";

        // test deleteBusinessRuleDefinition
        $sql_setup[] = "
            INSERT INTO `pmse_business_rules` (`id`,`deleted`,`name`,`rst_module`,`rst_source_definition`)
            VALUES ('4aa147ca-1325-11e6-bb94-80f85ad93479',0,'Business Rule','Accounts','test');
        ";

        // execute sql statements
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
            WHERE id in (
                '9ff025b6-e576-11e5-9261-fe497468edid',
                '38047c8e-e58c-11e5-9261-fe497468edid',
                '3c8704ca-e58c-11e5-9261-fe497468edid',
                '4290f060-e58c-11e5-9261-fe497468edid',
                '46d69d50-e58c-11e5-9261-fe497468edid',
                'ea9db4a4-f1ef-11e5-a564-2c531c938b02',
                '31366564-f1f0-11e5-a564-2c531c938b02',
                '43c9fefc-12f2-11e6-bb94-80f85ad93479'
            )
        ";
        $sql_teardown[] = "
            DELETE FROM pmse_bpm_related_dependency
            WHERE id in (
                '7d8a4f58-131e-11e6-bb94-80f85ad93479',
                '8214af6e-131e-11e6-bb94-80f85ad93479',
                '84de2a72-131e-11e6-bb94-80f85ad93479',
                '87e29cee-131e-11e6-bb94-80f85ad93479',
                '8c1b021a-131e-11e6-bb94-80f85ad93479',
                '8ff59e90-131e-11e6-bb94-80f85ad93479',
                '92f08498-131e-11e6-bb94-80f85ad93479',
                '6d544a1a-1320-11e6-bb94-80f85ad93479'
            )
        ";
        $sql_teardown[] = "
            DELETE FROM pmse_project
            WHERE id in (
                '38c90c70-7788-13a2-668d-513e2b8df5e1',
                '9ff025b6-e576-11e5-9261-fe49746prjid',
                '46d69d50-e58c-11e5-9261-fe49746prjid',
                '46d69d51-e58c-11e5-9261-fe49746prjid',
                '46d69d52-e58c-11e5-9261-fe49746prjid',
                '46d69d53-e58c-11e5-9261-fe49746prjid',
                '06082fac-ebca-11e5-a19f-342d44d047f0',
                '4a40ed2a-ebcb-11e5-a19f-342d44d047f0',
                '06877022-ebcb-11e5-a19f-342d44d047f0',
                '68dd07cc-ec71-11e5-a19f-342d44d047f0',
                'ce60db0a-ec71-11e5-a19f-342d44d047f0',
                'ba6b467a-ec72-11e5-a19f-342d44d047f0',
                '9dd09126-ec88-11e5-a19f-342d44d047f0',
                '04509716-ec89-11e5-a19f-342d44d047f0',
                '64dc5b98-ec88-11e5-a19f-342d44d047f0',
                '4a3ec220-ec89-11e5-a19f-342d44d047f0',
                'cc88007a-ec89-11e5-a19f-342d44d047f0',
                'b8ea5314-ec8a-11e5-a19f-342d44d047f0',
                '0ac139be-ed3f-11e5-94a1-736088870fb3',
                '2f6b6d54-ed51-11e5-94a1-736088870fb3',
                '800eda32-ed5d-11e5-94a1-736088870fb3',
                '7bf5370a-ed5f-11e5-94a1-736088870fb3',
                'f6440928-ed5f-11e5-94a1-736088870fb3',
                '7222b6a2-ed60-11e5-94a1-736088870fb3',
                'd79ee4d6-ed67-11e5-94a1-736088870fb3',
                '6376436e-ed68-11e5-94a1-736088870fb3',
                'c0e866a8-ed68-11e5-94a1-736088870fb3',
                'b25f1060-f1ef-11e5-a564-2c531c938b02'
            )
        ";
        $sql_teardown[] = "
            DELETE FROM pmse_bpmn_flow
            WHERE id in (
                '9ff025b6-e576-11e5-9261-fe497468afid',
                '8236146e-e58e-11e5-9261-fe497468afid',
                '87a549d8-e58e-11e5-9261-fe497468afid',
                '8b0b2fde-e58e-11e5-9261-fe497468afid',
                '8e736524-e58e-11e5-9261-fe497468afid',
                '647e62a2-ec6c-11e5-a19f-342d44d047f0',
                '501d46cc-ec6f-11e5-a19f-342d44d047f0',
                'b159c37a-ec6f-11e5-a19f-342d44d047f0',
                'bbdffe84-ec71-11e5-a19f-342d44d047f0',
                '81828b52-ec72-11e5-a19f-342d44d047f0',
                'd46a161e-ec72-11e5-a19f-342d44d047f0',
                'f0816562-ec88-11e5-a19f-342d44d047f0',
                '2c9b2e3e-ec89-11e5-a19f-342d44d047f0',
                '7a2f4c80-ec88-11e5-a19f-342d44d047f0',
                '7d4f167e-ec89-11e5-a19f-342d44d047f0',
                '738f31f4-ec8a-11e5-a19f-342d44d047f0',
                'c5dc7228-ec8a-11e5-a19f-342d44d047f0',
                '3e9eab68-ed3f-11e5-94a1-736088870fb3',
                '6f669ba4-ed51-11e5-94a1-736088870fb3',
                '9bc11498-ed5d-11e5-94a1-736088870fb3',
                'afd6f716-ed5f-11e5-94a1-736088870fb3',
                '1df4cc8c-ed60-11e5-94a1-736088870fb3',
                '8cffbfba-ed60-11e5-94a1-736088870fb3',
                'f076cadc-ed67-11e5-94a1-736088870fb3',
                '809c659a-ed68-11e5-94a1-736088870fb3',
                'db853c48-ed68-11e5-94a1-736088870fb3',
                'f725c6ee-f1ef-11e5-a564-2c531c938b02',
                'a0077b0e-f1f0-11e5-a564-2c531c938b02'
            )
        ";

        $sql_teardown[] = "
            DELETE FROM pmse_bpm_activity_definition
            WHERE id in (
                'f6c394c0-eb0e-11e5-b792-460e741c2f98',
                'df2b96fe-ebcd-11e5-a19f-342d44d047f0',
                'ff2b4dee-ebca-11e5-a19f-342d44d047f0',
                'ac2e2510-ec71-11e5-a19f-342d44d047f0',
                '76106654-ec72-11e5-a19f-342d44d047f0',
                'ca9cdf4a-ec72-11e5-a19f-342d44d047f0',
                'ea7732fa-ec88-11e5-a19f-342d44d047f0',
                '24df04c2-ec89-11e5-a19f-342d44d047f0',
                '73d6b15c-ec88-11e5-a19f-342d44d047f0',
                '53999002-ec89-11e5-a19f-342d44d047f0',
                '589b3654-ec8a-11e5-a19f-342d44d047f0',
                'bf8bef84-ec8a-11e5-a19f-342d44d047f0',
                '3665150e-ed3f-11e5-94a1-736088870fb3',
                '67ca2172-ed51-11e5-94a1-736088870fb3',
                '93bfc62c-ed5d-11e5-94a1-736088870fb3',
                'a8de2e7a-ed5f-11e5-94a1-736088870fb3',
                '169952fa-ed60-11e5-94a1-736088870fb3',
                '85811180-ed60-11e5-94a1-736088870fb3',
                'e9c27a1a-ed67-11e5-94a1-736088870fb3',
                '7a801436-ed68-11e5-94a1-736088870fb3',
                'd2a0a644-ed68-11e5-94a1-736088870fb3',
                'b37672ac-12f3-11e6-bb94-80f85ad93479',
                'e26e474c-12f3-11e6-bb94-80f85ad93479'
            )
        ";

        $sql_teardown[] = "
            DELETE FROM pmse_bpmn_activity
            WHERE id in (
                'f6c394c0-eb0e-11e5-b792-460e741c2f98',
                'df2b96fe-ebcd-11e5-a19f-342d44d047f0',
                'ff2b4dee-ebca-11e5-a19f-342d44d047f0',
                'ac2e2510-ec71-11e5-a19f-342d44d047f0',
                '76106654-ec72-11e5-a19f-342d44d047f0',
                'ca9cdf4a-ec72-11e5-a19f-342d44d047f0',
                'ea7732fa-ec88-11e5-a19f-342d44d047f0',
                '24df04c2-ec89-11e5-a19f-342d44d047f0',
                '73d6b15c-ec88-11e5-a19f-342d44d047f0',
                '53999002-ec89-11e5-a19f-342d44d047f0',
                '589b3654-ec8a-11e5-a19f-342d44d047f0',
                'bf8bef84-ec8a-11e5-a19f-342d44d047f0',
                '3665150e-ed3f-11e5-94a1-736088870fb3',
                '67ca2172-ed51-11e5-94a1-736088870fb3',
                '93bfc62c-ed5d-11e5-94a1-736088870fb3',
                'a8de2e7a-ed5f-11e5-94a1-736088870fb3',
                '169952fa-ed60-11e5-94a1-736088870fb3',
                '85811180-ed60-11e5-94a1-736088870fb3',
                'e9c27a1a-ed67-11e5-94a1-736088870fb3',
                '7a801436-ed68-11e5-94a1-736088870fb3',
                'd2a0a644-ed68-11e5-94a1-736088870fb3'
            )
        ";

        $sql_teardown[] = "
            DELETE FROM pmse_business_rules
            WHERE id in (
                '646a7084-ed3f-11e5-94a1-736088870fb3',
                '43c6cc8a-ed51-11e5-94a1-736088870fb3',
                '89f53bcc-ed5d-11e5-94a1-736088870fb3',
                '8ae94de6-ed5f-11e5-94a1-736088870fb3',
                '09c552d6-ed60-11e5-94a1-736088870fb3',
                '7abf6440-ed60-11e5-94a1-736088870fb3',
                'df46a8c2-ed67-11e5-94a1-736088870fb3',
                '6902404e-ed68-11e5-94a1-736088870fb3',
                'c85934b2-ed68-11e5-94a1-736088870fb3',
                'd27a7a0e-12f3-11e6-bb94-80f85ad93479',
                '4aa147ca-1325-11e6-bb94-80f85ad93479'
            )
        ";


        foreach ($sql_teardown as $q_teardown) {
            $res = $GLOBALS['db']->query($q_teardown);
        }
    }

    /**
     * Test for disabling a process author definition
     * @covers supp_Repairs::disablePADefinition
     */
    public function testDisablePADefinition()
    {
        $repairs = new supp_ProcessAuthorRepairs();
        if (!$repairs->isEnt() && !$repairs->isUlt()) {
            $this->markTestSkipped('Skipping test');
            return;
        }

        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $supp_ProcessAuthorRepairsTest->disablePADefinition("38c90c70-7788-13a2-668d-513e2b8df5e1");

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "38c90c70-7788-13a2-668d-513e2b8df5e1");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);
    }

    /**
     * Test for setting the new event definition
     * @covers supp_ProcessAuthorRepairs::setEventDefinition
     */
    public function testSetEventDefinition()
    {
        $repairs = new supp_ProcessAuthorRepairs();
        if (!$repairs->isEnt() && !$repairs->isUlt()) {
            $this->markTestSkipped('Skipping test');
            return;
        }

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
     * Test for setting the new action definition
     * @covers supp_ProcessAuthorRepairs::setActionDefinition
     */
    public function testSetActionDefinition()
    {
        $repairs = new supp_ProcessAuthorRepairs();
        if (!$repairs->isEnt() && !$repairs->isUlt()) {
            $this->markTestSkipped('Skipping test');
            return;
        }

        $actionId = "f6c394c0-eb0e-11e5-b792-460e741c2f98";
        $new_action_fields = '[{"name":"Industry","field":"industry","value":"Apparel","type":"DropDown"},{"name":"Website","field":"website","value":"test","type":"URL"}]';

        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $results = $supp_ProcessAuthorRepairsTest->setActionDefinition($actionId, $new_action_fields);

        // should return true
        $this->assertTrue($results);

        $sql = "
            SELECT act_fields
            FROM pmse_bpm_activity_definition
            WHERE id = '$actionId'
        ";
        $returnedCriteria = html_entity_decode($GLOBALS['db']->getOne($sql));

        // should return updated criteria
        $this->assertEquals($new_action_fields, $returnedCriteria);
    }

    /**
     * Test for setting the new business rule definition
     * @covers supp_ProcessAuthorRepairs::setBusinessRuleDefinition
     */
    public function testSetBusinessRuleDefinition()
    {
        $repairs = new supp_ProcessAuthorRepairs();
        if (!$repairs->isEnt() && !$repairs->isUlt()) {
            $this->markTestSkipped('Skipping test');
            return;
        }

        $ruleId = "646a7084-ed3f-11e5-94a1-736088870fb3";
        $new_rst_source_Definition = '{"id":"646a7084-ed3f-11e5-94a1-736088870fb3","base_module":"Accounts","type":"single","name":"test3","columns":{"conditions":[{"module":"Accounts","field":"industry"}],"conclusions":[""]},"ruleset":[{"id":1,"conditions":[{"value":[{"expType":"CONSTANT","expSubType":"string","expLabel":"Apparel","expValue":"Apparel"}],"variable_name":"industry","condition":"==","variable_module":"Accounts"}],"conclusions":[{"value":[{"expType":"VARIABLE","expSubtype":"DropDown","expLabel":"Industry","expValue":"industry","expModule":"member_of"}],"conclusion_value":"result","conclusion_type":"return"}]}]}';

        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $results = $supp_ProcessAuthorRepairsTest->setBusinessRuleDefinition($ruleId, $new_rst_source_Definition);

        // should return true
        $this->assertTrue($results);

        $sql = "
            SELECT rst_source_definition
            FROM pmse_business_rules
            WHERE id = '$ruleId'
        ";
        $returnedCriteria = html_entity_decode($GLOBALS['db']->getOne($sql));

        // should return updated criteria
        $this->assertEquals($new_rst_source_Definition, $returnedCriteria);
    }

    /**
     * Test for validating PS fields exist
     * @covers supp_ProcessAuthorRepairs::validatePAFieldExists
     */
    public function testValidatePAFieldExists()
    {
        $repairs = new supp_ProcessAuthorRepairs();
        if (!$repairs->isEnt() && !$repairs->isUlt()) {
            $this->markTestSkipped('Skipping test');
            return;
        }

        $base_module = "Accounts";
        $paDef = array(
            'id' => "testid",
            'name' => "testname"
        );

        // valid dropdown field in stock accounts
        $field = "account_type";

        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $return1 = $supp_ProcessAuthorRepairsTest->validatePAFieldExists($base_module, $field, $paDef);

        $this->assertEquals("enum", $return1);

        // invalid dropdown field in stock accounts
        $field = "nonexistantfield56_c";

        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $return2 = $supp_ProcessAuthorRepairsTest->validatePAFieldExists($base_module, $field, $paDef);

        $this->assertFalse($return2);

    }

    /**
     * Test for validating PA fields exist
     * @covers supp_ProcessAuthorRepairs::validatePAOptionListExists
     */
    public function testValidatePAOptionListExists()
    {
        $repairs = new supp_ProcessAuthorRepairs();
        if (!$repairs->isEnt() && !$repairs->isUlt()) {
            $this->markTestSkipped('Skipping test');
            return;
        }

        $type = "enum";
        $base_module = "Accounts";
        $paDef = array(
            'id' => "testid",
            'name' => "testname"
        );

        // valid dropdown field in stock accounts
        $field = "account_type";

        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $return1 = $supp_ProcessAuthorRepairsTest->validatePAOptionListExists($type, $base_module, $field, $paDef);

        $this->assertTrue(is_array($return1));
        $this->assertNotEmpty($return1);

        // invalid dropdown field in accounts
        $field = "nonexistantfield56_c";

        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $return2 = $supp_ProcessAuthorRepairsTest->validatePAOptionListExists($type, $base_module, $field, $paDef);

        $this->assertFalse($return2);

        // run on non-dropdown field
        $type = "textfield";
        $field = "account_type";

        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $return3 = $supp_ProcessAuthorRepairsTest->validatePAOptionListExists($type, $base_module, $field, $paDef);

        $this->assertFalse($return3);

    }

    /**
     * Test for validating PS fields exist
     * @covers supp_ProcessAuthorRepairs::validatePASelectedKey
     */
    public function testValidatePASelectedKey()
    {
        $repairs = new supp_ProcessAuthorRepairs();
        if (!$repairs->isEnt() && !$repairs->isUlt()) {
            $this->markTestSkipped('Skipping test');
            return;
        }

        $listKeys = array(
            "Apparel",
            "Banking",
            "expField",
            "Other"
        );
        $paDef = array(
            'id' => "testid",
            'name' => "testname"
        );
        $fieldString = "[{\"expType\":\"MODULE\",\"expSubtype\":\"DropDown\",\"expLabel\":\"Type is equal to \",\"expValue\":\"ëxpField*\",\"expOperator\":\"equals\",\"expModule\":\"Accounts\",\"expField\":\"account_type\"}]";

        // Valid item in list
        $selectedKey = "Apparel";

        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $return1 = $supp_ProcessAuthorRepairsTest->validatePASelectedKey($selectedKey, $listKeys, $fieldString, $paDef);

        // should return the fields string
        $this->assertEquals($fieldString, $return1);

        // Invalid item in list
        $selectedKey = "nonexistantvalue56";

        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $return2 = $supp_ProcessAuthorRepairsTest->validatePASelectedKey($selectedKey, $listKeys, $fieldString, $paDef);

        // should return the fields string
        $this->assertEquals($fieldString, $return2);

        // Invalid item in list, but corrected
        $selectedKey = "ëxpField*";
        $new_field_string = '[{"expType":"MODULE","expSubtype":"DropDown","expLabel":"Type is equal to ","expValue":"expField","expOperator":"equals","expModule":"Accounts","expField":"account_type"}]';

        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $return3 = $supp_ProcessAuthorRepairsTest->validatePASelectedKey($selectedKey, $listKeys, $fieldString, $paDef);

        // should return string
        $this->assertEquals($new_field_string, $return3);
    }

    /**
     * Test for fixing start criteria
     * @covers supp_ProcessAuthorRepairs::repairEventCriteria
     */
    public function testRepairEventCriteria()
    {
        $repairs = new supp_ProcessAuthorRepairs();
        if (!$repairs->isEnt() && !$repairs->isUlt()) {
            $this->markTestSkipped('Skipping test');
            return;
        }

        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $supp_ProcessAuthorRepairsTest->repairEventCriteria();

        // 4 broken records should be issues
        $this->assertGreaterThanOrEqual(4, count($supp_ProcessAuthorRepairsTest->foundIssues));

        // broken records
        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "46d69d50-e58c-11e5-9261-fe49746prjid");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "46d69d51-e58c-11e5-9261-fe49746prjid");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "46d69d52-e58c-11e5-9261-fe49746prjid");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "46d69d53-e58c-11e5-9261-fe49746prjid");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        // non-broken
        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "9ff025b6-e576-11e5-9261-fe49746prjid");
        $this->assertEquals("ACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "b25f1060-f1ef-11e5-a564-2c531c938b02");
        $this->assertEquals("ACTIVE", $paDefinition->prj_status);
    }

    /**
     * Test for fixing action fields
     * @covers supp_ProcessAuthorRepairs::repairActivities
     */
    public function testRepairActivities()
    {
        $repairs = new supp_ProcessAuthorRepairs();
        if (!$repairs->isEnt() && !$repairs->isUlt()) {
            $this->markTestSkipped('Skipping test');
            return;
        }

        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $supp_ProcessAuthorRepairsTest->repairActivities();

        // 11 broken records should be issues
        $this->assertGreaterThanOrEqual(11, count($supp_ProcessAuthorRepairsTest->foundIssues));

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "4a40ed2a-ebcb-11e5-a19f-342d44d047f0");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "06877022-ebcb-11e5-a19f-342d44d047f0");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "68dd07cc-ec71-11e5-a19f-342d44d047f0");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "ce60db0a-ec71-11e5-a19f-342d44d047f0");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "ba6b467a-ec72-11e5-a19f-342d44d047f0");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "9dd09126-ec88-11e5-a19f-342d44d047f0");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "04509716-ec89-11e5-a19f-342d44d047f0");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "64dc5b98-ec88-11e5-a19f-342d44d047f0");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "4a3ec220-ec89-11e5-a19f-342d44d047f0");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "cc88007a-ec89-11e5-a19f-342d44d047f0");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "b8ea5314-ec8a-11e5-a19f-342d44d047f0");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "06082fac-ebca-11e5-a19f-342d44d047f0");
        $this->assertEquals("ACTIVE", $paDefinition->prj_status);
    }

    /**
     * Test for fixing business rule fields
     * @covers supp_ProcessAuthorRepairs::repairBusinessRules
     */
    public function testRepairBusinessRules()
    {
        $repairs = new supp_ProcessAuthorRepairs();
        if (!$repairs->isEnt() && !$repairs->isUlt()) {
            $this->markTestSkipped('Skipping test');
            return;
        }

        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $supp_ProcessAuthorRepairsTest->repairBusinessRules();

        // 8 broken records should be issues
        $this->assertGreaterThanOrEqual(8, count($supp_ProcessAuthorRepairsTest->foundIssues));

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "2f6b6d54-ed51-11e5-94a1-736088870fb3");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "800eda32-ed5d-11e5-94a1-736088870fb3");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "7bf5370a-ed5f-11e5-94a1-736088870fb3");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "f6440928-ed5f-11e5-94a1-736088870fb3");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "7222b6a2-ed60-11e5-94a1-736088870fb3");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "d79ee4d6-ed67-11e5-94a1-736088870fb3");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "6376436e-ed68-11e5-94a1-736088870fb3");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "c0e866a8-ed68-11e5-94a1-736088870fb3");
        $this->assertEquals("INACTIVE", $paDefinition->prj_status);

        $paDefinition = BeanFactory::retrieveBean('pmse_Project', "0ac139be-ed3f-11e5-94a1-736088870fb3");
        $this->assertEquals("ACTIVE", $paDefinition->prj_status);

    }

    /**
     * Test for checking blacklisted fields
     * @covers supp_ProcessAuthorRepairs::isBlacklistedPAField
     */
    public function testIsBlacklistedPAField()
    {
        $repairs = new supp_ProcessAuthorRepairs();
        if (!$repairs->isEnt() && !$repairs->isUlt()) {
            $this->markTestSkipped('Skipping test');
            return;
        }

        $base_module = "Notes";
        $paDef = array(
            'id' => "testid",
            'name' => "testname"
        );

        // non-blacklisted field
        $field = "description";

        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $return1 = $supp_ProcessAuthorRepairsTest->isBlacklistedPAField($base_module, $field, $paDef);

        $this->assertFalse($return1);

        // blacklisted field
        $field = "deleted";

        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $return2 = $supp_ProcessAuthorRepairsTest->isBlacklistedPAField($base_module, $field, $paDef);

        $this->assertTrue($return2);
    }

    /**
     * Test for checking blacklisted field types
     * @covers supp_ProcessAuthorRepairs::isBlacklistedPAFieldType
     */
    public function testIsBlacklistedPAFieldType()
    {
        $repairs = new supp_ProcessAuthorRepairs();
        if (!$repairs->isEnt() && !$repairs->isUlt()) {
            $this->markTestSkipped('Skipping test');
            return;
        }

        $base_module = "Notes";
        $paDef = array(
            'id' => "testid",
            'name' => "testname"
        );

        // non-blacklisted field type
        $field = "description";
        $type = "TextArea";

        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $return1 = $supp_ProcessAuthorRepairsTest->isBlacklistedPAFieldType($type, $base_module, $field, $paDef);

        $this->assertFalse($return1);

        // blacklisted field type
        $field = "filename";
        $type = "file";

        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $return2 = $supp_ProcessAuthorRepairsTest->isBlacklistedPAFieldType($type, $base_module, $field, $paDef);

        $this->assertTrue($return2);
    }

    /**
     * Test for deleting the 3 different types of blacklisted actions
     * @covers supp_ProcessAuthorRepairs::deleteBlacklistedDefinition
     */
    public function testDeleteBlacklistedDefinition()
    {
        $repairs = new supp_ProcessAuthorRepairs();
        if (!$repairs->isEnt() && !$repairs->isUlt()) {
            $this->markTestSkipped('Skipping test');
            return;
        }

        $base_module = "Notes";
        $paDef = array(
            'id' => "testid",
            'name' => "testname"
        );

        // blacklisted event definition
        $paDef['source_table'] = "pmse_bpm_event_definition";
        $paDef['source_id'] = "43c9fefc-12f2-11e6-bb94-80f85ad93479";

        // blacklisted field
        $field = "deleted";

        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $return1 = $supp_ProcessAuthorRepairsTest->deleteBlacklistedDefinition($field, $paDef);

        $this->assertTrue($return1);

        // blacklisted activity definition
        $paDef['source_table'] = "pmse_bpm_activity_definition";
        $paDef['source_id'] = "b37672ac-12f3-11e6-bb94-80f85ad93479";
        $paDef['action_array'] = array(
            0 => array(
                "name" => "Industry",
                "field" => "industry",
                "value" => "Apparel",
                "type" => "DropDown"
                ),
            1 => array(
                "name" => "deleted",
                "field" => "deleted",
                "value" => "1",
                "type" => "int"
                ),
            );

        // blacklisted field
        $field = "deleted";

        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $return1 = $supp_ProcessAuthorRepairsTest->deleteBlacklistedDefinition($field, $paDef);

        $this->assertTrue($return1);

        // blacklisted business rule
        $paDef['source_table'] = "pmse_business_rules";
        $paDef['source_id'] = "d27a7a0e-12f3-11e6-bb94-80f85ad93479";
        $paDef['activity_id'] = "e26e474c-12f3-11e6-bb94-80f85ad93479";

        // blacklisted field
        $field = "deleted";

        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $return1 = $supp_ProcessAuthorRepairsTest->deleteBlacklistedDefinition($field, $paDef);

        $this->assertTrue($return1);
    }

    /**
     * Test for deleting a business rule definition
     * @covers supp_ProcessAuthorRepairs::deleteBusinessRuleDefinition
     */
    public function testDeleteBusinessRuleDefinition()
    {
        $repairs = new supp_ProcessAuthorRepairs();
        if (!$repairs->isEnt() && !$repairs->isUlt()) {
            $this->markTestSkipped('Skipping test');
            return;
        }

        $ruleId = "4aa147ca-1325-11e6-bb94-80f85ad93479";

        $supp_ProcessAuthorRepairsTest = new supp_ProcessAuthorRepairs();
        $supp_ProcessAuthorRepairsTest->setTesting(false);
        $results = $supp_ProcessAuthorRepairsTest->deleteBusinessRuleDefinition($ruleId);

        // should return true
        $this->assertTrue($results);

        $sql = "
            SELECT deleted
            FROM pmse_business_rules
            WHERE id = '$ruleId'
        ";
        $returnedDeleted = $GLOBALS['db']->getOne($sql);

        // should return updated deleted field
        $this->assertEquals(1, $returnedDeleted);
    }
}
