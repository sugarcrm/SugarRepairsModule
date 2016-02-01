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

    public $customLanguageFileList = array();
    public $customOtherFileList = array();
    public $customListNames = array();
    public $totalFiles = 0;
    public $makeBackups = false;
    public $deleteEmpty = true; //setting to true for no
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

    function __construct()
    {
        parent::__construct();
    }

    /**
     * Executes the repairs
     * @param bool $isTesting
     */
    public function execute($isTesting = false)
    {
        $customLanguageFiles = $this->getCustomLanguageFiles($isTesting);

        $GLOBALS['log']->debug("fixLanguageFiles: BEGIN processLanguageFiles");
        $currentFileCount = 0;
        foreach ($customLanguageFiles as $fullPath => $relativePath) {
            $currentFileCount++;
            $result = $this->testLanguageFile($fullPath);
            switch ($result) {
                case self::TYPE_SYNTAXERROR:
                    $this->capture('File', $fullPath, "Syntax Error in file: {$relativePath} ({$this->syntaxError})", 'Review', self::SEV_HIGH);
                    break;
                case self::TYPE_UNREADABLE:
                    $this->capture('File', $fullPath, "Unreadable file: {$relativePath}", 'Review', self::SEV_HIGH);
                    break;
                case self::TYPE_UNWRITABLE:
                    $this->capture('File', $fullPath, "Unwritable file: {$relativePath}", 'Review', self::SEV_HIGH);
                    break;
                case self::TYPE_EMPTY:
                    $this->capture('File', $fullPath, "Deleted file: {$relativePath}", 'Updated', self::SEV_HIGH, file_get_contents($fullPath));
                    break;
                case self::TYPE_DYNAMIC:
                    $this->log('File', $fullPath, "Problem file: {$relativePath}", 'Review', self::SEV_HIGH);
                    break;
                case self::TYPE_STATIC:
                    //$this->repairStaticFile($fullPath);
                    break;
            }
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
