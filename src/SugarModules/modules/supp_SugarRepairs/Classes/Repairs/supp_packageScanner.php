<?php

require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_Repairs.php');
require_once('modules/supp_SugarRepairs/Classes/supp_packageScanner.php');

class supp_PackageScanner extends supp_Repairs
{
    protected $loggerTitle = "PackageScanner";
    protected $installedModulesFiles = array();
    private $fileScanner;
    private $databaseScanner;

    function __construct()
    {
        parent::__construct();
        $this->fileScanner = new FileScanner($this->getrwd());
        $this->databaseScanner = new DatabaseScanner($GLOBALS['db']);
        $this->getInstalledModules();
    }

    /**
     * Executes the Package Scanner
     * @param array $args
     */
    public function execute(array $args)
    {
        $fileResults = array();
        $this->logAll('Begin Package Scan');

        //check for testing an other repair generic params
        parent::execute($args);

        $stamp = time();
        $this->logAll('Scanning Files');
        $fileResults = $this->fileScanner->scan();

        if (!empty($fileResults)) {
            $this->logAll(var_export($fileResults, true));
        }

        $this->logAll('Scanning Database');
        $databaseResults = $this->databaseScanner->scan();

        if (!empty($databaseResults)) {
            $this->logAll(var_export($databaseResults, true));
        }
    }


    /**
     * Fetches all installed modules on an instance
     * @return mixed
     */
    public function getInstalledModules()
    {
        $installedModulesSQL = "SELECT * FROM upgrade_history WHERE type = 'module' AND status = 'installed'";

        $result = $GLOBALS['db']->query($installedModulesSQL);
        $cwd = getcwd();
        while ($module = $GLOBALS['db']->fetchByAssoc($result)) {
            try {
                $manifest = unserialize(base64_decode($module['manifest']));
            } catch (Exception $e) {
                $this->logAll("Bad serialization on module: {$module['name']}<br>This is going to throw off the accuracy of this tool");
            }
            if (isset($manifest['installdefs']['copy'])) {
                foreach ($manifest['installdefs']['copy'] as $set) {
                    $fileName = realpath($set['to']);
                    $md5Name = '.' . substr($fileName, strlen($cwd));
                    if (!array_key_exists($md5Name, $this->fileScanner->MD5) && file_exists($fileName)) {
                        $fileName = strtolower($fileName);
                        $id = $module['id'];
                        $this->installedModulesFiles[$fileName] = $module['name'] . " (" . $id . ")";
                    }
                }
            }
        }
    }
}