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
    private $sqlCache = array();
    private $tokenList = array();
    public $changed;
    private $syntaxError;
    protected $loggerTitle = "Language";
    protected $foundIssues = array();

    function __construct()
    {
        parent::__construct();
    }

    /**
     * Executes the repairs
     */
    public function execute(array $args)
    {
        parent::execute($args);

        $customLanguageFiles = $this->getCustomLanguageFiles();

        $currentFileCount = 0;
        foreach ($customLanguageFiles as $fullPath => $relativePath) {
            $this->log("Processing {$fullPath}...");
            $currentFileCount++;
            $result = $this->testLanguageFile($fullPath);
            switch ($result) {
                case self::TYPE_SYNTAXERROR:
                    $this->foundIssues[$fullPath] = $fullPath;
                    $this->log("-> File has syntax error: {$this->syntaxError}. This will need to be corrected manually.");
                    break;
                case self::TYPE_UNREADABLE:
                    $this->foundIssues[$fullPath] = $fullPath;
                    $this->log("-> File is not readable. Please correct your filesystem permissions and try again.");
                    break;
                case self::TYPE_UNWRITABLE:
                    $this->foundIssues[$fullPath] = $fullPath;
                    $this->log("-> File is not writable. Please correct your filesystem permissions and try again.");
                    break;
                case self::TYPE_EMPTY:
                    $this->foundIssues[$fullPath] = $fullPath;
                    if (!$this->isTesting) {
                        unlink($fullPath);
                        $this->log("-> Deleted the file.");
                    } else {
                        $this->log("-> Will delete file.");
                    }
                    break;
                case self::TYPE_DYNAMIC:
                    $this->foundIssues[$fullPath] = $fullPath;
                    $this->log("-> File has code present. This will need to be corrected manually.");
                    break;
                case self::TYPE_STATIC:
                    $this->repairStaticFile($fullPath);
                    break;
            }
        }

        $foundIssuesCount = count($this->foundIssues);
        $this->log("Found {$foundIssuesCount} bad language files.");

        if (!$this->isTesting) {
            $this->runQRAR();
        }

        //execute the workflow repairs to correct any language updates
        $workflowRepair = new supp_WorkflowRepairs();
        $workflowRepair->execute($args);

        //execute the report repairs to correct or notify of any language updates
        $reportRepair = new supp_ReportRepairs();
        $reportRepair->execute($args);
    }

    /**
     * @param string $fileName
     */
    private function repairStaticFile($fileName)
    {
        //Next run the file through the tests and fill the new array
        $tokensByLine = $this->processTokenList(sugar_file_get_contents($fileName));

        if ($this->changed) {
            $this->foundIssues[$fileName] = $fileName;
            if (!$this->isTesting) {
                $this->writeNewFile($tokensByLine, $fileName);
            } else {
                $this->log("-> Will need to rewrite {$fileName}.");
            }
        } else {
            $this->log("-> No Changes.");
        }
    }

    /**
     * This is the 'Token Engine'  If finds arrays and labels the individual parts for later
     *    processing.  It also gets rid of 'GLOBALS' in place.
     *
     * @param string $fileContents - The contents of the file to be parsed
     * @return array
     */
    public function getAnnotatedTokenList($fileContents)
    {
        $processedTokenList = array();
        $globalsFlag = false;
        $drop = false;
        $counter = 0;
        $localTokenList = token_get_all($fileContents);
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
                        $keyList[1] = "\$" . trim($keyList[1], "'\"");
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
     * @param $fileContents
     * @return array
     */
    public function processTokenList($fileContents)
    {
        $this->changed = false;
        $tokensByLine = array();
        $lineNumber = 0;

        $this->tokenList = $this->getAnnotatedTokenList($fileContents);

        foreach ($this->tokenList as $index => $keyList) {
            if (is_array($keyList)) {
                $lineNumber = $keyList[2];
                switch ($keyList['TOKEN_NAME']) {
                    case 'T_ARRAY_NAME':
                        $tokenListName = $keyList[1];
                        break;
                    case 'T_ARRAY_KEY':
                        $oldKey = $keyList[1];
                        $keyList[1] = $this->getValidLanguageKeyName($keyList[1]);
                        if ($oldKey != $keyList[1]) {
                            //OK a key has changed, now we need to update everything
                            $this->changed = true;
                            //Sometimes the values come though as 'value', we need to get rid of the tick marks
                            $oldKey = trim($oldKey, "'\"");
                            $newKey = trim($keyList[1], "'\"");
                            if (!empty($tokenListName)) {
                                $listNameInfo = $this->findListField($tokenListName);
                                if (!empty($listNameInfo)) {
                                    $this->updateDatabase($listNameInfo, $oldKey, $newKey);
                                    $this->updateFieldsMetaDataTable($listNameInfo, $oldKey, $newKey);
                                    $this->scanFiles($oldKey, $newKey);
                                } else {
                                    //just scan files and dont update fields
                                    $this->scanFiles($oldKey, $newKey);
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
     * @param bool $testData
     */
    private function scanFiles($oldKey, $newKey)
    {
        //We only need to get this list once, it wont change
        if (empty($this->customOtherFileList)) {
            $this->customOtherFileList = $this->getCustomVardefFiles();
        }

        foreach ($this->customOtherFileList as $fullPath => $relativePath) {
            $this->updateFiles($oldKey, $newKey, $fullPath, $relativePath, sugar_file_get_contents($fullPath));
        }
    }

    /**
     * @param $oldKey
     * @param $newKey
     * @param $fullPath
     * @param $relativePath
     * @param $fileContents
     * @return array|mixed
     */
    public function updateFiles($oldKey, $newKey, $fullPath, $relativePath, $fileContents)
    {
        //todo: need to capture before/after info
        $searchString1 = "'" . trim($oldKey, "'\"") . "'";
        $searchString2 = '"' . trim($oldKey, "'\"") . '"';

        //TODO: Convert this to regex

        if (strpos($fileContents, $searchString1) !== false ||
            strpos($fileContents, $searchString2) !== false
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

            $newText = str_replace($oldText, $newText, $fileContents, $count);

            if ($count == 0) {
                //There were no changes so this file will have to be examined manually
                $this->log("->  Key '{$oldKey}' was found but could not be changed to '{$newKey}'. This may need to be manually corrected.");
            } else {

                if (!$this->isTesting) {
                    $this->log("-> Updating key from '{$oldKey}' to '{$newKey}' in '{$fullPath}'");
                    $this->capture($this->cycle_id, $this->loggerTitle, 'File', $fullPath, file_get_contents($fullPath), $newText, "Backing up '{$fullPath}'", 'Completed', 'P3');
                    sugar_file_put_contents($fullPath, $newText, LOCK_EX);
                } else {
                    $this->log("-> Will update key from '{$oldKey}' to '{$newKey}' in '{$fullPath}'");
                }
            }
            return $newText;
        } else {
            return $fileContents;
        }
    }

    /**
     * This function updated the fields_meta_data table looking for default values that need changing
     *
     * @param $fieldData
     * @param $newKey
     * @param $oldKey
     */
    public function updateFieldsMetaDataTable($fieldData, $oldKey, $newKey)
    {
        $hash = $GLOBALS['db']->fetchOne("SELECT * FROM fields_meta_data
                                          WHERE default_value LIKE '%^{$oldKey}^%' OR
                                                default_value = '{$oldKey}' OR
                                                ext4 LIKE '%{$oldKey}%'");
        if ($hash != false) {
            //back up the database table if it has not been backed up yet.
            if (!$this->isBackedUpTable('fields_meta_data')) {
                $this->backupTable('fields_meta_data');
            }

            foreach ($fieldData as $moduleName => $fieldName) {
                $sql = "SELECT id FROM fields_meta_data
                        WHERE custom_module='{$moduleName}'
                          AND (default_value LIKE '%^{$oldKey}^%' OR default_value = '{$oldKey}')
                          AND ext1='{$fieldName}'";
                $result = $GLOBALS['db']->query($sql, true, "Error updating fields_meta_data.");
                while ($hash = $GLOBALS['db']->fetchByAssoc($result)) {
                    $sql = "UPDATE fields_meta_data
                           SET default_value = REPLACE(default_value, '{$oldKey}', '{$newKey}')
                           WHERE id = '{$hash['id']}'";
                    //don't bother running the same query twice or at all if we are in testing mode
                    if (!in_array($sql, $this->queryCache) && !$this->isTesting) {
                        $this->updateQuery($sql);
                    }
                    $this->queryCache[] = $sql;
                }

                //catch new dependencies
                $sql = "SELECT id FROM fields_meta_data
                        WHERE custom_module='{$moduleName}'
                          AND (ext4 LIKE '%{$oldKey}%')
                          AND ext1='{$fieldName}'";
                $result = $GLOBALS['db']->query($sql, true, "Error updating fields_meta_data.");
                while ($hash = $GLOBALS['db']->fetchByAssoc($result)) {
                    $sql = "UPDATE fields_meta_data
                           SET ext4 = REPLACE(default_value, '{$oldKey}', '{$newKey}')
                           WHERE id = '{$hash['id']}'";
                    //don't bother running the same query twice or at all if we are in testing mode
                    if (!in_array($sql, $this->queryCache) && !$this->isTesting) {
                        $this->updateQuery($sql);
                    }
                    $this->queryCache[] = $sql;
                }
            }
            $this->log("-> {$oldKey} found as a default value or in a dependency in fields_meta_data table.");
        }
    }

    /**
     * This updates the tables in the database, it automatically detects if it is in the stock table or the custom table
     *
     * @param array $fieldData
     * @param string $oldKey
     * @param string $newValue
     * @return array
     */
    public function updateDatabase($fieldData, $oldKey, $newValue)
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

                $hash = $GLOBALS['db']->fetchOne("SELECT * FROM {$table} WHERE {$fieldName} LIKE '%^{$oldKey}^%' OR {$fieldName} = '{$oldKey}'");
                if ($hash != false) {
                    //back up the database table if it has not been backed up yet.
                    if (!$this->isBackedUpTable($table)) {
                        $this->backupTable($table);
                    }

                    $sql = "SELECT id FROM {$table}
                            WHERE {$fieldName} LIKE '%^{$oldKey}^%' OR
                                  {$fieldName} = '{$oldKey}'";
                    $result = $GLOBALS['db']->query($sql, true, "Error updating fields_meta_data.");
                    while ($hash = $GLOBALS['db']->fetchByAssoc($result)) {
                        $sql = "UPDATE {$table}
                                SET {$fieldName} = REPLACE({$fieldName}, '{$oldKey}', '{$newValue}')
                                WHERE {$fieldName} LIKE '%^{$oldKey}^%' OR
                                  {$fieldName} = '{$oldKey}'";
                        //don't bother running the same query twice or at all if we are in testing mode
                        if (!in_array($sql, $this->queryCache) && !$this->isTesting) {
                            $this->updateQuery($sql);
                        }
                        $this->queryCache[] = $sql;
                    }
                }
            }
        }
    }

    /**
     * @param string $listName
     * @return array
     */
    public function findListField($listName)
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
            $this->log("-> The list {$listName} is not used by any fields. This is just for informational purposes.");
        } else {
            $this->log("-> Found {$listName} in bean '{$bean} in field '{$fieldName}'");
        }

        $this->arrayCache[$listName] = $retArray;
        return $retArray;
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
                $syntaxError .= "Error: '{$msg}' in line {$line} in {$fileName}\n";
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
    private function writeNewFile($tokenList, $fileName)
    {
        $this->log("-> Writing file '{$fileName}'");
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

        if (!$this->isTesting) {
            if (is_file($fileName)) {
                $this->capture($this->cycle_id, $this->loggerTitle, 'File', $fileName, file_get_contents($fileName), implode("\n", $assembledFile), "Backing up '{$fileName}'", 'Completed', 'P3');
                //Update the file but retain its modified and access date stamps
                $fileAccessTime = date('U', fileatime($fileName));
                $fileModifiedTime = date('U', filemtime($fileName));
                sugar_file_put_contents($fileName, $assembledFile, LOCK_EX);
                sugar_touch($fileName, $fileModifiedTime, $fileAccessTime);
            } else {
                $this->capture($this->loggerTitle, 'File', 'Completed', 'P3', "Writing file '{$fileName}'", '', implode("\n", $assembledFile));
                sugar_file_put_contents($fileName, $assembledFile, LOCK_EX);
            }
        }

        return $assembledFile;
    }
}
