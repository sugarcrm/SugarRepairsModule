<?php

require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_Repairs.php');

class supp_VardefRepairs extends supp_Repairs
{
    protected $loggerTitle = "Vardef";
    protected $foundIssues = array();

    function __construct()
    {
        parent::__construct();
    }

    /**
     * Repairs any broken default values
     * @param $vardef
     */
    public function repairDefs(&$dictionary)
    {
        foreach ($dictionary as $module => $modDefs) {

            foreach ($modDefs['fields'] as $field => $fieldDefs) {

                $defKey = "{$module} :: {$field}";
                $this->log("-> Looking at {$defKey}...");

                $type = $this->getFieldType($module, $field);

                //check for invalid default values
                if ($type && isset($fieldDefs['default'])) {

                    if ($type == 'multienum') {
                        if (!$this->isTesting) {
                            //multienum is stored in the db and this is unused
                            $this->log("-> Vardef '{$defKey}' is a multienum and should not have a default value in the vardef file. Index was removed.");
                            unset($dictionary[$module]['fields'][$field]['default']);
                        } else {
                            $this->log("-> Vardef '{$defKey}' is a multienum and should not have a default value in the vardef file. The default index will be removed.");
                        }
                    } else if ($type == 'enum') {
                        $listKeys = $this->getFieldOptionKeys($module, $field);
                        $selectedKeys = unencodeMultienum($fieldDefs['default']);

                        $modifiedSelectedKeys = $selectedKeys;
                        foreach ($selectedKeys as $id => $selectedKey) {
                            $issue = false;
                            if (!in_array($selectedKey, $listKeys)) {
                                $this->foundIssues[$defKey] = $defKey;
                                $issue = true;
                            }

                            if ($issue) {
                                $testKey = $this->getValidLanguageKeyName($selectedKey);
                                //try to fix the key if it was updated in the lang repair script
                                if ($testKey !== $selectedKey) {
                                    if (in_array($testKey, $listKeys)) {
                                        $issue = false;
                                        $modifiedSelectedKeys[$id] = $testKey;
                                    }
                                }
                            }

                            if ($issue) {
                                $this->log("Vardef '{$defKey}' has an invalid key '{$selectedKey}'. Allowed keys for {$module} / {$field} are: " . print_r($listKeys, true));
                                $this->foundIssues[$defKey] = $defKey;
                                //shouldnt disable - do something to alert? ¯\_(ツ)_/¯
                            }
                        }

                        if ($modifiedSelectedKeys !== $selectedKeys) {
                            if (!$this->isTesting) {
                                $this->log("Vardef '{$defKey}' has an invalid key '{$selectedKey}' that was updated to '{$testKey}'. Allowed keys for {$module} / {$field} are: " . print_r($listKeys, true));
                                if ($type == 'enum') {
                                    $dictionary[$module]['fields'][$field]['default'] = $modifiedSelectedKeys[$id];
                                } else if ($type == 'multienum') {
                                    $dictionary[$module]['fields'][$field]['default'] = encodeMultienumValue($modifiedSelectedKeys);
                                }

                            } else {
                                $this->log("Vardef '{$defKey}' has an invalid key '{$selectedKey}' that will be updated to '{$testKey}'. Allowed keys for {$module} / {$field} are: " . print_r($listKeys, true));
                            }
                        }
                    }
                }
            }

        }

        return $dictionary;
    }


    /**
     * Executes the vardef repairs
     * @param array $args
     * @return bool
     */
    public function execute(array $args)
    {
        if ($this->isCE()) {
            $this->log('Repair ignored as it does not apply to CE');
            return false;
        }

        //check for testing an other repair generic params
        parent::execute($args);

        $stamp = time();

        if (
            $this->backupTable('fields_meta_data')
        ) {
            $vardefs = $this->getCustomVardefFiles();

            foreach ($vardefs as $fullPath => $relativePath) {
                $this->log("Processing '{$fullPath}'...");
                $dictionary = array();
                require($fullPath);
                $this->repairDefs($dictionary);

                //will do rewrite here
            }

        }

        if (!$this->isTesting) {
            $this->runQRAR();
        }
    }

}
