<?php
/*********************************************************************************
 * Fix Language Files
 * Kenneth Brill (kbrill@sugarcrm.com)
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY KENNETH BRILL, KENNETH BRILL DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 *
 * @category   Language file repair script
 * @package    fixLanguageFiles
 * @author     Kenneth Brill <kbrill@sugarcrm.com>
 * @copyright  2015-2016 SugarCRM
 * @license    http://www.gnu.org/licenses/agpl.txt
 * @version    3.3
 */
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

global $current_user;

if (isset($_POST['step']) && $_POST['step'] == 'start') {
    $rlf = new fixLanguageFiles();

    //check to see if we make backups
    if (isset($_POST['makeBackups'])) {
        $rlf->makeBackups = true;
    }
    //check to see if we delete empty language files
    if (isset($_POST['deleteEmpty'])) {
        $rlf->deleteEmpty = true;
    }

    if (isset($_POST['lowLevelLog']) && $_POST['lowLevelLog'] == 1) {
        $rlf->lowLevelLog = true;
    } else {
        $rlf->lowLevelLog = false;
    }

    if (isset($_POST['compressWhitespace']) && $_POST['compressWhitespace'] == 1) {
        $rlf->compressWhitespace = true;
    } else {
        $rlf->compressWhitespace = false;
    }

    //Run the tests
    $rlf->processLanguageFiles();

    //Run a QR&R
    $GLOBALS['log']->debug("fixLanguageFiles: BEGIN QRR");
    require_once('modules/Administration/QuickRepairAndRebuild.php');
    $RAC = new RepairAndClear();
    $actions = array('clearAll');
    $RAC->repairAndClearAll($actions, array('All Modules'), false, $output);
    $GLOBALS['log']->debug("fixLanguageFiles: END QRR");

    $sugar_smarty = new Sugar_Smarty();
    $sugar_smarty->assign("MOD", $mod_strings);
    $sugar_smarty->assign("APP", $app_strings);

    $sugar_smarty->assign("RETURN_MODULE", "Administration");
    $sugar_smarty->assign("RETURN_ACTION", "index");
    $sugar_smarty->assign("DB_NAME", $db->dbName);

    $sugar_smarty->assign("MODULE", $currentModule);
    $sugar_smarty->assign("PRINT_URL", "index.php?" . $GLOBALS['request_string']);

    //result storage
    $sugar_smarty->assign("MANUALFIXFILES", implode("\n", $rlf->manualFixFiles));
    $sugar_smarty->assign("MODIFIEDFILES", implode("\n", $rlf->modifiedFiles));
    $sugar_smarty->assign("INDEXCHANGES", implode("\n", $rlf->indexChanges));
    $sugar_smarty->assign("REMOVEDFILES", implode("\n", $rlf->removedFiles));
    $sugar_smarty->assign("REMOVEDMODULES", implode("\n", $rlf->removedModules));

    $sugar_smarty->display("custom/modules/Administration/fixLanguageFilesResult.tpl");

    unlink("custom/fixLanguageFiles_Progress.php");
} else {
    $title = getClassicModuleTitle(
        "Administration",
        array(
            "<a href='index.php?module=Administration&action=index'>{$mod_strings['LBL_MODULE_NAME']}</a>",
            translate('LBL_FIXLANGUAGEFILES')
        ),
        false
    );

    global $currentModule;

    $GLOBALS['log']->info("Administration: fixLanguageFiles");

    $sugar_smarty = new Sugar_Smarty();
    $sugar_smarty->assign("MOD", $mod_strings);
    $sugar_smarty->assign("APP", $app_strings);

    $sugar_smarty->assign("TITLE", $title);

    $sugar_smarty->display("custom/modules/Administration/fixLanguageFiles.tpl");
}


class fixLanguageFiles
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

    public $customLanguageFileList = array();
    public $customOtherFileList = array();
    public $customListNames = array();
    public $totalFiles = 0;
    public $makeBackups = false;
    public $deleteEmpty = false;
    public $lowLevelLog = true;
    public $compressWhitespace = true;

    //result storage
    public $manualFixFiles = array();
    public $modifiedFiles = array();
    public $indexChanges = array();
    public $removedFiles = array();
    public $removedModules = array();

    private $dynamicTokens = array('T_OBJECT_OPERATOR', 'T_DOUBLE_COLON', 'T_CONCAT');
    private $arrayCache = array();
    private $queryCache = array();
    private $newTokenList = array("</php");
    private $changed;
    private $objectList;
    private $keyCount;
    private $beanFiles;
    private $beanList;
    private $syntaxError;
    private $reportKeys = array();

    public function __construct()
    {
        $beanList = $beanFiles = $objectList = array();
        require 'include/modules.php';
        $this->beanList = $beanList;
        $this->beanFiles = $beanFiles;
        $this->objectList = $objectList;

        $this->scanCustomDirectory();

        //Clean Up tem files
        if (file_exists('fixLanguageFiles.log')) {
            unlink('fixLanguageFiles.log');
        }
        if (file_exists("custom/fixLanguageFiles_Progress.php")) {
            unlink("custom/fixLanguageFiles_Progress.php");
        }

        $this->totalFiles = count($this->customLanguageFileList);
        $this->preLoadReportData();
    }

    /**
     * Fills the directory lists so we only have to scan it once.
     *
     * @param string $directory
     */
    private function scanCustomDirectory($directory = 'custom')
    {
        $GLOBALS['log']->debug("fixLanguageFiles: BEGIN scanCustomDirectory");
        $result = array();
        $path = realpath($directory);

        // Create recursive dir iterator which skips dot folders
        $dir = new RecursiveDirectoryIterator($path,
            FilesystemIterator::SKIP_DOTS);

        // Flatten the recursive iterator, folders come before their files
        $objects = new RecursiveIteratorIterator($dir,
            RecursiveIteratorIterator::SELF_FIRST);

        //in each of these we skip the custom/application/ and custom/modules/MODULE_NAME/Ext directories as they
        // will be updated after a QRR
        foreach ($objects as $name => $object) {
            if (!$object->isDir() &&
                stripos($name, DIRECTORY_SEPARATOR . 'Language' . DIRECTORY_SEPARATOR) !== false &&
                stripos($name, 'custom' . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR) === false &&
                (stripos($name, DIRECTORY_SEPARATOR . 'Ext' . DIRECTORY_SEPARATOR) === false ||
                    stripos($name, DIRECTORY_SEPARATOR . 'Extension' . DIRECTORY_SEPARATOR) !== false
                ) &&
                substr($name, -4) == '.php'
            ) {
                $this->customLanguageFileList[] = $name;
            } else if ((substr($name, -4) == '.php' ||
                    substr($name, -3) == '.js' ||
                    substr($name, -4) == '.tpl') &&
                (stripos($name, DIRECTORY_SEPARATOR . 'Ext' . DIRECTORY_SEPARATOR) === false ||
                    stripos($name, DIRECTORY_SEPARATOR . 'Extension' . DIRECTORY_SEPARATOR) !== false
                ) &&
                stripos($name, 'custom' . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR) === false
            ) {
                $this->customOtherFileList[] = $name;
            }
        }
        $GLOBALS['log']->debug("fixLanguageFiles: END scanCustomDirectory");
    }

    /**
     * Preload the data from reports to speed up the process later
     */
    private function preLoadReportData()
    {
        $sql = "SELECT id FROM reports";
        $result = $GLOBALS['db']->query($sql);
        while ($hash = $GLOBALS['db']->fetchByAssoc($result, false)) {
            $trash = $this->parseReportFilters($$hash['id'], null, null);
        }
    }

    /**
     * Returns the changed $reportContent if there are changes made or FALSE in there
     *  were no changes make
     *
     * @param string $reportID
     * @param string $oldKey
     * @param string $newKey
     * @return bool
     */
    private function parseReportFilters($reportID, $oldKey, $newKey)
    {
        $changed = false;
        $jsonObj = getJSONobj();
        $savedReport = BeanFactory::getBean('Reports', $reportID);
        $reportContent = $jsonObj->decode(html_entity_decode($savedReport->content));

        if (!is_array($this->reportKeys[$reportID])) {
            $this->reportKeys[$reportID] = array();
        }
        foreach ($reportContent['filters_def'] as $index => $filterGroup) {
            foreach ($filterGroup as $subIndex => $filterList) {
                if ($subIndex !== 'operator') {
                    foreach ($filterList as $subSubIndex => $filterIndex) {
                        if ($subSubIndex !== 'operator') {
                            foreach ($filterIndex['input_name0'] as $filterNameIndex => $filterValueIndex) {
                                $this->reportKeys[$reportID][$filterValueIndex] = $filterValueIndex;
                                if ($filterValueIndex == $oldKey) {
                                    $reportContent['filters_def'][$index][$subIndex][$subSubIndex]['input_name0'][$filterNameIndex] = $newKey;
                                    unset($this->reportKeys[$reportID][$filterValueIndex]);
                                    $this->reportKeys[$reportID][$newKey] = $newKey;
                                    $changed = true;
                                    $this->logThis("Filter for report {$reportID} changed from '{$filterValueIndex}' to '{$newKey}'", self::SEV_LOW);
                                }
                            }
                        }
                    }
                }
            }
        }
        if ($changed) {
            return $reportContent;
        } else {
            return false;
        }
    }

    /**
     * flatfile logger
     * @param string $entry
     * @param int $severity
     */
    public function logThis($entry, $severity = self::SEV_LOW)
    {
        global $mod_strings;

        if ($severity == self::SEV_LOW && $this->lowLevelLog = false) {
            return;
        }

        if (file_exists('include/utils/sugar_file_utils.php')) {
            require_once('include/utils/sugar_file_utils.php');
        }
        $log = 'fixLanguageFiles.log';

        // create if not exists
        $fp = @fopen($log, 'a+');
        if (!is_resource($fp)) {
            $GLOBALS['log']->fatal('fixLanguageFiles could not open/lock upgradeWizard.log file');
            die($mod_strings['ERR_UW_LOG_FILE_UNWRITABLE']);
        }

        $line = date('r') . " [{$severity}] - " . $entry . "\n";

        if (@fwrite($fp, $line) === false) {
            $GLOBALS['log']->fatal('fixLanguageFiles could not write to upgradeWizard.log: ' . $entry);
            die($mod_strings['ERR_UW_LOG_FILE_UNWRITABLE']);
        }

        if (is_resource($fp)) {
            fclose($fp);
        }
    }

    /**
     *
     */
    public function processLanguageFiles()
    {
        $GLOBALS['log']->debug("fixLanguageFiles: BEGIN processLanguageFiles");
        $currentFileCount = 0;
        foreach ($this->customLanguageFileList as $fileName) {
            $currentFileCount++;
            $result = $this->testLanguageFile($fileName);
            switch ($result) {
                case self::TYPE_SYNTAXERROR:
                    $this->logThis("Syntax Error in file: " . $this->truncateFileName($fileName) . ": {$this->syntaxError}", self::SEV_HIGH);
                    $this->manualFixFiles[$fileName] = $this->truncateFileName($fileName);
                    break;
                case self::TYPE_UNREADABLE:
                    $this->logThis("Unreadable file: " . $this->truncateFileName($fileName) . "", self::SEV_HIGH);
                    $this->manualFixFiles[$fileName] = $this->truncateFileName($fileName);
                    break;
                case self::TYPE_UNWRITABLE:
                    $this->logThis("Unwritable file: " . $this->truncateFileName($fileName) . "", self::SEV_HIGH);
                    $this->manualFixFiles[$fileName] = $this->truncateFileName($fileName);
                    break;
                case self::TYPE_EMPTY:
                    $this->logThis("Empty language file: " . $this->truncateFileName($fileName));
                    if ($this->deleteEmpty) {
                        unlink($fileName);
                        $this->removedFiles[] = $this->truncateFileName($fileName);
                        $this->logThis("-> Deleted file");
                    }
                    break;
                case self::TYPE_DYNAMIC:
                    $this->logThis("You will need to manually update: " . $this->truncateFileName($fileName), self::SEV_HIGH);
                    $this->manualFixFiles[$fileName] = $this->truncateFileName($fileName);
                    break;
                case self::TYPE_STATIC:
                    $this->repairStaticFile($fileName);
                    break;
            }
            $this->updateProgress($currentFileCount, $this->totalFiles);
        }
        $GLOBALS['log']->debug("fixLanguageFiles: END processLanguageFiles");
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
        $GLOBALS['log']->debug("fixLanguageFiles: BEGIN testLanguageFile: {$fileName}");
        $varCounter = 0;

        //Check to see if we can process the files at all
        if (!is_readable($fileName)) {
            return self::TYPE_UNREADABLE;
        }
        if (!is_writable($fileName)) {
            return self::TYPE_UNWRITABLE;
        }

        // Get the shell output from the syntax check command
        //$output = shell_exec('php -l "' . $fileName . '"');
        $output = $this->testPHPSyntax(file_get_contents($fileName));

        // If the error text above was matched, throw an exception containing the syntax error
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
            if (in_array($token[0], $this->dynamicTokens)) {
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
     * @param $code
     * @return string
     *
     */
    private function testPHPSyntax($code)
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

    private function truncateFileName($fileName)
    {
        return str_replace($_SERVER['CONTEXT_DOCUMENT_ROOT'] . '/', "", $fileName);
    }

    /**
     * @param string $fileName
     */
    private function repairStaticFile($fileName)
    {
        $GLOBALS['log']->debug("fixLanguageFiles: BEGIN repairStaticFile {$fileName}");
        $this->logThis("Processing " . $this->truncateFileName($fileName));

        //Next run the file through the tests and fill the new array
        $tokensByLine = $this->processTokenList($fileName);

        if ($this->changed) {
            $this->changed = false;
            $this->modifiedFiles[$fileName] = $this->truncateFileName($fileName);
            $this->backupFile($fileName);
            $flags = LOCK_EX;
            $this->writeNewFile($tokensByLine, $fileName, $flags);
        } else {
            $this->logThis("-> No Changes");
        }

        //Put the language files back
        $GLOBALS['log']->debug("fixLanguageFiles: END repairStaticFile");
    }

    /**
     * @param string $fileName
     * @return array
     */
    public function processTokenList($fileName = '')
    {
        $tokensByLine = array();
        $lineNumber = 0;
        $globalsFlag = false;
        $doubleArrowFlag = false;
        $tokens = token_get_all(file_get_contents($fileName));

        foreach ($tokens as $index => $keyList) {
            if (is_array($tokens[$index])) {
                //we are always interested in the line number
                $lineNumber = $keyList[2];
                //Add the token name to the array
                $tokenNumber = $keyList[0];
                $keyList['TOKEN_NAME'] = token_name($tokenNumber);
                $keyList[1]=trim($keyList[1],"'");

                //Keep these for future reference
                if ($keyList['TOKEN_NAME'] == 'T_CONSTANT_ENCAPSED_STRING') {
                    $currentNameString = $keyList[1];
                    $currentNameIndex = count($tokensByLine[$lineNumber]);
                    $currentNameLineNumber = $lineNumber;
                }

                //Find ARRAY_KEYS in multiline arrays
                if ($keyList['TOKEN_NAME'] == 'T_DOUBLE_ARROW') {
                    $doubleArrowFlag = true;
                    $previousElementIndex = count($tokensByLine[$lineNumber]) - 2;
                    if ($tokensByLine[$lineNumber][$previousElementIndex]['TOKEN_NAME'] == 'T_CONSTANT_ENCAPSED_STRING') {
                        $tokensByLine[$lineNumber][$previousElementIndex]['TOKEN_NAME'] = 'T_ARRAY_KEY';
                        $oldKey = $tokensByLine[$lineNumber][$previousElementIndex][1];
                        $newKey = $this->fixIndexNames($currentNameString, $oldKey, $fileName);
                        $tokensByLine[$lineNumber][$previousElementIndex][1] = $newKey;
                    }
                } else {
                    if ($keyList['TOKEN_NAME'] != 'T_WHITESPACE' &&
                        $keyList['TOKEN_NAME'] != 'T_ARRAY'
                    ) {
                        $doubleArrowFlag = false;
                    }
                }

                if ($keyList['TOKEN_NAME'] == 'T_ARRAY' && $doubleArrowFlag == true) {
                    $tokensByLine[$currentNameLineNumber][$currentNameIndex]['TOKEN_NAME'] = "T_ARRAY_NAME";
                    $doubleArrowFlag = false;
                }

                //Find ARRAY_KEYS in single line arrays
                if (count($tokensByLine[$lineNumber]) == 5 &&
                    $tokensByLine[$lineNumber][0]['TOKEN_NAME'] == 'T_VARIABLE' &&
                    $tokensByLine[$lineNumber][1] == '['
                ) {
                    $keyList['TOKEN_NAME'] = 'T_ARRAY_KEY';
                    $oldKey = $keyList[1];
                    $newKey = $this->fixIndexNames($tokensByLine[$lineNumber][2][1], $oldKey, $fileName);
                    $keyList[1] = $newKey;
                }

                //Take care of files with huge areas of whitespace
                if(trim($keyList[1])=="") {
                    if(stristr($keyList[1],"\r")!==false) {
                        if($this->compressWhitespace) {
                            $keyList[1] = "";
                        } else {
                            $keyList[1] = "\r";
                        }
                    }elseif(stristr($keyList[1],"\n")!==false) {
                        if($this->compressWhitespace) {
                            $keyList[1] = "";
                        } else {
                            $keyList[1] = "\n";
                        }
                    }
                }

                //fix globals issue
                if ($keyList[1] == "\$GLOBALS") {
                    $globalsFlag = true;
                    $this->changed = true;
                } elseif ($globalsFlag == true) {
                    if ($keyList['TOKEN_NAME'] == 'T_CONSTANT_ENCAPSED_STRING') {
                        $keyList[1] = "\$" . $keyList[1];
                        $keyList['TOKEN_NAME'] = 'T_VARIABLE';
                        $tokensByLine[$lineNumber][] = $keyList;
                    }
                } else {
                    $tokensByLine[$lineNumber][] = $keyList;
                }

            } else {
                if ($globalsFlag == false) {
                    $tokensByLine[$lineNumber][] = $keyList;
                } else {
                    if ($keyList == ']') {
                        $globalsFlag = false;
                    }
                }
            }
        }
        return $tokensByLine;
    }

    private function fixIndexNames($arrayName, $oldKey, $fileName)
    {
        //Now go through and remove the characters [& / - ( )] and spaces (in some cases) from array keys
        $badChars = array(' & ', '&', ' - ', '-', '/', ' / ', '(', ')');
        $goodChars = array('_', '_', '_', '_', '_', '_', '', '');
        $newKey = str_replace($badChars, $goodChars, $oldKey, $count);
        if ($newKey != $oldKey) {
            //replace the bad sub-key
            $this->keyCount = $this->keyCount + $count;
            $this->changed = true;
            $listField = $this->findListField($arrayName);
            $this->updateDatabase($listField, $oldKey, $newKey);
            $this->updateFieldsMetaDataTable($listField, $newKey, $oldKey);
            $this->updatefiles($newKey, $oldKey);
            $this->updateReportFilters($oldKey, $newKey);
            $this->modifiedFiles[$fileName] = $this->truncateFileName($fileName);
            $this->indexChanges[$oldKey] = $newKey;
        }
        return $newKey;
    }

    /**
     * @param string $listName
     * @param string $module
     * @return array
     */
    private function findListField($listName, $module = "")
    {
        global $beanList;
        $moduleList = array();
        $retArray = array();

        //if the array as already been processed then just return the value
        if (isset($this->arrayCache[$listName])) {
            return $this->arrayCache[$listName];
        }

        if (!empty($module) && array_key_exists($module, $beanList)) {
            $moduleList[$module] = $beanList[$module];
        } else {
            $moduleList = $beanList;
        }
        foreach ($moduleList as $bean => $object) {
            $focus = BeanFactory::getBean($bean);
            if (isset($focus->field_defs) && !empty($focus->field_defs)) {
                foreach ($focus->field_defs as $fieldName => $definitions) {
                    if (array_key_exists('options', $definitions) && $definitions['options'] == $listName) {
                        $retArray[$bean] = $fieldName;
                    }
                }
            }
        }
        if (empty($retArray)) {
            $this->logThis("Could not locate '{$listName}' in bean '{$bean}', it appears not to be used as a dropdown list", self::SEV_LOW);

        }
        $this->arrayCache[$listName] = $retArray;
        return $retArray;
    }

    /**
     * @param string $fieldData
     * @param string $oldValue
     * @param string $newValue
     */
    private function updateDatabase($fieldData, $oldValue, $newValue)
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
                $query = str_replace(array("\r", "\n"), "", "UPDATE {$table}
                            SET {$fieldName} = REPLACE({$fieldName}, '{$oldValue}', '{$newValue}')
                            WHERE {$fieldName} LIKE '%^{$oldValue}^%' OR
                                  {$fieldName} = '{$oldValue}'");
                $query = preg_replace('/\s+/', ' ', $query);
                //dont bother running the same query twice
                if (!in_array($query, $this->queryCache)) {
                    $this->logThis("-> Running Query: {$query}");
                    $GLOBALS['db']->query($query, true, "Error updating {$table}.");
                    $this->queryCache[] = $query;
                }
            }
        }
    }

    /**
     * @param string $listName
     * @param string $newKey
     * @param string $oldKey
     */
    private function updateFieldsMetaDataTable($listName, $newKey, $oldKey)
    {
        foreach ($listName as $moduleName => $fieldName) {
            $query = str_replace(array("\r", "\n"), "", "UPDATE fields_meta_data
                        SET default_value = REPLACE(default_value, '{$oldKey}', '{$newKey}')
                        WHERE custom_module='{$moduleName}'
                          AND (default_value LIKE '%^{$oldKey}^%' OR default_value = '{$oldKey}')
                          AND ext1='{$fieldName}'");
            $query = preg_replace('/\s+/', ' ', $query);
            //dont bother running the same query twice
            if (!in_array($query, $this->queryCache)) {
                $this->logThis("-> Running Query: {$query}");
                $GLOBALS['db']->query($query, true, "Error updating fields_meta_data.");
                $this->queryCache[] = $query;
            }
        }
    }

    /**
     * Shows a list of files that might need manual updating
     *
     * @param string $searchString
     * @param string $oldKey
     * @return bool
     */
    private function updateFiles($newKey, $oldKey)
    {
        $matches = array();
        if (empty($newKey) || in_array($oldKey, $this->customListNames)) {
            return false;
        }

        $searchString1 = "'" . $oldKey . "'";
        $searchString2 = '"' . $oldKey . '"';

        foreach ($this->customOtherFileList as $fileName) {
            $text = sugar_file_get_contents($fileName);
            if (strpos($text, $searchString1) !== FALSE ||
                strpos($text, $searchString2) !== FALSE
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
                $text = str_replace($oldText, $newText, $text);
                if (strpos($text, $searchString1) !== FALSE) {
                    $matches[$fileName] = true;
                    $this->customListNames[] = $oldKey;
                } else {
                    $this->modifiedFiles[$fileName] = $this->truncateFileName($fileName);
                    $this->backupFile($fileName);
                    sugar_file_put_contents($fileName, $text, LOCK_EX);
                }
            }
        }

        if (!empty($matches)) {
            $this->logThis("------------------------------------------------------------", self::SEV_MEDIUM);
            $this->logThis("These files MAY need to be updated to reflect the new key (New '{$newKey}' vs. old '{$oldKey}')", self::SEV_MEDIUM);
            $this->logThis("-------------------------------------------------------------", self::SEV_MEDIUM);
            foreach ($matches as $fileName => $flag) {
                $this->manualFixFiles[$fileName] = $this->truncateFileName($fileName);
                $this->logThis($this->truncateFileName($fileName), self::SEV_MEDIUM);
            }
            $this->logThis("-------------------------------------------------------------", self::SEV_MEDIUM);
        }
    }

    /**
     * @param string $srcFile
     * @return bool
     */
    private function backupFile($srcFile)
    {
        //Just return if no backup files are needed
        if ($this->makeBackups == false) {
            return true;
        }

        $dstFile = str_replace('custom', 'custom_flf', $srcFile);
        if (!file_exists(dirname($dstFile))) {
            if (!mkdir(dirname($dstFile), 0777, true)) {
                $this->logThis("Could not create " . dirname($dstFile) . ", so backup file could not be created");
                return false;
            }
        }
        if (file_exists($dstFile)) {
            unlink($dstFile);
        }
        if (!copy($srcFile, $dstFile)) {
            $this->logThis("Could not copy to {$dstFile}, so backup file could not be created");
            return false;
        }
        return true;
    }

    /**
     * @param $oldKey
     * @param $newKey
     */
    private function updateReportFilters($oldKey, $newKey)
    {
        $jsonObj = getJSONobj();
        foreach ($this->reportKeys as $reportID => $filterKeys) {
            if (in_array($oldKey, $filterKeys)) {
                $contents = $this->parseReportFilters($reportID, $oldKey, $newKey);
                $encodedContent = $jsonObj->encode(htmlentities($contents));
                $savedReport = BeanFactory::getBean('Reports', $reportID);
                $savedReport->content = $encodedContent;
                $savedReport->save();
                $this->logThis("Report {$reportID} Updated with new key '{$newKey}'", self::SEV_MEDIUM);
            }
        }
    }

    /**
     * @param array $tokenList
     * @param string $fileName
     */
    private function writeNewFile($tokenList, $fileName)
    {
        $assembledFile = array();
        foreach ($tokenList as $lineNumber => $contents) {
            $assembledFile[$lineNumber] = "";
            foreach ($contents as $index => $element) {
                if (is_array($element)) {
                    //Remove the OUTSIDE slashes from T_CONSTANT_ENCAPSED_STRING
                    if ($element['TOKEN_NAME'] == 'T_CONSTANT_ENCAPSED_STRING' || $element['TOKEN_NAME'] == 'T_ARRAY_KEY') {
                        if (substr($element[1], 0, 2) == "\'") {
                            $element[1] = substr($element[1], 2);
                        }
                        if (substr($element[1], -2) == "\'") {
                            $element[1] = substr($element[1], 0, -2);
                        }
                    }

                    $assembledFile[$lineNumber] .= $element[1];
                } else {
                    $element = trim($element);
                    $assembledFile[$lineNumber] .= $element;
                }
            }
            if (stristr($assembledFile[$lineNumber], "\n") === false) {
                $assembledFile[$lineNumber] .= "\n";
            }
        }
        sugar_file_put_contents($fileName, $assembledFile, LOCK_EX);
    }

    /**
     * @param INT $current
     * @param INT $total
     */
    private function updateProgress($current, $total)
    {
        $percentage = ceil($current / $total * 100);
        $fh = fopen('custom/fixLanguageFiles_Progress.php', 'w');
        fwrite($fh, "<?php\n");
        fwrite($fh, "echo '{$percentage}';");
        fclose($fh);
    }
}
