<?php

class FileScanner extends supp_PackageScanner
{
    public $filePath;
    public $scannerPath;
    private $scanner;
    public $MD5;
    public $issues = array();
    protected $installedModulesFiles = array();
    private $validExt = array('log', 'htaccess', 'manifest', 'ttf', 'ico', 'svg', 'json');
    private $ignoreDirectories = array('cache', 'upload');
    private $ignoreFilenames = array('./config.php','./config_override.php');
    private $sugarModuleList = array("MigrationScanner", "HealthCheck", "SugarHeartbeat", "SugarSystemInfo", "supp_SugarRepairs");
    private $showEverything = false;
    public $returnedIssues = array();

    public function __construct($filePath, $scannerPath = '')
    {
        if (!empty($filePath)) {
            if (substr($filePath, -1) == DIRECTORY_SEPARATOR) {
                $filePath = substr($filePath, 0, -1);
            }
            $this->filePath = $filePath;
        } else {
            $this->logAll("Path to SugarCRM files required");
            die();
        }
        if (!empty($scannerPath)) {
            if (is_file($scannerPath)) {
                $this->scannerPath = $scannerPath;
            } else {
                $this->logAll("Valid path to ModuleInstall/ModuleScanner.php required");
                die();
            }
        } elseif (is_file($this->filePath . DIRECTORY_SEPARATOR . 'ModuleInstall/ModuleScanner.php')) {
            $this->scannerPath = $this->filePath . DIRECTORY_SEPARATOR . 'ModuleInstall/ModuleScanner.php';
        } else {
            $this->logAll("Path to ModuleInstall/ModuleScanner.php required");
            die();
        }
        $this->setMD5($this->filePath);
        require_once($this->scannerPath);
        $this->scanner = new ModuleScanner();
    }

    /**
     * Scans in all relevant files from the root directory
     * @param $directory
     */
    public function scan()
    {
        $Directory = new RecursiveDirectoryIterator($this->filePath);
        $Iterator = new RecursiveIteratorIterator($Directory);

        $customDir = DIRECTORY_SEPARATOR . "custom" . DIRECTORY_SEPARATOR;

        foreach ($Iterator as $fileName => $value) {
            $pi = pathinfo($fileName);
            /**
             * Ignore directories, files with allowed extensions and files with names like:
             * list.php42398572345
             * list.php_2341234
             * list.php[23412324]
             *
             * if $showEverything is set to TRUE then it will ignore nothing
             */
            if (!is_dir($fileName) &&
                (((isset($pi['extension']) &&
                            !in_array($pi['extension'], $this->validExt)) &&
                        !preg_match($this->ignoreRegex(), $fileName) &&
                        !preg_match('/[a-zA-Z]+\d+$/', $fileName) &&
                        !preg_match('/[a-zA-Z]+_\d+$/', $fileName) &&
                        !preg_match('/[a-zA-Z]+\[\d+\]$/', $fileName)) || $this->showEverything)
            ) {
                $md5Name = "." . substr($fileName, strlen($this->filePath));
                $relativeFileName = substr($fileName, strlen($this->filePath) + 1);
                if (array_key_exists($md5Name, $this->MD5)) {
                    $fileMD5 = md5_file($fileName);
                    if($fileMD5 != $this->MD5[$md5Name] && !in_array($md5Name, $this->ignoreFilenames)) {
                        $issue = 'MD5 Mismatch:';
                        if (!array_key_exists($issue, $this->returnedIssues)) {
                            $this->returnedIssues[$issue] = array();
                        }
                        //List each file only once in each issue category
                        if (!in_array($relativeFileName, $this->returnedIssues[$issue])) {
                            $this->returnedIssues[$issue][] = $relativeFileName;
                        }
                    }
                } else {
                    $issues = $this->scanner->scanFile($fileName);
                    if (!empty($issues)) {
                        foreach ($issues as $key => $issue) {
                            $issue = trim($issue);
                            //WARN if custom module loaded a file into the root directory
                            if (stripos($fileName, $customDir) === false) {
                                if (!array_key_exists(strtolower($fileName), $this->installedModulesFiles)) {
                                    $issue = 'Files that will be removed in the migration:';
                                }
                            }
                            //Separate out SugarCRM upgrade/migration modules
                            if (!$this->ms_stripos($this->sugarModuleList, $fileName)) {
                                $issue = 'SugarCRM Upgrade/Migration Modules - Need to be uninstalled before Migration';
                            }
                            if (!array_key_exists($issue, $this->returnedIssues)) {
                                $this->returnedIssues[$issue] = array();
                            }
                            //List each file only once in each issue category
                            if (!in_array($relativeFileName, $this->returnedIssues[$issue])) {
                                $this->returnedIssues[$issue][] = $relativeFileName;
                            }
                        }
                    }
                    //WARN if custom module loaded a file into the root directory
                    if (stripos($fileName, $customDir) === false) {
                        if (!array_key_exists(strtolower($fileName), $this->installedModulesFiles)) {
                            $issue = 'Files that will be removed in the migration:';
                            //List each file only once in each issue category
                            if (!in_array($relativeFileName, $this->returnedIssues[$issue])) {
                                $this->returnedIssues[$issue][] = $relativeFileName;
                            }
                        }
                    }
                }
            }
        }

        return $this->returnedIssues;
    }

    /**
     * if an element of an array appears in the filename then return FALSE
     *
     * @param array $arrayToFind
     * @param $haystack
     * @return bool
     */
    private function ms_stripos($arrayToFind = array(), $haystack)
    {
        foreach ($arrayToFind as $needle) {
            if (stripos($haystack, $needle) !== false) {
                return false;
            }
        }
        return true;
    }

    /**
     * Set up an array with all the then files in the MD5 file in it
     */
    private function setMD5($path)
    {
        $this->MD5 = array();
        $md5_string = array();
        if (is_file($path . DIRECTORY_SEPARATOR . 'files.md5')) {
            require($path . DIRECTORY_SEPARATOR . 'files.md5');
        } else {
            die("File not found: ".$path . DIRECTORY_SEPARATOR . 'files.md5');
        }
        $this->MD5 = $md5_string;
    }

    /**
     * Set up the regex pattern from an array of words
     *
     * @return string
     */
    private function ignoreRegex()
    {
        $regex = array();
        foreach ($this->ignoreDirectories as $directoryName) {
            $regex[] .= "\\/{$directoryName}\\/";
        }
        return "(" . implode("|", $regex) . ")";
    }
}

class DatabaseScanner
{
    public $scanResults = array();
    public $connection;

    public function __construct($connection) {
        $this->connection=$connection;
    }

    public function scan() {
        $procedureQuery = "SHOW PROCEDURE STATUS WHERE Db = DATABASE() AND Type = 'PROCEDURE'";
        $result=$this->connection->query($procedureQuery);
        while($hash=$this->connection->fetchByAssoc($result)) {
            if($hash['Name'] != '_hierarchy') {
                $this->scanResults['invalid_stored_procedures'][]=array('name'=>$hash['Name']);
            }
        }
        $functionQuery="SHOW FUNCTION STATUS WHERE Db = DATABASE() AND Type = 'FUNCTION'";
        $result=$this->connection->query($functionQuery);
        while($hash=$this->connection->fetchByAssoc($result)) {
            $this->scanResults['invalid_stored_functions'][]=array('name'=>$hash['name']);
        }

        return $this->scanResults;
    }
}

class DatabaseBackupScanner
{
    private $db_dump;
    public $scanResults=array();

    public function __construct($dumpPath)
    {
        if (!empty($dumpPath)) {
            $this->db_dump = $dumpPath;
        } else {
            die("Path to dump file required");
        }
    }

    public function scan()
    {
        $ret_val = null;
        $output = array();
        $last_line = null;
        foreach (array('^use `', '^USE `', '^definer=`', '^DEFINER=`', '^create\s*definer=', '^CREATE\s*DEFINER=') as $pattern) {
            $last_line = exec(
                sprintf("grep -n -m 1 %s %s", escapeshellarg($pattern), escapeshellarg($this->db_dump)),
                $output,
                $ret_val
            );
            if (0 == $ret_val) {
                $this->scanResults[$pattern]=1;
            } elseif (1 != $ret_val) {
                $this->scanResults[$pattern]=count($output);
            }
        }

        return $this->scanResults;
    }
}