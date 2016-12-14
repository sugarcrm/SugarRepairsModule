<?PHP

require_once('modules/supp_SugarRepairs/supp_SugarRepairs_sugar.php');
require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_LanguageRepairs.php');
require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_TeamSetRepairs.php');
require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_WorkflowRepairs.php');
require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_ReportRepairs.php');
require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_VarfdefRepairs.php');
require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_EmailAddressRepairs.php');
require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_ProcessAuthorRepairs.php');
require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_ForecastWorksheetRepairs.php');
require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_MetadataRepairs.php');

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

    /**
     * Repairs vardefs
     * @param array $args
     * @return bool|void
     */
    public function repairVardefs(array $args)
    {
        $vardefRepairs = new supp_VardefRepairs();
        return $vardefRepairs->execute($args);
    }

    /**
     * Repairs email addresses
     * @param array $args
     * @return bool|void
     */
    public function repairEmailAddresses(array $args)
    {
        $emailAddressRepairs = new supp_EmailAddressRepairs();
        return $emailAddressRepairs->execute($args);
    }

    /**
     * Repairs or disable process author definitions
     * @param array $args
     * @return bool|void
     */
    public function repairProcessAuthor(array $args)
    {
        $processAuthorRepairs = new supp_ProcessAuthorRepairs();
        return $processAuthorRepairs->execute($args);
    }

    /**
     * Repairs forecast worksheets
     * @param array $args
     * @return bool|void
     */
    public function repairForecasts(array $args)
    {
        $forecastWorksheetRepairs = new supp_ForecastWorksheetRepairs();
        return $forecastWorksheetRepairs->execute($args);
    }

    /**
     * Repairs forecast worksheets
     * @param array $args
     * @return bool|void
     */
    public function repairMetadata(array $args)
    {
        $metadataRepairs = new supp_MetadataRepairs();
        return $metadataRepairs->execute($args);
    }
}