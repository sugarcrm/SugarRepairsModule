<?php

abstract class supp_Repairs
{
    protected $loggerTitle = "Logger Title";
    protected $cycle_id = '';
    protected $isTesting = true; //always default to true
    protected $backupTables = array();

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
    protected function logChange($message)
    {
        $this->log($message, "[Sugar Repairs][{$this->cycle_id}][{$this->loggerTitle}][Change] ");
    }

    /**
     * Logs an action the user will have to do
     * @param $message
     */
    protected function logAction($message)
    {
        $this->log($message, "[Sugar Repairs][{$this->cycle_id}][{$this->loggerTitle}][Action] ");
    }

    /**
     * Logger for repair actions
     * @param $message
     * @param string $level
     */
    protected function log($message, $prefix='')
    {
        if (empty($prefix)) {
            $log = "[Sugar Repairs][{$this->cycle_id}][{$this->loggerTitle}] ";
        } else {
            $log = $prefix;
        }

        $log .= $message;

        $GLOBALS['log']->fatal($log);

        if (php_sapi_name() === 'cli') {
            if (
                isset($_SERVER['argv'][0])
                && (
                    strpos($_SERVER['argv'][0], 'phpunit') !== FALSE
                    || $_SERVER['argv'][0] == '/usr/bin/phpunit'
                )
            ) {
                //in phpunit tests
            } else {
                echo $log . "\n";
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
            //module builder application
            '(\\/|\\\)custom(\\/|\\\)modulebuilder(\\/|\\\)packages(\\/|\\\)(.*?)(\\/|\\\)language(\\/|\\\)application(\\/|\\\)(.*?)\.lang.php$',
            //module builder modules
            '(\\/|\\\)custom(\\/|\\\)modulebuilder(\\/|\\\)packages(\\/|\\\)(.*?)(\\/|\\\)modules(\\/|\\\)(.*?)(\\/|\\\)language(\\/|\\\)(.*?)\.lang.php$',
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
            function($t) { return $t[0] == T_VARIABLE; }
        );

        foreach ($results as $result)
        {
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

        $this->log("Running a Quick Repair & Rebuild...");
        require_once('modules/Administration/QuickRepairAndRebuild.php');
        $RAC = new RepairAndClear();
        $actions = array('clearAll');
        $RAC->repairAndClearAll($actions, array('All Modules'), false, false);
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

        $this->logChange("-> Update SQL: " . $sql);

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
        $app_list_strings = return_app_list_strings_language('en_us');

        if (isset($app_list_strings[$listName])) {
            $list = array_keys($app_list_strings[$listName]);
            return $list;
        } else {
            $this->log("-> The list '{$listName}' was not found.");
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

        //only allow letters, numbers, spaces, and underscores
        $key = preg_replace("/[^a-z0-9\s\_]/i", ' ', $key);

        if ($storedKey !== $key) {
            //if key was changed, clean whitespace
            $key = preg_replace('!\s+!', ' ', $key);
            $key = trim($key);
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
     * Executes the repairs
     * @param array $args
     */
    public function execute(array $args)
    {
        if (isset($args['test']) && ($args['test'] == 'false' || $args['test'] == '0' || $args['test'] == false)) {
            $this->setTesting(false);
        }

        if ($this->isTesting) {
            $this->log("Running in test mode.");
        }
    }
}