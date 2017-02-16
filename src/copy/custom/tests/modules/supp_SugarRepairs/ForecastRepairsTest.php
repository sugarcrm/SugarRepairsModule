<?php
// Copyright 2016 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_ForecastWorksheetRepairs.php');

/**
 * @group support
 * @group forecastRepairs
 */
class suppSugarRepairsForecastWorksheetRepairsTest extends Sugar_PHPUnit_Framework_TestCase
{

    protected $reportIDs = array();

    public function setUp()
    {
        parent::setUp();
        SugarTestHelper::setUp("current_user");
        $GLOBALS['current_user']->getSystemUser();
        $GLOBALS['app_list_strings'] = return_app_list_strings_language('en_us');
        $GLOBALS['log'] = LoggerManager::getLogger('SugarCRM');
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $bean = BeanFactory::newBean("Users");
        $bean->id = '38c90c70-7788-13a2-668d-513e2b8df5e1';
        $bean->new_with_id = true;
        $bean->first_name = 'Manager';
        $bean->first_name = 'Test';
        $bean->user_name = 'mtest';
        $bean->save();

        $bean = BeanFactory::newBean("Users");
        $bean->id = '2c78445c-f795-11e5-9b16-a19e342a368f';
        $bean->new_with_id = true;
        $bean->first_name = 'Worker';
        $bean->first_name = 'Test';
        $bean->user_name = 'wtest';
        $bean->reports_to_id = '38c90c70-7788-13a2-668d-513e2b8df5e1';
        $bean->save();

        $bean = BeanFactory::newBean("TimePeriods");
        $bean->id = '736d000c-f79d-11e5-9b16-a19e342a368f';
        $bean->start_date = '2015-01-01';
        $bean->end_date = '2015-03-01';
        $bean->new_with_id = true;
        $bean->save();

        $sql_setup[] = "CREATE TABLE forecast_manager_worksheets_repairTemp LIKE forecast_manager_worksheets;";
        $sql_setup[] = "INSERT forecast_manager_worksheets_repairTemp SELECT * FROM forecast_manager_worksheets;";
        $sql_setup[] = "DELETE FROM forecast_manager_worksheets;";

        $sql_setup[] = "
            INSERT INTO forecast_manager_worksheets(id,timeperiod_id) 
            VALUES ('6b542c24-f79d-11e5-9b16-a19e342a368f','736d000c-f79d-11e5-9b16-a19e342a368f')
        ";

        $sql_setup[] = "CREATE TABLE quotas_repairTemp LIKE quotas;";
        $sql_setup[] = "INSERT quotas_repairTemp SELECT * FROM quotas;";
        $sql_setup[] = "DELETE FROM quotas;";

        $sql_setup[] = "
            INSERT INTO quotas(id,timeperiod_id,quota_type) 
            VALUES ('151cf64c-3ee7-11e6-a35d-31e3e1bb05e5','736d000c-f79d-11e5-9b16-a19e342a368f','Rollup')
        ";

        $sql_setup[] = "
            INSERT INTO quotas(id,timeperiod_id,quota_type) 
            VALUES ('6e5be8a8-3ee7-11e6-a35d-31e3e1bb05e5','736d000c-f79d-11e5-9b16-a19e342a368f','Direct')
        ";

        foreach ($sql_setup as $q_setup) {
            $res = $GLOBALS['db']->query($q_setup);
        }
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        $sql_teardown[] = "
            DELETE FROM users
            WHERE id in (
                '38c90c70-7788-13a2-668d-513e2b8df5e1',
                '2c78445c-f795-11e5-9b16-a19e342a368f'
            )
        ";

        $sql_teardown[] = "
            DELETE FROM timeperiods
            WHERE id in (
                '736d000c-f79d-11e5-9b16-a19e342a368f'
            )
        ";

        $sql_teardown[] = "DELETE FROM forecast_manager_worksheets;";
        $sql_teardown[] = "INSERT forecast_manager_worksheets SELECT * FROM forecast_manager_worksheets_repairTemp;";
        $sql_teardown[] = "DROP TABLE forecast_manager_worksheets_repairTemp;";

        $sql_teardown[] = "DELETE FROM quotas;";
        $sql_teardown[] = "INSERT quotas SELECT * FROM quotas_repairTemp;";
        $sql_teardown[] = "DROP TABLE quotas_repairTemp;";

        foreach ($sql_teardown as $q_teardown) {
            $res = $GLOBALS['db']->query($q_teardown);
        }
    }

    /**
     * Test for returning time period ids
     * @covers supp_ForecastWorksheetRepairs::getAllTimePeriodIds
     */
    public function testGetAllTimePeriodIds()
    {
        if (version_compare($GLOBALS['sugar_version'], '7.2', '<')) {
            $this->markTestSkipped('Repair ignored as it does not apply to this version.');
            return false;
        }

        $repairs = new supp_ForecastWorksheetRepairs();
        $repairs->setTesting(false);
        $result = $repairs->getAllTimePeriodIds();

        $this->assertTrue(is_array($result));
        $this->assertGreaterThan(0, count($result));
    }

    /**
     * Test for returning time period ids
     * @covers supp_ForecastWorksheetRepairs::getAllTimePeriodIds
     */
    public function testValidateTimePeriodId()
    {
        if (version_compare($GLOBALS['sugar_version'], '7.2', '<')) {
            $this->markTestSkipped('Repair ignored as it does not apply to this version.');
            return false;
        }
        $repairs = new supp_ForecastWorksheetRepairs();
        $repairs->setTesting(false);
        $result = $repairs->validateTimePeriodId("736d000c-f79d-11e5-9b16-a19e342a368f");

        $this->assertTrue($result);

        $result = $repairs->validateTimePeriodId("fake_timeperiod_id_6468");
        $this->assertFalse($result);
    }

    /**
     * Test for returning level 1 managers
     * @covers supp_ForecastWorksheetRepairs::getLevelOneManagers
     */
    public function testGetLevelOneManagers()
    {
        if (version_compare($GLOBALS['sugar_version'], '7.2', '<')) {
            $this->markTestSkipped('Repair ignored as it does not apply to this version.');
            return false;
        }
        $repairs = new supp_ForecastWorksheetRepairs();
        $repairs->setTesting(false);
        $result = $repairs->getLevelOneManagers();

        $this->assertTrue(is_array($result));
        $this->assertGreaterThan(0, count($result));

        $this->assertTrue(is_array($repairs->usersToProcess));
        $this->assertGreaterThan(0, count($repairs->usersToProcess));

        $this->assertArrayHasKey('38c90c70-7788-13a2-668d-513e2b8df5e1', $repairs->usersToProcess[1]);
    }

    /**
     * Test for returning level workers to managers
     * @covers supp_ForecastWorksheetRepairs::getNextLevelUsersByManager
     */
    public function testGetNextLevelUsersByManager()
    {
        if (version_compare($GLOBALS['sugar_version'], '7.2', '<')) {
            $this->markTestSkipped('Repair ignored as it does not apply to this version.');
            return false;
        }

        $repairs = new supp_ForecastWorksheetRepairs();
        $repairs->setTesting(false);
        $repairs->getNextLevelUsersByManager(2, array('38c90c70-7788-13a2-668d-513e2b8df5e1'));

        $this->assertTrue(is_array($repairs->usersToProcess));
        $this->assertGreaterThan(0, count($repairs->usersToProcess));

        $this->assertArrayHasKey('2c78445c-f795-11e5-9b16-a19e342a368f', $repairs->usersToProcess[2]);
    }

    /**
     * Test for clearing the forecast_manager_worksheets table
     * @covers supp_ForecastWorksheetRepairs::clearForecastWorksheet
     */
    public function testClearForecastWorksheet()
    {
        if (version_compare($GLOBALS['sugar_version'], '7.2', '<')) {
            $this->markTestSkipped('Repair ignored as it does not apply to this version.');
            return false;
        }

        $repairs = new supp_ForecastWorksheetRepairs();
        $repairs->setTesting(false);
        $results = $repairs->clearForecastWorksheet('736d000c-f79d-11e5-9b16-a19e342a368f');

        $this->assertEquals(1, $results['affected_row_count']);

        $sql = "
            SELECT id
            FROM forecast_manager_worksheets
        ";
        $result = $GLOBALS['db']->query($sql);
        $affected_row_count = $GLOBALS['db']->getAffectedRowCount($result);

        $this->assertEquals(0, $affected_row_count);
    }

    /**
     * Test for clearing the quotas table
     * @covers supp_ForecastWorksheetRepairs::clearRollupQuotas
     */
    public function testClearRollupQuotas()
    {
        if (version_compare($GLOBALS['sugar_version'], '7.2', '<')) {
            $this->markTestSkipped('Repair ignored as it does not apply to this version.');
            return false;
        }
        $repairs = new supp_ForecastWorksheetRepairs();
        $repairs->setTesting(false);
        $results = $repairs->clearRollupQuotas('736d000c-f79d-11e5-9b16-a19e342a368f');

        $this->assertEquals(1, $results['affected_row_count']);

        $sql = "
            SELECT id
            FROM quotas
        ";
        $result = $GLOBALS['db']->query($sql);
        $affected_row_count = $GLOBALS['db']->getAffectedRowCount($result);

        $this->assertEquals(1, $affected_row_count);
    }

    /**
     * Test for running the whole shebang!
     * @covers supp_ForecastWorksheetRepairs::repairForecastWorksheets
     */
    public function testRepairForecastWorksheets()
    {
        if (version_compare($GLOBALS['sugar_version'], '7.2', '<')) {
            $this->markTestSkipped('Repair ignored as it does not apply to this version.');
            return false;
        }
        $repairs = new supp_ForecastWorksheetRepairs();
        $repairs->setTesting(false);

        $repairs->repairForecastWorksheets("ALL");

        $sql = "
            SELECT id
            FROM forecast_manager_worksheets
        ";
        $result = $GLOBALS['db']->query($sql);
        $affected_row_count = $GLOBALS['db']->getAffectedRowCount($result);

        $this->assertGreaterThan(0, $affected_row_count);

    }
}
