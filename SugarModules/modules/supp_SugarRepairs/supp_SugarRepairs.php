<?PHP

require_once('modules/supp_SugarRepairs/supp_SugarRepairs_sugar.php');
require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_LanguageRepairs.php');
require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_TeamSetRepairs.php');

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
     * @param bool $isTesting
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

}