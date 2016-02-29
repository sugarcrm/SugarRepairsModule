<?PHP

require_once('modules/supp_SugarRepairs/supp_SugarRepairs_sugar.php');
require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_LanguageRepairs.php');
require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_TeamSetRepairs.php');
require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_WorkflowRepairs.php');
require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_ReportRepairs.php');

class supp_SugarRepairs extends supp_SugarRepairs_sugar
{
    /**
     * This is a depreciated method, please start using __construct() as this method will be removed in a future version
     *
     * @see __construct
     * @depreciated
     */
    function supp_SugarRepairs()
    {
        self::__construct();
    }

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Repairs the Sugar language files
     * @param $args
     */
    public function repairLanguages($args)
    {
        $langRepairs = new supp_LanguageRepairs();
        return $langRepairs->execute($args);
    }

    /**
     * Repairs the Team Sets
     * @param bool $isTesting
     */
    public function repairTeamSets(array $args)
    {
        $teamSetRepairs = new supp_TeamSetRepairs();
        return $teamSetRepairs->execute($args);
    }

    /**
     * Repairs or disables workflows
     * @param array $args
     * @return bool|void
     */
    public function repairWorkflows(array $args)
    {
        $workflowRepairs = new supp_WorkflowRepairs();
        return $workflowRepairs->execute($args);
    }

    /**
     * Repairs reports
     * @param array $args
     * @return bool|void
     */
    public function repairReports(array $args)
    {
        $workflowRepairs = new supp_ReportRepairs();
        return $workflowRepairs->execute($args);
    }
}