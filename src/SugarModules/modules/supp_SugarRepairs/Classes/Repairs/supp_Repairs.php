<?php
// Copyright 2016 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

abstract class supp_Repairs
{
    protected $loggerTitle = "Logger Title";
    protected $cycle_id = '';
    protected $isTesting = true; //always default to true
    protected $backupTables = array();
    public $unitTestLog = array();
    protected $listCache = array();

    /**
     * Construct to set the cycle id
     * supp_Repairs constructor.
     */
    function __construct()
    {
        $this->cycle_id = create_guid();
    }

    /**
     * Allows a user to manually set the cycle id
     * @param $cycleId
     */
    public function setCycleId($cycleId)
    {
        $this->cycle_id = $cycleId;
    }

    /**
     * Logs a change for the user to view
     * @param $message
     */
    protected function logAll($message)
    {
        $this->log($message, "[Sugar Repairs][{$this->cycle_id}][{$this->loggerTitle}][All] ", "All");
    }

    /**
     * Logs a change for the user to view
     * @param $message
     */
    protected function logChange($message)
    {
        $this->log($message, "[Sugar Repairs][{$this->cycle_id}][{$this->loggerTitle}][Change] ", "Change");
    }

    /**
     * Logs an action the user will have to do
     * @param $message
     */
    protected function logAction($message)
    {
        $this->log($message, "[Sugar Repairs][{$this->cycle_id}][{$this->loggerTitle}][Action] ", "Action");
    }

    /**
     * Logger Function
     * @param $message
     * @param string $prefix
     * @param string $type ('All', 'Combined, 'Action', 'Change')
     */
    protected function log($message, $prefix = '', $type = 'Combined')
    {
        if (empty($prefix)) {
            $log = "[Sugar Repairs][{$this->cycle_id}][{$this->loggerTitle}] ";
        } else {
            $log = $prefix;
        }

        $log .= $message;

        //$GLOBALS['log']->fatal($log);
        $this->logThis($log, $type);

        if (php_sapi_name() === 'cli') {
            if (
                isset($_SERVER['argv'][0])
                && (
                    strpos($_SERVER['argv'][0], 'phpunit') !== FALSE
                    || $_SERVER['argv'][0] == '/usr/bin/phpunit'
                )
            ) {
                //Capture the log file for unit tests
                $this->unitTestLog[] = $log;
            } else {
                echo $log . "\n";
            }
        }
    }

    /**
     * Flat File Logging System
     * @param string $entry
     * @param string $type ('All', 'Combined, 'Action', 'Change')
     * @param string $path
     */
    function logThis($entry, $type = 'Combined', $path = '')
    {
        if (file_exists('include/utils/sugar_file_utils.php')) {
            require_once('include/utils/sugar_file_utils.php');
        }

        //Make sure all messages end in a CR
        if(substr($entry,-1)!="\n") {
            $entry .= "\n";
        }

        if($type=='All') {
            $fileArray=array('Combined',"Action","Change");
        } else {
            //We always write to the 'Combined' file
            $fileArray = array_unique(array($type, 'Combined'));
        }

        foreach ($fileArray as $fileType) {
            $log = empty($path) ? "SugarRepairModule-{$fileType}-{$this->cycle_id}.log" : $path;

            // create if not exists
            $fp = @fopen($log, 'a+');
            if (!is_resource($fp)) {
                $GLOBALS['log']->fatal('SugarRepairModule could not open/lock SugarRepairModule.log file');
                die('SugarRepairModule could not open/lock SugarRepairModule.log file');
            }

            if (@fwrite($fp, $entry) === false) {
                $GLOBALS['log']->fatal('SugarRepairModule could not write to SugarRepairModule.log: ' . $entry);
                die('SugarRepairModule could not write to SugarRepairModule.log');
            }

            if (is_resource($fp)) {
                fclose($fp);
            }
        }
    }

    /**
     * Captures a database record
     * @param $cycle_id
     * @param $repair_type
     * @param $target_type
     * @param $target
     * @param $value_before
     * @param $value_after
     * @param $description
     * @param $status
     * @param $priority
     */
    protected function capture($cycle_id, $repair_type, $target_type, $target, $value_before, $value_after, $description, $status, $priority)
    {
        $sugarRepair = BeanFactory::newBean('supp_SugarRepairs');
        $sugarRepair->name = $target;
        $sugarRepair->cycle_id = $cycle_id;
        $sugarRepair->type = $repair_type;
        $sugarRepair->target_type = $target_type;
        $sugarRepair->target = $target;
        $sugarRepair->value_before = $value_before;
        $sugarRepair->value_after = $value_after;
        $sugarRepair->description = $description;
        $sugarRepair->status = $status;
        $sugarRepair->priority = $priority;
        $sugarRepair->save();
    }

    /**
     * Returns the custom language directories in Sugar.
     * @return string
     */
    protected function getLangRegex()
    {
        $langRegexes = array(
            //include
            '(\\/|\\\)custom(\\/|\\\)include(\\/|\\\)language(\\/|\\\)(.*?)\.lang.php$',
            //application extensions
            '(\\/|\\\)custom(\\/|\\\)Extension(\\/|\\\)application(\\/|\\\)Ext(\\/|\\\)Language(\\/|\\\)(.*?)\.php$',
            //module extensions
            '(\\/|\\\)custom(\\/|\\\)Extension(\\/|\\\)modules(\\/|\\\)(.*?)(\\/|\\\)Ext(\\/|\\\)Language(\\/|\\\)(.*?)\.php$',
            //module builder application (removed with plans to make modulebuilder its own scan soon)
            //'(\\/|\\\)custom(\\/|\\\)modulebuilder(\\/|\\\)packages(\\/|\\\)(.*?)(\\/|\\\)language(\\/|\\\)application(\\/|\\\)(.*?)\.lang.php$',
            //module builder modules (removed with plans to make modulebuilder its own scan soon)
            //'(\\/|\\\)custom(\\/|\\\)modulebuilder(\\/|\\\)packages(\\/|\\\)(.*?)(\\/|\\\)modules(\\/|\\\)(.*?)(\\/|\\\)language(\\/|\\\)(.*?)\.lang.php$',
            //custom modules
            '(\\/|\\\)custom(\\/|\\\)modules(\\/|\\\)(.*?)(\\/|\\\)language(\\/|\\\)(.*?)\.lang.php$',
        );

        return '/(' . implode(')|(', $langRegexes) . ')/';
    }

    /**
     * Returns the custom language directories in Sugar.
     * @return string
     */
    protected function getVardefRegex()
    {
        $langRegexes = array(
            //module extensions
            '(\\/|\\\)custom(\\/|\\\)Extension(\\/|\\\)modules(\\/|\\\)(.*?)(\\/|\\\)Ext(\\/|\\\)Vardefs(\\/|\\\)(.*?)\.php$',
            //module builder application
            //disabling for now as the variables are $vardefs and not $dictionary
            //'(\\/|\\\)custom(\\/|\\\)modulebuilder(\\/|\\\)packages(\\/|\\\)(.*?)(\\/|\\\)modules(\\/|\\\)(.*?)(\\/|\\\)vardefs.php$',
            //custom modules
            '(\\/|\\\)custom(\\/|\\\)modules(\\/|\\\)(.*?)(\\/|\\\)vardefs.php$',
        );

        return '/(' . implode(')|(', $langRegexes) . ')/';
    }

    /**
     * Returns the list of variables in a file
     * @param $file
     * @return array
     */
    public function getVariablesInFile($file)
    {
        $vars = array();
        $results = array_filter(
            token_get_all(file_get_contents($file)),
            function ($t) {
                return $t[0] == T_VARIABLE;
            }
        );

        foreach ($results as $result) {
            if (isset($result[1])) {
                $vars[$result[1]] = $result[1];
            }
        }

        return $vars;
    }

    /**
     * Returns the fields metadata from the database
     * @param $module
     * @param $field
     * @return bool
     */
    public function getFieldsMetadata($module, $field)
    {
        $sql = "SELECT * FROM fields_meta_data WHERE deleted = 0 AND custom_module = '{$module}' AND name = '{$field}'";
        $result = $GLOBALS['db']->query($sql);
        while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
            return $row;
        }

        return false;
    }

    /**
     * Determines if we've already backed up a table
     * @param $table
     * @return bool
     */
    protected function isBackedUpTable($table)
    {
        if (in_array($table, $this->backupTables)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Backs up a database table
     * @param $table
     */
    protected function backupTable($table, $stamp = '')
    {
        if ($this->isTesting) {
            return true;
        }

        if (empty($stamp)) {
            $stamp = time();
        }

        $this->log("Backing up {$table}...");
        $backupTable = preg_replace('{/$}', '', "{$table}_srm_{$stamp}");

        global $sugar_config;

        $maxTableLength = 128;
        if ($sugar_config['dbconfig']['db_type'] == 'mysql') {
            $maxTableLength = 64;
        } else if ($sugar_config['dbconfig']['db_type'] == 'oci8') {
            $maxTableLength = 30;
        } else if ($sugar_config['dbconfig']['db_type'] == 'mssql') {
            $maxTableLength = 128;
        } else if ($sugar_config['dbconfig']['db_type'] == 'ibm_db2') {
            $maxTableLength = 128;
        }

        if (strlen($backupTable) > $maxTableLength) {
            //limit table name - max length for oracle is 30
            $backupTable = substr($backupTable, 0, $maxTableLength);
        }

        $result = $GLOBALS['db']->tableExists($backupTable);
        if ($result) {
            $this->log("Database table '{$backupTable}' already exists. Renaming.");
            $backupTable . '_' . time();
        }

        if ($sugar_config['dbconfig']['db_type'] == 'mysql') {
            $GLOBALS['db']->query("CREATE TABLE {$backupTable} LIKE {$table}");
            $GLOBALS['db']->query("INSERT {$backupTable} SELECT * FROM {$table}");
        } else if ($sugar_config['dbconfig']['db_type'] == 'oci8') {
            $GLOBALS['db']->query("CREATE TABLE {$backupTable} AS SELECT * {$table}");
        } else if ($sugar_config['dbconfig']['db_type'] == 'mssql') {
            $GLOBALS['db']->query("SELECT * INTO {$backupTable} FROM {$table}");
        } else if ($sugar_config['dbconfig']['db_type'] == 'ibm_db2') {
            $GLOBALS['db']->query("CREATE TABLE {$backupTable} LIKE {$table}");
            $GLOBALS['db']->query("INSERT INTO {$backupTable} (SELECT * FROM {$table})");
        } else {
            $this->log("Database type '{$sugar_config['dbconfig']['db_type']}' not yet supported.");
            return false;
        }

        //capture list
        $this->backupTables[$backupTable] = $table;

        $result = $GLOBALS['db']->tableExists($backupTable);
        if ($result) {
            $this->logChange("Created {$backupTable} from {$table}.");
        } else {
            $this->log("Could not create {$backupTable} from {$table}!");
            die();
        }

        return $result;
    }

    /**
     * Fetches all custom language files
     */
    protected function getCustomLanguageFiles()
    {
        $path = realpath('custom');

        $directory = new RecursiveDirectoryIterator($path);
        $iterator = new RecursiveIteratorIterator($directory);

        $filter = new RegexIterator($iterator, $this->getLangRegex());
        $fileList = array();
        foreach ($filter as $file) {
            $fullPath = $file->__toString();
            $relativePath = str_replace(realpath(''), '', $fullPath);

            //we need to order files by date modified
            $fileList[$file->getMTime()][$fullPath] = $relativePath;
        }

        ksort($fileList);
        $fileList = call_user_func_array('array_merge', $fileList);

        return $fileList;
    }

    /**
     * Fetches all custom language files
     */
    protected function getCustomVardefFiles()
    {
        $path = realpath('custom');

        $directory = new RecursiveDirectoryIterator($path);
        $iterator = new RecursiveIteratorIterator($directory);

        $filter = new RegexIterator($iterator, $this->getVardefRegex());
        $fileList = array();
        foreach ($filter as $file) {
            $fullPath = $file->__toString();
            $relativePath = str_replace(realpath(''), '', $fullPath);

            //we need to order files by date modified
            $fileList[$file->getMTime()][$fullPath] = $relativePath;
        }

        ksort($fileList);
        $fileList = call_user_func_array('array_merge', $fileList);

        return $fileList;
    }

    /**
     * Runs a QRAR against the instance
     */
    public function runQRAR()
    {
        if ($this->isTesting) {
            return;
        }

        if (version_compare($GLOBALS['sugar_version'], '6.8', '<') && version_compare($GLOBALS['sugar_version'], '6.7', '>=')) {
            $this->logAction("You will need to run a Quick Repair & Rebuild after the repair has completed.");
            return;
        }

        $this->log("Running a Quick Repair & Rebuild...");
        require_once('modules/Administration/QuickRepairAndRebuild.php');
        $RAC = new RepairAndClear();
        $RAC->repairAndClearAll(array('clearAll'), array('All Modules'), false, false);
    }

    /**
     * Clears all language caches
     */
    public function clearLanguagesCache()
    {
        // Get the hashes array handled first
        $hashes = array();
        $path = sugar_cached("api/metadata/hashes.php");
        @include($path);

        // Track which indexes were deleted
        $deleted = array();
        foreach ($hashes as $key => $hash) {
            // If the index is a .json file path, unset it and delete it
            if (strpos($key, '.json')) {
                unset($hashes[$key]);
                @unlink($key);
                $deleted[$key] = $key;
            }
        }

        // Now handle files on the file system. This should yield an empty array
        // but its better to be safe than sorry
        $files = glob(sugar_cached("api/metadata/*.json"));
        foreach ($files as $file) {
            @unlink($file);
            $deleted[$file] = $file;
        }

        if ($deleted) {
            write_array_to_file("hashes", $hashes, $path);
        }
    }

    /**
     * Runs a rebuild workflow on the instance
     */
    public function runRebuildWorkflow()
    {
        if ($this->isTesting) {
            return;
        }

        $this->log("Running a Rebuild Workflow...");
        require_once('include/workflow/plugin_utils.php');

        global $beanFiles;
        global $mod_strings;
        global $db;

        $workflow_object = new WorkFlow();

        $module_array = $workflow_object->get_module_array();

        foreach ($module_array as $key => $module) {
            $dir = "custom/modules/" . $module . "/workflow";
            if (file_exists($dir)) {
                if ($elements = glob($dir . "/*")) {
                    foreach ($elements as $element) {
                        is_dir($element) ? remove_workflow_dir($element) : unlink($element);
                    }
                }
            }
        }

        $workflow_object->repair_workflow();
        build_plugin_list();
    }

    /**
     * Identifies syntax errors
     * @param $code
     * @return mixed|string
     */
    protected function testPHPSyntax($code)
    {
        $braces = 0;
        $inString = 0;

        // First of all, we need to know if braces are correctly balanced.
        // This is not trivial due to variable interpolation which
        // occurs in heredoc, backticked and double quoted strings
        foreach (token_get_all($code) as $token) {
            if (is_array($token)) {
                switch ($token[0]) {
                    case T_CURLY_OPEN:
                    case T_DOLLAR_OPEN_CURLY_BRACES:
                    case T_START_HEREDOC:
                        ++$inString;
                        break;
                    case T_END_HEREDOC:
                        --$inString;
                        break;
                }
            } else if ($inString & 1) {
                switch ($token) {
                    case '`':
                    case '"':
                        --$inString;
                        break;
                }
            } else {
                switch ($token) {
                    case '`':
                    case '"':
                        ++$inString;
                        break;

                    case '{':
                        ++$braces;
                        break;
                    case '}':
                        if ($inString) --$inString;
                        else {
                            --$braces;
                            if ($braces < 0) break 2;
                        }

                        break;
                }
            }
        }

        // Display parse error messages and use output buffering to catch them
        $inString = @ini_set('log_errors', false);
        $token = @ini_set('display_errors', true);
        ob_start();

        // If $braces is not zero, then we are sure that $code is broken.
        // We run it anyway in order to catch the error message and line number.

        // Else, if $braces are correctly balanced, then we can safely put
        // $code in a dead code sandbox to prevent its execution.
        // Note that without this sandbox, a function or class declaration inside
        // $code could throw a "Cannot redeclare" fatal error.

        $braces || $code = "if(0){{$code}\n}";

        $code = str_replace("<?php", "", $code);
        $code = str_replace("?>", "", $code);

        if (false === eval($code)) {
            if ($braces) {
                $braces = PHP_INT_MAX;
            } else {
                // Get the maximum number of lines in $code to fix a border case
                false !== strpos($code, "\r") && $code = strtr(str_replace("\r\n", "\n", $code), "\r", "\n");
                $braces = substr_count($code, "\n");
            }

            $code = ob_get_clean();
            $code = strip_tags($code);

            // Get the error message and line number
            if (preg_match("'syntax error, (.+) in .+ on line (\d+)$'s", $code, $code)) {
                $code[2] = (int)$code[2];
                $code = $code[2] <= $braces
                    ? array($code[1], $code[2])
                    : array('unexpected $end' . substr($code[1], 14), $braces);
            } else {
                $code = array('syntax error', 0);
            }
        } else {
            ob_end_clean();
            $code = false;
        }

        @ini_set('display_errors', $token);
        @ini_set('log_errors', $inString);

        return $code;
    }

    /**
     * Determines is the edition is CE
     * @return bool
     */
    public function isCE()
    {
        return $GLOBALS['sugar_flavor'] == 'CE';
    }

    /**
     * Determines is the edition is Professional
     * @return bool
     */
    public function isPro()
    {
        return $GLOBALS['sugar_flavor'] == 'PRO';
    }

    /**
     * Determines is the edition is Corporate
     * @return bool
     */
    public function isCorp()
    {
        return $GLOBALS['sugar_flavor'] == 'CORP';
    }

    /**
     * Determines is the edition is Enterprise
     * @return bool
     */
    public function isEnt()
    {
        return $GLOBALS['sugar_flavor'] == 'ENT';
    }

    /**
     * Determines is the edition is Ultimate
     * @return bool
     */
    public function isUlt()
    {
        return $GLOBALS['sugar_flavor'] == 'ULT';
    }

    /**
     * Allows a developer to toggle the isTesting flag
     * @param $isTesting
     */
    public function setTesting($isTesting)
    {
        $this->isTesting = $isTesting;
    }

    /**
     * Prefixes a given report with Broken: to let users know problematic reports
     * @param $id
     */
    public function markReportBroken($id)
    {
        $message = "Broken: ";

        $savedReport = BeanFactory::getBean('Reports', $id);

        if (!$this->isTesting) {
            if (substr($savedReport->name, 0, strlen($message)) !== $message) {
                $this->logChange("-> Marking report '{$savedReport->name}' ({$savedReport->id}) as broken.");
                $savedReport->name = $message . $savedReport->name;
                $savedReport->save();
            } else {
                $this->log("-> Report '{$savedReport->name}' ({$savedReport->id}) is already marked as broken.");
            }
        } else {
            $this->logChange("-> Will mark report '{$savedReport->name}' ({$savedReport->id}) as broken.");
        }
    }

    /**
     * Should be used anytime an update query is being ran
     * @param $sql
     */
    public function updateQuery($sql)
    {
        if ($this->isTesting) {
            return true;
        }

        $this->logChange("-> Ran Update SQL: " . $sql);

        $this->capture($this->cycle_id, $this->loggerTitle, 'Database', 'table', "The follow tables are backups:" . implode(',', $this->backupTables), $sql, "Capturing Update SQL'", 'Completed', 'P3');
        return $GLOBALS['db']->query($sql);
    }

    /**
     * Disables a specific workflow
     * @param $id
     */
    public function disableWorkflow($id)
    {
        $workflow = BeanFactory::getBean('WorkFlow', $id);

        if ($workflow->status != 0) {

            if (!$this->isTesting) {
                $this->logChange("Disabling workflow '{$workflow->name}' ({$id})...");
                $workflow->status = 0;
                $workflow->save();
            } else {
                $this->logChange("-> Will disable workflow '{$workflow->name}' ({$id}).");
            }
        } else {
            $this->log("-> Workflow '{$workflow->name}' ({$id}) is already disabled.");
        }
    }

    /**
     * Disables a specific process author definition
     * @param $id
     */
    public function disablePADefinition($id)
    {
        $paDefinition = BeanFactory::retrieveBean('pmse_Project', $id);
        if (is_object($paDefinition) === false) {
            $this->logAction("-> Failed to locate PA Definition {$id}.");
            return false;
        }

        if ($paDefinition->prj_status == "ACTIVE") {
            if (!$this->isTesting) {
                $this->logChange("Disabling PA Definition '{$paDefinition->name}' ({$id})...");
                $paDefinition->prj_status = "INACTIVE";
                $paDefinition->save();
            } else {
                $this->logChange("-> Will disable PA Definition '{$paDefinition->name}' ({$id}).");
            }
        } else {
            $this->log("-> PA Definition '{$paDefinition->name}' ({$id}) is already disabled.");
        }
    }

    /**
     * Used to fetch the related module name from module and link
     * @param $module string
     * @param $link string
     * @return string
     */
    public function getRelatedModuleName($module, $link)
    {
        $bean = BeanFactory::getBean($module);
        $bean->load_relationship($link);

        if ($bean->$link->relationship->selfReferencing == true) {
            return $module;
        } else {
            if ($bean->$link->relationship->lhs_module == $module) {
                return $bean->$link->relationship->rhs_module;
            } else {
                return $bean->$link->relationship->lhs_module;
            }
        }
    }

    /**
     * Used to fetch module name from object name
     * @param $objectName
     * @return mixed
     */
    public function getModuleName($objectName)
    {
        global $beanList;

        $module = array_search($objectName, $beanList);

        if (!$module) {
            $module = $objectName;
        }

        return $module;
    }

    /**
     * Returns the field def for a specific field
     * @param $module
     * @param $field
     * @return bool
     */
    public function getFieldDefinition($module, $field)
    {
        $bean = BeanFactory::getBean($module);

        if (isset($bean->field_defs[$field])) {
            return $bean->field_defs[$field];
        } else {
            $this->log("-> The field '{$field}' was not found on the '{$module}' module. It may have been deleted.");
            return false;
        }
    }

    /**
     * Determines a fields type
     * @param $module
     * @param $field
     */
    public function getFieldType($module, $field)
    {
        $def = $this->getFieldDefinition($module, $field);
        if ($def && isset($def['type'])) {
            return $def['type'];
        } else {
            $this->log("-> Type definition not found for {$module} / {$field}");
            return false;
        }
    }

    /**
     * Writes a dictionary variable file
     * @param $objectName
     * @param $field
     * @param $array
     * @param $fileName
     */
    protected function writeDictionaryFile($dictionary, $fileName)
    {
        $this->logchange("-> Writing dictionary variable file '{$fileName}'");
        if (!$this->isTesting) {

            $content = "<?php\n // created: " . date('Y-m-d H:i:s') . "\n";
            foreach ($dictionary as $objectName => $modDefs) {
                foreach ($modDefs as $section => $sectionDefs) {
                    if (is_array($sectionDefs)) {
                        foreach ($sectionDefs as $item => $itemDefs) {
                            if (is_array($itemDefs)) {
                                foreach ($itemDefs as $property => $value) {
                                    $content .= override_value_to_string_recursive(array($objectName, $section, $item, $property), 'dictionary', $value) . "\n";
                                }
                            } else {
                                $content .= override_value_to_string_recursive(array($objectName, $section, $item), 'dictionary', $itemDefs) . "\n";
                            }
                        }
                    } else {
                        $content .= override_value_to_string_recursive(array($objectName, $section), 'dictionary', $sectionDefs) . "\n";
                    }
                }
            }

            if (is_file($fileName)) {
                $beforeContents = file_get_contents($fileName);
                $fileAccessTime = date('U', fileatime($fileName));
                $fileModifiedTime = date('U', filemtime($fileName));
                $this->capture($this->cycle_id, $this->loggerTitle, 'File', $fileName, $beforeContents, $content, "Backing up '{$fileName}'", 'Completed', 'P3');
                sugar_file_put_contents($fileName, $content, LOCK_EX);
                sugar_touch($fileName, $fileModifiedTime, $fileAccessTime);
            } else {
                $this->capture($this->cycle_id, $this->loggerTitle, 'File', $fileName, null, $content, "Writing new '{$fileName}'", 'Completed', 'P3');
                sugar_file_put_contents($fileName, $content, LOCK_EX);
            }
        }
    }

    /**
     * Allows the writing of a new file
     * @param $file
     * @param $contents
     */
    protected function writeFile($fileName, $contents)
    {
        $this->logchange("-> Writing file '{$fileName}'");
        if (!$this->isTesting) {
            if (is_file($fileName)) {
                $this->capture($this->cycle_id, $this->loggerTitle, 'File', $fileName, file_get_contents($fileName), $contents, "Backing up '{$fileName}'", 'Completed', 'P3');
                //Update the file but retain its modified and access date stamps
                $fileAccessTime = date('U', fileatime($fileName));
                $fileModifiedTime = date('U', filemtime($fileName));
                sugar_file_put_contents($fileName, $contents, LOCK_EX);
                sugar_touch($fileName, $fileModifiedTime, $fileAccessTime);
            } else {
                $this->capture($this->cycle_id, $this->loggerTitle, 'File', $fileName, null, $contents, "Writing new '{$fileName}'", 'Completed', 'P3');
                sugar_file_put_contents($fileName, $contents, LOCK_EX);
            }
        }
    }

    /**
     * Returns the keys for a list
     * @param $listName
     * @return array|bool
     */
    public function getListOptions($listName)
    {
        //If the listCache is built and the ListName we are looking for
        // is in it then just return that.  Otherwise rescan the language file again
        if (!empty($this->listCache)) {
            if (array_key_exists($listName, $this->listCache)) {
                return $this->listCache[$listName];
            } else {
                $this->listCache = array();
            }
        }
        $SupportedLanguages['bg_BG'] = 'bg_BG';
        $SupportedLanguages['cs_CZ'] = 'cs_CZ';
        $SupportedLanguages['da_DK'] = 'da_DK';
        $SupportedLanguages['de_DE'] = 'de_DE';
        $SupportedLanguages['el_EL'] = 'el_EL';
        $SupportedLanguages['es_ES'] = 'es_ES';
        $SupportedLanguages['fr_FR'] = 'fr_FR';
        $SupportedLanguages['he_IL'] = 'he_IL';
        $SupportedLanguages['hu_HU'] = 'hu_HU';
        $SupportedLanguages['it_it'] = 'it_it';
        $SupportedLanguages['lt_LT'] = 'lt_LT';
        $SupportedLanguages['ja_JP'] = 'ja_JP';
        $SupportedLanguages['ko_KR'] = 'ko_KR';
        $SupportedLanguages['lv_LV'] = 'lv_LV';
        $SupportedLanguages['nb_NO'] = 'nb_NO';
        $SupportedLanguages['nl_NL'] = 'nl_NL';
        $SupportedLanguages['pl_PL'] = 'pl_PL';
        $SupportedLanguages['pt_PT'] = 'pt_PT';
        $SupportedLanguages['ro_RO'] = 'ro_RO';
        $SupportedLanguages['ru_RU'] = 'ru_RU';
        $SupportedLanguages['sv_SE'] = 'sv_SE';
        $SupportedLanguages['tr_TR'] = 'tr_TR';
        $SupportedLanguages['zh_CN'] = 'zh_CN';
        $SupportedLanguages['pt_BR'] = 'pt_BR';
        $SupportedLanguages['ca_ES'] = 'ca_ES';
        $SupportedLanguages['en_UK'] = 'en_UK';
        $SupportedLanguages['sr_RS'] = 'sr_RS';
        $SupportedLanguages['sk_SK'] = 'sk_SK';
        $SupportedLanguages['sq_AL'] = 'sq_AL';
        $SupportedLanguages['et_EE'] = 'et_EE';
        $SupportedLanguages['es_LA'] = 'es_LA';
        $SupportedLanguages['fi_FI'] = 'fi_FI';
        $SupportedLanguages['ar_SA'] = 'ar_SA';
        $SupportedLanguages['uk_UA'] = 'uk_UA';
        $SupportedLanguages['en_us'] = 'en_us';

        foreach ($SupportedLanguages as $lang) {
            $app_list_strings = return_app_list_strings_language($lang);

            //Build the cache, making sure the keys only appear once
            foreach ($app_list_strings as $listNames => $keys) {
                if (!isset($this->listCache[$listNames])) {
                    $this->listCache[$listNames] = array();
                }
                if (!is_array($keys)) {
                    $keys = array($keys => $keys);
                }
                $this->listCache[$listNames] = array_unique(array_merge($this->listCache[$listNames], array_keys($keys)));
            }
        }

        if (array_key_exists($listName, $this->listCache)) {
            return $this->listCache[$listName];
        } else {
            return false;
        }
    }

    /**
     * Returns the option keys for a list
     * @param $module
     * @param $field
     * @return array|bool
     */
    public function getFieldOptionKeys($module, $field)
    {
        $definition = $this->getFieldDefinition($module, $field);
        if ($definition && isset($definition['options'])) {
            $listName = $definition['options'];
        } else {
            $this->log("-> No options list found for {$module} / {$field}: " . print_r($definition, true));
            return false;
        }

        $list = $this->getListOptions($listName);

        if ($list) {
            $this->log("-> {$module} / {$field} is using the list '{$listName}'.");
        }

        return $list;
    }

    /**
     * Returns valid key names give a string
     * @param $key
     * @return string
     */
    public function getValidLanguageKeyName($key)
    {
        $storedKey = $key;

        //try to keep it with original intent
        $replacements = array(
            '&amp;' => ' and ',
            '&' => ' and '
        );

        $key = str_replace(array_keys($replacements), array_values($replacements), $key);

        //try to convert accents
        $key = strtr(utf8_decode($key), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');

        //only allow letters, numbers, spaces, and underscores
        $key = preg_replace("/[^a-z0-9\s\_\.]/i", ' ', $key);

        if ($storedKey !== $key) {
            //if key was changed, clean whitespace
            $key = preg_replace('!\s+!', ' ', $key);
            $key = trim($key);

            if (empty($key)) {
                $this->log("The key '{$storedKey}' converted to an empty string.");
                return false;
            }
        }

        return $key;
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
     * Removes all forecast_manager_worksheets records for
     * the specified timeperiod
     * @param string $timeperiod_id Time Period Id to remove forecast data
     * @return array
     */
    public function clearForecastWorksheet($timeperiod_id)
    {
        if ($this->isTesting) {
            return;
        }

        $sql = "
            DELETE
            FROM forecast_manager_worksheets
            WHERE timeperiod_id = '$timeperiod_id'
        ";

        $res = $this->updateQuery($sql);
        $affected_row_count = $GLOBALS['db']->getAffectedRowCount($res);
        $GLOBALS['log']->info('Deleted ' . $affected_row_count . ' from forecast_manager_worksheets table.');
        return array(
            'affected_row_count' => $affected_row_count
        );
    }

    /**
     * Returns the last JSON error in a readable format
     * @return string
     */
    public function getJSONLastError()
    {
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return 'No errors';
                break;
            case JSON_ERROR_DEPTH:
                return 'Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                return 'Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                return 'Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                return 'Syntax error, malformed JSON';
                break;
            case JSON_ERROR_UTF8:
                return 'Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                return 'Unknown error';
                break;
        }
    }


    /**
     * Executes the repairs
     * @param array $args
     */
    public function execute(array $args)
    {
        if (isset($args['test']) && ($args['test'] === 'false' || $args['test'] === false)) {
            $this->setTesting(false);
        }

        if ($this->isTesting) {
            $this->log("Running in test mode.");
        }
    }

    public function getCustomModules(){
        $custMods = array();
        
        //check language file for new module names
        $extLang = 'custom/application/Ext/Include/modules.ext.php';
        if (file_exists($extLang)) {
            include $extLang;

                //check to see if modules are declared in the app_list_string array of the language file
            if (!empty($moduleList)) {
                foreach ($moduleList as $key => $moduleName) {
                    //check path to see if it's valid
                    $modPath = 'modules/'.$moduleName;
                    if (file_exists($modPath)) {
                        $custMods[$moduleName] = $modPath;
                    }
                }
             }
         }
 
 
         return $custMods;
    }
}
