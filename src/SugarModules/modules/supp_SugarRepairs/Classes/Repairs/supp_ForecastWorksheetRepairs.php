<?php

require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_Repairs.php');

class supp_ForecastWorksheetRepairs extends supp_Repairs
{
    protected $loggerTitle = "Forecasts";

    public $usersToProcess = array();
    public $timePeriodIdsToProcess = array();

    function __construct()
    {
        parent::__construct();
    }

    /**
     * Gets all time period IDs not deleted
     * @return array $timePeriodIds
     */
    public function getAllTimePeriodIds()
    {

        $query = new SugarQuery();
        $query->select(array(
            'id',
        ));
        $query->from(BeanFactory::newBean("TimePeriods"));
        $query->where()
            ->equals('deleted', '0');
        $results = $query->execute();

        $timePeriodIds = array();

        foreach ($results as $row) {
            $timePeriodIds[] = $row['id'];
        }
        return $timePeriodIds;
    }

    /**
     * Validates timeperiod_id param
     * @return boolean
     */
    public function validateTimePeriodId($timeperiod_id)
    {

        $query = new SugarQuery();
        $query->select(array(
            'id',
        ));
        $query->from(BeanFactory::newBean("TimePeriods"));
        $query->where()
            ->equals('deleted', '0')
            ->equals('id', $timeperiod_id);
        
        $returnId = $query->getOne();

        if ($returnId == $timeperiod_id) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets all level 1 (top level) managers
     * @return array $levelOneIds
     */
    public function getLevelOneManagers()
    {

        $levelOneIds = array();
        $level = 1;

        //Get level 1 -- managers with no reports_to
        $sql = "
            SELECT u.id,u.user_name,u.reports_to_id,IFNULL(u_boss.num_workers,0) as num_workers
            FROM users u
                LEFT JOIN (
                    SELECT reports_to_id manager_id,count(*) num_workers
                    FROM users
                    WHERE deleted = '0' AND (reports_to_id IS NOT NULL AND reports_to_id <> '')
                    GROUP BY reports_to_id
                ) u_boss
                    ON
                        u_boss.manager_id = u.id
            WHERE u.deleted = '0' and num_workers > 0 AND (reports_to_id IS NULL OR reports_to_id = '')
        ";
        $res = $GLOBALS['db']->query($sql);
        while ($row = $GLOBALS['db']->fetchByAssoc($res)) {
            $id = $row['id'];

            $levelOneIds[] = $id;
            $this->usersToProcess[$level][$id] = array(
                    "commit_type" => "manager",
                    "forecast_type" => "Rollup"
                );
        }

        return $levelOneIds;
    }

    /**
     * Gets all level 2+ users and managers by their reports to id
     * Run recursively until out of reports_to's
     * @param int $level Current level being processed
     * @param array $idFilter Ids of managers to get users by
     */
    public function getNextLevelUsersByManager($level, $idFilter)
    {

        $userIdFilters = array();

        $idFilterString = "'" . implode("','", $idFilter) . "'";

        $sql = "
            SELECT u.id,u.user_name,u.reports_to_id,IFNULL(u_boss.num_workers,0) as num_workers
            FROM users u
                LEFT JOIN (
                    SELECT reports_to_id manager_id,count(*) num_workers
                    FROM users
                    WHERE deleted = '0' AND (reports_to_id IS NOT NULL AND reports_to_id <> '')
                    GROUP BY reports_to_id
                ) u_boss
                    ON
                        u_boss.manager_id = u.id
            WHERE u.deleted = '0' AND reports_to_id in ($idFilterString)
        ";

        $res = $GLOBALS['db']->query($sql);
        while ($row = $GLOBALS['db']->fetchByAssoc($res)) {

            $id = $row['id'];
            
            $userIdFilters[] = $id;

            if ($row['num_workers'] > 0) {
                $commit_type = "manager";
                $forecast_type = "Rollup";
            } else {
                $commit_type = "sales_rep";
                $forecast_type = "Direct";
            }

            $this->usersToProcess[$level][$id] = array(
                "commit_type" => $commit_type,
                "forecast_type" => $forecast_type
            );
        }

        $level++;
        if (count($userIdFilters) > 0 && is_array($userIdFilters)) {
            $this->getNextLevelUsersByManager($level, $userIdFilters);
        }
    }

    /**
     * Removes all forecast_manager_worksheets records for
     * the specified timeperiod
     * @param string $timeperiod_id Time Period Id to remove forecast data
     * @return array
     */
    public function clearForecastWorksheet($timeperiod_id)
    {
        $sql = "
            DELETE
            FROM forecast_manager_worksheets 
            WHERE timeperiod_id = '$timeperiod_id'
        ";
        
        if (!$this->isTesting) {
            $this->logChange("-> Clearing forecast_manager_worksheets table for timeperiod_id '{$timeperiod_id}'");
            $results = $this->updateQuery($sql);
            $affected_row_count =  $GLOBALS['db']->getAffectedRowCount($results);
            $this->logChange('-> Deleted '.$affected_row_count.' from forecast_manager_worksheets table.');
        } else {
            $this->logChange("-> Will clear forecast_manager_worksheets table for timeperiod_id '{$timeperiod_id}'");
            $affected_row_count = 0;
        }
        
        return array(
            'affected_row_count' => $affected_row_count
            );
    }

    /**
     * Removes all quotas records for
     * the specified timeperiod and quota_type of Rollup
     * @param string $timeperiod_id Time Period Id to remove forecast data
     * @return array
     */
    public function clearRollupQuotas($timeperiod_id)
    {
        $sql = "
            DELETE
            FROM quotas 
            WHERE quota_type = 'Rollup' AND timeperiod_id = '$timeperiod_id'
        ";
        
        if (!$this->isTesting) {
            $this->logChange("-> Clearing Rollup quotas table for timeperiod_id '{$timeperiod_id}'");
            $results = $this->updateQuery($sql);
            $affected_row_count =  $GLOBALS['db']->getAffectedRowCount($results);
            $this->logChange('-> Deleted '.$affected_row_count.' from quotas table.');
        } else {
            $this->logChange("-> Will clear Rollup quotas table for timeperiod_id '{$timeperiod_id}'");
            $affected_row_count = 0;
        }
        
        return array(
            'affected_row_count' => $affected_row_count
            );
    }

    /**
     * Recommits users' worksheets for the specified time period
     * @param string $timeperiod_id Time period to re-commit
     */
    public function processUserCommits($timeperiod_id)
    {

        global $current_user;

        foreach ($this->usersToProcess as $level => $userArray) {

            foreach ($userArray as $user_id => $forecastSettigns) {
                $this->log('-> Starting process for user: '.$user_id.'...');

                // New Process -- Set global current_user, run class code
                $current_user = new User();
                $current_user->retrieve($user_id);

                $args = array(
                    "currency_id" => "-99",
                    "commit_type" => $forecastSettigns['commit_type'],
                    "timeperiod_id" => $timeperiod_id,
                    "forecast_type" => $forecastSettigns['forecast_type'],
                    "module" => "Forecasts",
                );

                $file = 'include/SugarForecasting/Committed.php';
                $klass = 'SugarForecasting_Committed';

                // check for a custom file exists
                SugarAutoLoader::requireWithCustom($file);
                $klass = SugarAutoLoader::customClass($klass);
                // create the class

                /* @var $obj SugarForecasting_AbstractForecast */
                $obj = new $klass($args);

                if (!$this->isTesting) {
                    $this->logChange("-> Saving new Forecast Commits for user '{$user_id}', timeperiod '{$timeperiod_id}'");
                    $results = $obj->save();
                } else {
                    $this->logChange("-> Will Save new Forecast Commits for user '{$user_id}, timeperiod '{$timeperiod_id}'");
                }
            }
        }
        $current_user->getSystemUser();
        $this->log("-> Finished processing timeperiod '{$timeperiod_id}'");
    }

    /**
     * Executes the forecast repairs
     * @param string $timeperiod_id Time period to re-commit
     */
    public function repairForecastWorksheets($timeperiod_id)
    {
        $this->logAll('Begin forecast worksheet repairs');

        $userIdFilters = $this->getLevelOneManagers();

        if (count($userIdFilters) > 0 && is_array($userIdFilters)) {
            $this->getNextLevelUsersByManager(2, $userIdFilters);
        }

        krsort($this->usersToProcess);

        if ($timeperiod_id == "ALL") {
            $this->timePeriodIdsToProcess = $this->getAllTimePeriodIds();
        } else {
            if ($this->validateTimePeriodId($timeperiod_id)) {
                $this->timePeriodIdsToProcess = array($timeperiod_id);
            } else {
                $this->logAction("-> Unable to find timeperiod_id '{$timeperiod_id}'. This will have to be fixed manually.");
            }
        }

        if (is_array($this->timePeriodIdsToProcess) && count($this->timePeriodIdsToProcess) > 0) {
            foreach ($this->timePeriodIdsToProcess as $timePeriod) {
                $this->log("-> Processing timeperiod id $timePeriod");
                $cfw_results = $this->clearForecastWorksheet($timePeriod);
                $crq_results = $this->clearRollupQuotas($timePeriod);
                $this->processUserCommits($timePeriod);
            }
        } else {
            $this->log('-> No valid timeperiods found.');
        }
        
        $this->log('End forecast worksheet repairs');
    }

    /**
     * Executes the forecast repairs
     * @param bool $isTesting
     */
    public function execute(array $args)
    {
        //check for testing an other repair generic params
        parent::execute($args);

        $stamp = time();

        if ($this->backupTable('forecast_manager_worksheets', $stamp) &&
            $this->backupTable('forecast_worksheets', $stamp) &&
            $this->backupTable('quotas', $stamp)
        ) {
            $this->repairForecastWorksheets($args['timeperiod_id']);
        } else {
            $this->log('Could not backup table');
        }
    }
}
