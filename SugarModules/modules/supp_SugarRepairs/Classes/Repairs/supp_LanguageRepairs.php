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
                    $this->logAction("-> File has syntax error: {$this->syntaxError}. This will need to be corrected manually.");
                    break;
                case self::TYPE_UNREADABLE:
                    $this->foundIssues[$fullPath] = $fullPath;
                    $this->logAction("-> File is not readable. Please correct your filesystem permissions and try again.");
                    break;
                case self::TYPE_UNWRITABLE:
                    $this->foundIssues[$fullPath] = $fullPath;
                    $this->logAction("-> File is not writable. Please correct your filesystem permissions and try again.");
                    break;
                case self::TYPE_EMPTY:
                    $this->foundIssues[$fullPath] = $fullPath;
                    if (!$this->isTesting) {
                        unlink($fullPath);
                        $this->logChange("-> Deleted the file.");
                    } else {
                        $this->logChange("-> Will delete file.");
                    }
                    break;
                case self::TYPE_DYNAMIC:
                    $this->foundIssues[$fullPath] = $fullPath;
                    $this->logAction("-> File has code present. This will need to be corrected manually.");
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
            $this->clearLanguagesCache();
        }

        //execute the vardef repairs to correct any language updates
        $vardefRepair = new supp_VardefRepairs();
        //copy cycle id
        $vardefRepair->setCycleId($this->cycle_id);
        $vardefRepair->execute($args);

        //execute the workflow repairs to correct any language updates
        $workflowRepair = new supp_WorkflowRepairs();
        //copy cycle id
        $workflowRepair->setCycleId($this->cycle_id);
        $workflowRepair->execute($args);

        //execute the report repairs to correct or notify of any language updates
        $reportRepair = new supp_ReportRepairs();
        //copy cycle id
        $reportRepair->setCycleId($this->cycle_id);
        $reportRepair->execute($args);
    }

    /**
     * @param string $fileName
     */
    private function repairStaticFile($fileName)
    {
        //Next run the file through the tests and fill the new array
        $tokensByLine = $this->processTokenList(file_get_contents($fileName), $fileName);

        if ($this->changed) {
            $this->foundIssues[$fileName] = $fileName;
            if (!$this->isTesting) {
                $assembledFile = array();
                foreach ($tokensByLine as $lineNumber => $contents) {
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

                $assembledFile = implode('', $assembledFile);
                $this->writeFile($fileName, $assembledFile);
            } else {
                $this->logChange("-> Will need to rewrite {$fileName}.");
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
    public function processTokenList($fileContents, $fileName = '')
    {
        $this->changed = false;
        $tokensByLine = array();
        $lineNumber = 0;
        $tokenListName="";

        $this->tokenList = $this->getAnnotatedTokenList($fileContents);

        foreach ($this->tokenList as $index => $keyList) {
            if (is_array($keyList)) {
                $lineNumber = $keyList[2];
                switch ($keyList['TOKEN_NAME']) {
                    case 'T_ARRAY_NAME':
                        $tokenListName = trim($keyList[1],"'\"");
                        break;
                    case 'T_ARRAY_KEY':
                        $oldKeyInQuotes = $keyList[1];
                        if(token_name($keyList[0])=='T_LNUMBER') {
                            //If the key is an integer then set its tyoe
                            // and skip the rest of the processing
                            settype($keyList[1], 'integer');
                            continue;
                        }
                        if(token_name($keyList[0])=='T_DNUMBER') {
                            //if there is a decimal, then convert to a string with quotes, we may
                            // skip the rest of the processing here too
                            $keyList[1]="'{$keyList[1]}'";
                            continue;
                        }
                        $cleanOldKey = trim($oldKeyInQuotes, "'\"");
                        $cleanTestKey = $this->getValidLanguageKeyName($cleanOldKey);
                        if ($cleanTestKey === false) {
                            $this->logAction("-> The converted key for '{$cleanOldKey}' in list '{$tokenListName}' will be empty. This will need to be manually corrected in studio.");
                            continue;
                        }

                        $testKeyInQuotes = "'{$cleanTestKey}'"; // need to rewrap this for string replacements

                        $currentOptions = $this->getListOptions($tokenListName);

                        if ($currentOptions == false) {
                            $this->logAction("-> A non-existent list ($tokenListName) was found in '{$fileName}'. This will need to be manually corrected.");
                            continue;
                        }

                        if ($cleanOldKey != $cleanTestKey && in_array($cleanTestKey, $currentOptions)) {
                            $this->logAction("-> The key '{$cleanOldKey}' in '{$fileName}' cannot be updated as '{$cleanTestKey}' already exists in the list '{$tokenListName}'. This will need to be manually corrected. List options are: " . print_r($currentOptions, true));
                        } else {
                            //Since numeric indexes come back without quotes we are going to test both side without quotes
                            if ($cleanOldKey != $cleanTestKey) {
                                $keyList[1] = $testKeyInQuotes;
                                //OK a key has changed, now we need to update everything
                                $this->changed = true;
                                //Sometimes the values come though as 'value', we need to get rid of the tick marks
                                if (!empty($tokenListName)) {
                                    $listNameInfo = $this->findListField(trim($tokenListName, "'\""));
                                    if (!empty($listNameInfo)) {
                                        $this->updateDatabase($listNameInfo, $cleanOldKey, $cleanTestKey);
                                    }
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
     * This updates the tables in the database, it automatically detects if it is in the stock table or the custom table
     *
     * @param array $fieldData
     * @param string $oldKey
     * @param string $newValue
     * @return array
     */
    public function updateDatabase($fieldData, $oldKey, $newKey)
    {
        if (!empty($fieldData)) {
            foreach ($fieldData as $module => $fieldNames) {
                $id_field_name = 'id';
                $bean = BeanFactory::getBean($module);
                foreach ($fieldNames as $fieldName) {
                    $fieldDef = $bean->field_defs[$fieldName];
                    if (array_key_exists('source', $fieldDef) && $fieldDef['source'] == 'custom_fields') {
                        $table = $bean->table_name . '_cstm';
                        $id_field_name = 'id_c';
                    } else {
                        $table = $bean->table_name;
                    }

                    $hash = $GLOBALS['db']->fetchOne("SELECT * FROM {$table} WHERE {$fieldName} LIKE '%^{$oldKey}^%' OR {$fieldName} = '{$oldKey}'");
                    if ($hash != false) {
                        if ($this->isTesting) {
                            $this->log("-> Will update database value '{$oldKey}' to '{$newKey}' in '{$table}.{$fieldName}'.");
                        } else {
                            $this->log("-> Updating database value '{$oldKey}' to '{$newKey}' in '{$table}.{$fieldName}'.");
                        }
                        //back up the database table if it has not been backed up yet.
                        if (!$this->isBackedUpTable($table)) {
                            $this->backupTable($table);
                        }

                        $sql = "SELECT {$id_field_name} FROM {$table}
                            WHERE {$fieldName} LIKE '%^{$oldKey}^%' OR
                                  {$fieldName} = '{$oldKey}'";
                        $result = $GLOBALS['db']->query($sql, true, "Error updating {$table}.");
                        while ($hash = $GLOBALS['db']->fetchByAssoc($result)) {
                            $id = $hash[$id_field_name];
                            $sql = "UPDATE {$table}
                                SET {$fieldName} = REPLACE({$fieldName}, '{$oldKey}', '{$newKey}')
                                WHERE {$id_field_name} = '{$id}'";
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
                        $retArray[$bean][] = $fieldName;
                    }
                }
            }
        }

        if (empty($retArray)) {
            $this->log("-> The list {$listName} is not used by any fields. This is just for informational purposes.");
        } else {
            $this->log("-> Found list '{$listName}' in '{$bean}' / '{$fieldName}'");
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
}
