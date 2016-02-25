<?php

require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_Repairs.php');

class supp_LanguageRepairs extends supp_Repairs
{
    const TYPE_EMPTY = 0;
    const TYPE_DYNAMIC = 1;
    const TYPE_STATIC = 2;
    const TYPE_UNREADABLE = 3;
    const TYPE_UNWRITABLE = 4;
    const TYPE_SYNTAXERROR = 5;

    const SEV_HIGH = 2;
    const SEV_MEDIUM = 1;
    const SEV_LOW = 0;

    public $customOtherFileList = array();

    private $dynamicTokens = array('T_OBJECT_OPERATOR', 'T_DOUBLE_COLON', 'T_CONCAT');
    private $arrayCache = array();
    private $queryCache = array();
    private $tokenList = array();
    private $changed;
    private $syntaxError;
    private $reportKeys = array();
    private $tableBackupFlag = array();

    function __construct()
    {
        parent::__construct();
        $this->preLoadReportData();
    }

    /**
     * Executes the repairs
     * @param bool $isTesting
     */
    public function execute($isTesting = false)
    {
        $customLanguageFiles = $this->getCustomLanguageFiles($isTesting);

        $currentFileCount = 0;
        foreach ($customLanguageFiles as $fullPath => $relativePath) {
            $currentFileCount++;
            $result = $this->testLanguageFile($fullPath);
            switch ($result) {
                case self::TYPE_SYNTAXERROR:
                    $this->capture('File', $relativePath, "Syntax Error in file: {$relativePath} ({$this->syntaxError})", 'Review', self::SEV_HIGH);
                    $this->log("Syntax Error in file: {$relativePath}", 'Review', self::SEV_HIGH);
                    break;
                case self::TYPE_UNREADABLE:
                    $this->capture('File', $relativePath, "Unreadable file: {$relativePath}", 'Review', self::SEV_HIGH);
                    $this->log("Unreadable file: {$relativePath}", 'Review', self::SEV_HIGH);
                    break;
                case self::TYPE_UNWRITABLE:
                    $this->capture('File', $relativePath, "Unwritable file: {$relativePath}", 'Review', self::SEV_HIGH);
                    $this->log("Unwritable file: {$relativePath}", 'Review', self::SEV_HIGH);
                    break;
                case self::TYPE_EMPTY:
                    $this->capture('File', $relativePath, "Deleted file: {$relativePath}", 'Updated', self::SEV_HIGH, file_get_contents($fullPath));
                    if (!$isTesting) {
                        unlink($fullPath);
                    }
                    $this->log("Deleted file: {$relativePath}", 'Review', self::SEV_HIGH);
                    break;
                case self::TYPE_DYNAMIC:
                    $this->capture('File', $relativePath, "Dynamic file: {$relativePath}", 'Review', self::SEV_HIGH);
                    $this->log("Dynamic file: {$relativePath}", 'Review', self::SEV_HIGH);
                    break;
                case self::TYPE_STATIC:
                    $this->repairStaticFile($fullPath);
                    break;
            }
        }
        $this->runRebuildWorkflow();
        $this->runQRAR();
    }

    /**
     * @param string $fileName
     */
    private function repairStaticFile($fileName, $isTesting = false)
    {
        $this->log("Processing {$fileName}");

        //Next run the file through the tests and fill the new array
        $tokensByLine = $this->processTokenList($fileName);

        if ($isTesting) {
            return $tokensByLine;
        } elseif ($this->changed) {
            $this->writeNewFile($tokensByLine, $fileName, $isTesting);
        } else {
            $this->log("-> No Changes");
        }
    }

    /**
     * This is the 'Token Engine'  If finds arrays and labels the individual parts for later
     *    processing.  It also gets rid of 'GLOBALS' in place.
     *
     * @param string $fileName - The name and path to the file
     * @return array
     */
    private function getAnnotatedTokenList($fileName)
    {
        $processedTokenList = array();
        $globalsFlag = false;
        $drop = false;
        $counter = 0;
        $localTokenList = token_get_all(file_get_contents($fileName));
        foreach ($localTokenList as $index => $keyList) {
            if (is_array($keyList)) {
                $tokenNumber = $keyList[0];
                $keyList['TOKEN_NAME'] = token_name($tokenNumber);
                //eliminate white space
                if ($keyList['TOKEN_NAME'] == "T_WHITESPACE") {
                    $drop = true;
                }
                if ($keyList[1] == "\$GLOBALS") {
                    $drop = true;
                    $globalsFlag = true;
                }
                //We need to convert the index to an actual variable
                if ($globalsFlag) {
                    if ($keyList['TOKEN_NAME'] == "T_CONSTANT_ENCAPSED_STRING") {
                        $keyList[1] = "\$" . trim($keyList[1], "'");
                        $keyList['TOKEN_NAME'] = 'T_VARIABLE';
                    }
                }
            } else {
                if ($globalsFlag) {
                    if ($keyList == "[" || $keyList == "]") {
                        if ($keyList == "]") {
                            $globalsFlag = false;
                        }
                        $drop = true;
                    }
                }
            }
            if (!$drop) {
                $processedTokenList[$counter] = $keyList;
                $counter++;
            }
            $drop = false;
        }

        //second pass
        $complexArray = false;
        foreach ($processedTokenList as $index => $keyList) {
            if (is_array($keyList)) {
                if ($keyList['TOKEN_NAME'] == 'T_VARIABLE') {
                    if ($keyList[1] == "\$app_list_strings") {
                        //complex array
                        if ((isset($processedTokenList[$index + 5]['TOKEN_NAME']) &&
                            $processedTokenList[$index + 5]['TOKEN_NAME'] == 'T_ARRAY')
                        ) {
                            $complexArray = true;
                        }
                        //simple array
                        if ($processedTokenList[$index + 7] == '=') {
                            $processedTokenList[$index + 5]['TOKEN_NAME'] = 'T_ARRAY_KEY';
                        }
                        $processedTokenList[$index + 2]['TOKEN_NAME'] = 'T_ARRAY_NAME';
                    }
                }
                if ($keyList['TOKEN_NAME'] == 'T_DOUBLE_ARROW' && $complexArray) {
                    $processedTokenList[$index - 1]['TOKEN_NAME'] = 'T_ARRAY_KEY';
                }
            } else {
                if ($keyList == ';' && $complexArray) {
                    $complexArray = false;
                }
            }
        }
        return $processedTokenList;
    }

    /**
     * @param string $fileName
     * @return array
     */
    public function processTokenList($fileName)
    {
        $this->changed = false;
        $tokensByLine = array();
        $lineNumber = 0;

        $this->tokenList = $this->getAnnotatedTokenList($fileName);

        foreach ($this->tokenList as $index => $keyList) {
            if (is_array($keyList)) {
                $lineNumber = $keyList[2];
                switch ($keyList['TOKEN_NAME']) {
                    case 'T_ARRAY_NAME':
                        $tokenListName = $keyList[1];
                        break;
                    case 'T_ARRAY_KEY':
                        $oldValue = $keyList[1];
                        $keyList[1] = $this->fixIndexNames(trim($keyList[1], "'"));
                        if ($oldValue != $keyList[1]) {
                            //OK a key has changed, now we need to update everything
                            $this->changed = true;
                            //Sometimes the values come though as 'value', we need to get rid of the tick marks
                            $oldValue = trim($oldValue, "'");
                            $newKey = trim($keyList[1], "'");
                            if (!empty($tokenListName)) {
                                $listNameInfo = $this->findListField($tokenListName);
                                if (!empty($listNameInfo)) {
                                    $this->updateDatabase($listNameInfo, $oldValue, $newKey);
                                    $this->updateFieldsMetaDataTable($listNameInfo, $oldValue, $newKey);
                                    $this->updateFiles($oldValue, $newKey);
                                    $this->updateReportFilters($oldValue, $newKey);
                                    $this->updateWorkFlow($oldValue, $newKey);
                                } else {
                                    $this->updateFiles($oldValue, $newKey);
                                    $this->updateReportFilters($oldValue, $newKey);
                                    $this->updateWorkFlow($oldValue, $newKey);
                                    $this->log("ERROR: No list name for {$keyList[1]}.");
                                }
                            }
                        }
                        break;
                    case 'T_CONSTANT_ENCAPSED_STRING':
                    default:
                        break;
                }
            }
            $tokensByLine[$lineNumber][] = $keyList;
        }
        return $tokensByLine;
    }

    /**
     * @param string $oldKey
     * @param string $newKey
     * @param bool $isTesting
     */
    private function updateReportFilters($oldKey, $newKey, $isTesting = false)
    {
        $jsonObj = getJSONobj();
        foreach ($this->reportKeys as $reportID => $filterKeys) {
            if ($this->recursiveValueSearch($oldKey, $filterKeys) !== false) {
                $savedReport = BeanFactory::getBean('Reports', $reportID);
                $reportContent = $jsonObj->decode(html_entity_decode($savedReport->content));
                //do a global search and replace for the old key
                $newReportContent = $this->recursive_array_replace($oldKey, $newKey, $reportContent);
                //reenclode the contect
                $encodedContent = $jsonObj->encode(htmlentities($newReportContent));
                $savedReport->content = $encodedContent;
                //back up the database table if it has not been backed up yet.
                if (in_array('saved_reports', $this->tableBackupFlag) == false && !$isTesting) {
                    $this->tableBackupFlag['saved_reports'] = 'saved_reports';
                    $this->backupTable('saved_reports', "FLF");
                }
                //now save the record
                $savedReport->save();
                $this->log("Report {$reportID} saved with new key '{$newKey}'");
            }
        }
    }

    /**
     * @param $find - What to find
     * @param $replace - What to replace it with
     * @param $array - The array to search
     * @return array|mixed - The returned updated array
     */
    private function recursive_array_replace($find, $replace, $array)
    {
        if (!is_array($array)) {
            return str_replace($find, $replace, $array);
        }
        $newArray = array();
        foreach ($array as $key => $value) {
            $newArray[$key] = $this->recursive_array_replace($find, $replace, $value);
        }
        return $newArray;
    }

    /**
     * Update workflow data, this function only updates the tables, the rebuild workflow repair run at the end
     *   of the execute() will rebuild the files.
     *
     * @param $oldKey
     * @param $newKey
     * @param bool $isTesting
     */
    private function updateWorkFlow($oldKey, $newKey, $isTesting = false)
    {
        //TriggerShells
        $sql = "SELECT id AS numOfChagesNeeded FROM workflow_triggershells WHERE eval LIKE \"%'{$oldKey}'%\"";
        $hash = $GLOBALS['db']->fetchOne($sql);
        if ($hash != false) {
            if (!in_array('workflow_triggershells', $this->tableBackupFlag)) {
                $this->backupTable('workflow_triggershells' . 'FLF');
                $this->tableBackupFlag['workflow_triggershells'] = 'workflow_triggershells';
            }
            $sql = "UPDATE workflow_triggershells eval = REPLACE(eval, '{$oldKey}', '{$newKey}')
                        WHERE eval LIKE \"%'{$oldKey}'%\"";
            $result = $GLOBALS['db']->query($sql);
            $this->log("-> Updated workflow_triggershells");
        }

        //Actions
        $sql = "SELECT count(id) AS numOfChagesNeeded FROM workflow_actions
                  WHERE value = \"{$oldKey}\" OR
                       (value LIKE \"{$oldKey}^%\" OR
                        value LIKE \"%^{$oldKey}\" OR
                        value LIKE \"%^{$oldKey}^%\")";
        $hash = $GLOBALS['db']->fetchOne($sql);
        if ($hash != false) {
            if (!in_array('workflow_actions', $this->tableBackupFlag)) {
                $this->backupTable('workflow_actions' . 'FLF');
                $this->tableBackupFlag['workflow_actions'] = 'workflow_actions';
            }
            $sql = "UPDATE workflow_actions value = REPLACE(value, '{$oldKey}', '{$newKey}')
                            WHERE value = \"{$oldKey}\" OR
                                 (value LIKE \"{$oldKey}^%\"
                                  value LIKE \"%^{$oldKey}\"
                                  value LIKE \"%^{$oldKey}^%\")";
            $result = $GLOBALS['db']->query($sql);
            $this->log("-> Updated workflow_actions");
        }
    }

    /**
     * Preload the data from reports to speed up the process later
     */
    private function preLoadReportData()
    {
        $jsonObj = getJSONobj();
        $this->log("Preloading data from Reports module.");
        $sql = "SELECT id,name FROM saved_reports WHERE deleted=0";
        $result = $GLOBALS['db']->query($sql);
        $trash = array();
        while ($hash = $GLOBALS['db']->fetchByAssoc($result, false)) {
            $savedReport = BeanFactory::getBean('Reports', $hash['id']);
            $reportContent = $jsonObj->decode(html_entity_decode($savedReport->content));
            if (array_key_exists('filters_def', $reportContent)) {
                $this->reportKeys[$hash['id']] = $reportContent['filters_def'];
            }
        }
    }

    /**
     * @param $needle = The VALUE you are looking for
     * @param array $array - The array to search
     * @return bool
     */
    private function recursiveValueSearch($needle, array $array)
    {
        foreach ($array as $key => $value) {
            if ($value == $needle) {
                return $value;
            }
            if (is_array($value)) {
                if ($x = $this->recursiveValueSearch($needle, $value)) {
                    return $x;
                }
            }
        }
        return false;
    }

    /**
     * Processes Vardef files looking for the keys that need changing
     *
     * @param string $searchString
     * @param string $oldKey
     * @param bool $isTesting
     */
    private function updateFiles($oldKey, $newKey, $isTesting = false)
    {
        //TODO: Convert this to regex
        //TODO: TODO: learn regex
        //We only need to get this list once, it wont change
        if (empty($this->customOtherFileList)) {
            $this->customOtherFileList = $this->getCustomVardefFiles();
        }

        $searchString1 = "'" . $oldKey . "'";
        $searchString2 = '"' . $oldKey . '"';

        foreach ($this->customOtherFileList as $fullPath => $relativePath) {
            $text = sugar_file_get_contents($fullPath);
            if (strpos($text, $searchString1) !== false ||
                strpos($text, $searchString2) !== false
            ) {
                $oldText = array(
                    "=> '{$oldKey}'",
                    "=> \"{$oldKey}\"",
                    "=>'{$oldKey}'",
                    "=>\"{$oldKey}\"",
                    "= '{$oldKey}'",
                    "= \"{$oldKey}\"",
                    "='{$oldKey}'",
                    "=\"{$oldKey}\""
                );
                $newText = array(
                    "=> '{$newKey}'",
                    "=> \"{$newKey}\"",
                    "=>'{$newKey}'",
                    "=>\"{$newKey}\"",
                    "= '{$newKey}'",
                    "= \"{$newKey}\"",
                    "='{$newKey}'",
                    "=\"{$newKey}\""

                );
                $newText = str_replace($oldText, $newText, $text, $count);
                if ($count == 0) {
                    //There were no changes so this file will have to be examined manually
                    $this->capture("File", $relativePath, "Key '{$oldKey}' found but could not be changed to '{$newKey}'.", 'Review', self::SEV_HIGH);
                } else {
                    $this->capture("File", $relativePath, "Key 'Vardef file updated'.", 'Updated', self::SEV_LOW, $text, $newText);
                    $this->log("-> Updated Vardefs file '{$fullPath}'");
                    if ($isTesting) {
                        return $newText;
                    } else {
                        sugar_file_put_contents($fullPath, $newText, LOCK_EX);
                    }
                }
            }
        }
    }

    /**
     * This function updated the fields_meta_data table looking for default values that need changing
     *
     * @param $fieldData
     * @param $newKey
     * @param $oldKey
     * @param bool $isTesting
     */
    private function updateFieldsMetaDataTable($fieldData, $newKey, $oldKey, $isTesting = false)
    {
        $hash = $GLOBALS['db']->fetchOne("SELECT * FROM fields_meta_data WHERE default_value LIKE '%^{$oldKey}^%' OR default_value = '{$oldKey}'");
        if ($hash != false) {
            //back up the database table if it has not been backed up yet.
            if (in_array('fields_meta_data', $this->tableBackupFlag) == false && !$isTesting) {
                $this->tableBackupFlag['fields_meta_data'] = 'fields_meta_data';
                $this->backupTable('fields_meta_data', "FLF");
            }

            foreach ($fieldData as $moduleName => $fieldName) {
                $query = str_replace(array("\r", "\n"), "", "UPDATE fields_meta_data
                        SET default_value = REPLACE(default_value, '{$oldKey}', '{$newKey}')
                        WHERE custom_module='{$moduleName}'
                          AND (default_value LIKE '%^{$oldKey}^%' OR default_value = '{$oldKey}')
                          AND ext1='{$fieldName}'");
                $query = preg_replace('/\s+/', ' ', $query);
                //dont bother running the same query twice
                if (!in_array($query, $this->queryCache)) {
                    $GLOBALS['db']->query($query, true, "Error updating fields_meta_data.");
                    $this->queryCache[] = $query;
                }
            }
        }
    }

    /**
     * This updates the tables in the database, it automatically detects if it is in the stock table or the custom table
     *
     * @param $fieldData
     * @param $oldValue
     * @param $newValue
     * @param bool $isTesting
     */
    private function updateDatabase($fieldData, $oldValue, $newValue, $isTesting = false)
    {
        if (!empty($fieldData)) {
            foreach ($fieldData as $module => $fieldName) {
                $bean = BeanFactory::getBean($module);
                $fieldDef = $bean->field_defs[$fieldName];
                if (array_key_exists('source', $fieldDef) && $fieldDef['source'] == 'custom_fields') {
                    $table = $bean->table_name . '_cstm';
                } else {
                    $table = $bean->table_name;
                }

                $hash = $GLOBALS['db']->fetchOne("SELECT * FROM {$table} WHERE {$fieldName} LIKE '%^{$oldValue}^%' OR {$fieldName} = '{$oldValue}'");
                if ($hash != false) {
                    //back up the database table if it has not been backed up yet.
                    if (in_array($table, $this->tableBackupFlag) == false && !$isTesting) {
                        $this->tableBackupFlag[$table] = $table;
                        $this->backupTable($table, "FLF");
                    }

                    $query = str_replace(array("\r", "\n"), "", "UPDATE {$table}
                            SET {$fieldName} = REPLACE({$fieldName}, '{$oldValue}', '{$newValue}')
                            WHERE {$fieldName} LIKE '%^{$oldValue}^%' OR
                                  {$fieldName} = '{$oldValue}'");
                    $query = preg_replace('/\s+/', ' ', $query);
                    //dont bother running the same query twice
                    if (!in_array($query, $this->queryCache)) {
                        $GLOBALS['db']->query($query, true, "Error updating {$table}.");
                        $this->queryCache[] = $query;
                    }
                }
            }
        }
    }

    /**
     * @param $listName
     * @return array
     */
    private function findListField($listName)
    {
        global $beanList;
        $retArray = array();

        //if the array as already been processed then just return the value
        if (isset($this->arrayCache[$listName])) {
            return $this->arrayCache[$listName];
        }

        foreach ($beanList as $bean => $object) {
            $focus = BeanFactory::getBean($bean);
            if (isset($focus->field_defs) && !empty($focus->field_defs)) {
                foreach ($focus->field_defs as $fieldName => $definitions) {
                    if (array_key_exists('options', $definitions) && $definitions['options'] == $listName) {
                        $retArray[$bean] = $fieldName;
                        break;
                    }
                }
            }
        }

        if (empty($retArray)) {
            $this->log("-> Could not locate {$listName}, it appears not to be used as a dropdown list");
        } else {
            $this->log("-> Found {$listName} in bean '{$bean} in field '{$fieldName}'");
        }

        $this->arrayCache[$listName] = $retArray;
        return $retArray;
    }

    /**
     * @param $oldKey
     * @return mixed
     */
    private function fixIndexNames($oldKey)
    {
        //Now go through and remove the characters [& / - ( )] and spaces (in some cases) from array keys
        $badChars = array(' & ', '&', ' - ', '-', ' / ', '/', '(', ')');
        $goodChars = array('_', '_', '_', '_', '_', '_', '', '');
        $newKey = str_replace($badChars, $goodChars, $oldKey, $count);
        return "'" . $newKey . "'";
    }

    /**
     * Tests a PHP file to see if it is a list of static variables or if it has dynamic content in it.
     *
     * Dynamic = $app_list_strings['LBL_EMAIL_ADDRESS_BOOK_TITLE_ICON'] =
     *      SugarThemeRegistry::current()->getImage('icon_email_addressbook',
     *                                              "",
     *                                              null,
     *                                              null,
     *                                              ".gif",
     *                                              'Address Book').' Address Book';
     *
     * Static = $app_list_strings['LBL_EMAIL_ADDRESS_BOOK_TITLE'] = 'Address Book';
     *
     * @param $fileName
     * @return int
     */
    private function testLanguageFile($fileName)
    {
        $varCounter = 0;

        //Check to see if we can process the files at all
        if (!is_readable($fileName)) {
            return self::TYPE_UNREADABLE;
        }
        if (!is_writable($fileName)) {
            return self::TYPE_UNWRITABLE;
        }

        // Get the shell output from the syntax check command
        $output = $this->testPHPSyntax(file_get_contents($fileName));

        // If there is output then there is an error
        if ($output !== false) {
            $syntaxError = "";
            foreach ($output as $msg => $line) {
                $syntaxError .= "\nError: '{$msg}' in line {$line}";
            }
            $this->syntaxError = $syntaxError;
            return self::TYPE_SYNTAXERROR;
        }

        $tokens = token_get_all(file_get_contents($fileName));
        foreach ($tokens as $index => $token) {
            if (is_array($token)) {
                $tokenText = token_name($token[0]);
            } else {
                //this isn't translated for some reason
                if ($tokens[$index] == '.') {
                    $tokenText = 'T_CONCAT';
                } else {
                    $tokenText = "";
                }
            }
            //Check to see if this line contains a variable.  If so
            // then this file isn't empty
            if ($tokenText == 'T_VARIABLE') {
                $varCounter++;
            }
            //Check to see if this line contains one of the
            // dynamic tokens
            if (in_array($tokenText, $this->dynamicTokens)) {
                return self::TYPE_DYNAMIC;
            }
        } //end foreach
        //If there were no variables in the file then it is considered empty
        if ($varCounter == 0) {
            return self::TYPE_EMPTY;
        }
        return self::TYPE_STATIC;
    }

    /**
     * @param array $tokenList
     * @param string $fileName
     */
    private function writeNewFile($tokenList, $fileName, $isTesting = false)
    {
        $assembledFile = array();
        foreach ($tokenList as $lineNumber => $contents) {
            $assembledFile[$lineNumber] = "";
            foreach ($contents as $index => $element) {
                if (is_array($element)) {
                    $assembledFile[$lineNumber] .= $element[1];
                } else {
                    $element = trim($element);
                    $assembledFile[$lineNumber] .= $element;
                }
            }
            if (substr($assembledFile[$lineNumber], -1) != "\n") {
                $assembledFile[$lineNumber] .= "\n";
            }
        }
        sugar_file_put_contents($fileName, $assembledFile, LOCK_EX);
    }

    /**
     * Logs an entry record in the Sugar Repair table
     * @param $target_type
     * @param $target
     * @param $value_before
     * @param $value_after
     */
    protected function capture($target_type, $target, $status, $priority, $description = '', $value_before = '', $value_after = '')
    {
        parent::capture($this->cycle_id, 'Language', $target_type, $target, $value_before, $value_after, $description, $status, $priority);
    }
}
