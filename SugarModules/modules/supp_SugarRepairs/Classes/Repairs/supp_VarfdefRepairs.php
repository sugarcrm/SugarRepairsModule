<?php

require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_Repairs.php');

class supp_VardefRepairs extends supp_Repairs
{
    protected $loggerTitle = "Vardefs";
    protected $foundVardefIssues = array();
    protected $foundMetadataIssues = array();

    function __construct()
    {
        parent::__construct();
    }

    /**
     * Cycles through metadata for updates
     * @return mixed
     */
    public function repairFieldsMetadata()
    {
        $sql = "SELECT * FROM fields_meta_data WHERE fields_meta_data.deleted = 0 AND (fields_meta_data.type = 'multienum' OR fields_meta_data.type = 'enum')";
        $result = $GLOBALS['db']->query($sql);

        while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
            $defKey = "{$row['custom_module']} / {$row['name']}";
            $type = $row['type'];
            $module = $row['custom_module'];
            $field = $row['name'];
            $this->log("Processing fields metadata for '{$defKey}'...'");
            $listKeys = $this->getFieldOptionKeys($module, $field);


            if ($listKeys == false) {
                $this->logAction("-> Metadata '{$defKey}' is using an invalid list'. This can be corrected by resaving the field in studio.");
                $this->foundMetadataIssues[$defKey] = $defKey;
                continue;
            }

            $selectedKeys = unencodeMultienum($row['default_value']);

            $modifiedSelectedKeys = $selectedKeys;
            foreach ($selectedKeys as $id => $selectedKey) {
                $issue = false;
                if (!in_array($selectedKey, $listKeys)) {
                    $this->foundMetadataIssues[$defKey] = $defKey;
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

                if ($issue && $type == 'enum' && count($selectedKeys) == 1 && isset($selectedKeys[0]) && empty($selectedKeys[0])) {
                    if (isset($listKeys[0])) {
                        $issue = false;
                        //set default value to first item in list
                        $modifiedSelectedKeys[0] = $listKeys[0];
                    }
                }

                if ($issue && $type == 'multienum' && count($selectedKeys) == 1 && isset($selectedKeys[0]) && empty($selectedKeys[0])) {
                    //multienums can be empty
                    $issue = false;
                }

                if ($issue) {
                    $this->logAction("-> Metadata '{$defKey}' has an invalid default value '{$selectedKey}'. This can be corrected by resaving the field in studio. Allowed keys for {$module} / {$field} are: " . print_r($listKeys, true));
                    $this->foundMetadataIssues[$defKey] = $defKey;
                    //dont disable - just alert
                }
            }


            if ($modifiedSelectedKeys !== $selectedKeys) {

                if ($type == 'enum') {
                    if (isset($modifiedSelectedKeys[0])) {
                        $default_value = $modifiedSelectedKeys[0];
                    } else {
                        $default_value = '';
                    }
                } else if ($type == 'multienum') {
                    $default_value = encodeMultienumValue($modifiedSelectedKeys);
                }

                if (!$this->isTesting) {
                    $this->logChange("-> Metadata '{$defKey}' has an invalid default value '{$row['default_value']}' that was updated to '{$default_value}'. Allowed keys for {$module} / {$field} are: " . print_r($listKeys, true));
                    $this->updateQuery("UPDATE fields_meta_data SET default_value = '{$default_value}' WHERE deleted = 0 AND custom_module = '{$module}' AND name = '{$field}'");
                } else {
                    $this->logChange("-> Metadata '{$defKey}' has an invalid default value '{$row['default_value']}' that will be updated to '{$default_value}'. Allowed keys for {$module} / {$field} are: " . print_r($listKeys, true));
                }
            }
        }

        $foundIssuesCount = count($this->foundMetadataIssues);
        $this->log("Found {$foundIssuesCount} bad metadata records.");
    }

    /**
     * Repairs any broken default values in vardefs
     */
    public function repairDefs()
    {
        $vardefs = $this->getCustomVardefFiles();

        foreach ($vardefs as $fullPath => $relativePath) {
            $this->log("Processing '{$fullPath}'...");

            $variables = $this->getVariablesInFile($fullPath);

            if (count($variables) == 1 && isset($variables['$dictionary'])) {
                //proceed
            } else if (count($variables) > 1 && isset($variables['$dictionary'])) {
                $this->logAction("-> File contains multiple variables. This will need to be manually corrected. Variables present are: " . print_r($variables));
                continue;
            } else {
                $append = '';
                if (!empty($variables)) {
                    $append = " This will need to be manually corrected. Variables present are: " . print_r($variables);
                }
                $this->log("-> No \$dictionary variables are present.{$append}");
                continue;
            }

            $dictionary = array();
            require($fullPath);

            $multipleDictionaryIndexes = false;

            $storedDictionary = $dictionary;
            foreach ($dictionary as $objectName => $modDefs) {

                if (!isset($modDefs['fields'])) {
                    continue;
                }

                //check if we have other indexes specified
                $modDefTest = $modDefs;
                unset($modDefTest['fields']);

                if (!empty($modDefTest)) {
                    $multipleDictionaryIndexes = true;
                }

                $module = $this->getModuleName($objectName);
                foreach ($modDefs['fields'] as $field => $fieldDefs) {

                    $defKey = "{$module} / {$field}";
                    $this->log("-> Looking at '{$defKey}'...");

                    $type = $this->getFieldType($module, $field);

                    //check for invalid default values
                    if ($type && isset($fieldDefs['default'])) {
                        if (in_array($type, array('enum', 'multienum'))) {
                            $listKeys = $this->getFieldOptionKeys($module, $field);

                            if ($listKeys == false) {
                                $this->logAction("-> Metadata '{$defKey}' has an invalid dropdown list. This can be corrected by resaving the field in studio. Allowed keys for {$module} / {$field} are: " . print_r($listKeys, true));
                                $this->foundMetadataIssues[$defKey] = $defKey;
                                continue;
                            }

                            $selectedKeys = unencodeMultienum($fieldDefs['default']);

                            $modifiedSelectedKeys = $selectedKeys;
                            foreach ($selectedKeys as $id => $selectedKey) {
                                $issue = false;
                                if (!in_array($selectedKey, $listKeys)) {
                                    $this->foundVardefIssues[$defKey] = $defKey;
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

                                if ($issue && $type == 'enum' && count($selectedKeys) == 1 && isset($selectedKeys[0]) && empty($selectedKeys[0])) {
                                    if (isset($listKeys[0])) {
                                        $issue = false;
                                        //set default value to first item in list
                                        $modifiedSelectedKeys[0] = $listKeys[0];
                                    }
                                }

                                if ($issue) {
                                    $this->logAction("-> Vardef '{$defKey}' has an invalid key '{$selectedKey}' in '{$fullPath}'. This will need to be manually corrected. Allowed keys for {$module} / {$field} are: " . print_r($listKeys, true));
                                    $this->foundVardefIssues[$defKey] = $defKey;
                                    //dont disable - just alert
                                }
                            }

                            if ($modifiedSelectedKeys !== $selectedKeys) {
                                if ($type == 'enum') {
                                    if (isset($modifiedSelectedKeys[0])) {
                                        $default_value = $modifiedSelectedKeys[0];
                                    } else {
                                        $default_value = '';
                                    }
                                } else if ($type == 'multienum') {
                                    $default_value = encodeMultienumValue($modifiedSelectedKeys);
                                }

                                if ($multipleDictionaryIndexes) {
                                    $this->foundVardefIssues[$defKey] = $defKey;
                                    $this->logAction("-> Vardef '{$fullPath}' has indexes aside from fields. To avoid any issues, this file should be corrected manually. '{$defKey}' should have a default value of '{$default_value}'. " . print_r($dictionary, true));
                                    break;
                                } else {
                                    if (!$this->isTesting) {
                                        $dictionary[$objectName]['fields'][$field]['default'] = $default_value;
                                        $this->logChange("-> Vardef '{$defKey}' has an invalid default value '{$fieldDefs['default']}' that was updated to '{$default_value}'. Allowed keys for {$module} / {$field} are: " . print_r($listKeys, true));
                                    } else {
                                        $this->logChange("-> Vardef '{$defKey}' has an invalid default value '{$fieldDefs['default']}' that will be updated to '{$default_value}'. Allowed keys for {$module} / {$field} are: " . print_r($listKeys, true));
                                    }
                                }
                            }
                        }
                    }

                    if (in_array($type, array('enum', 'multienum')) && isset($fieldDefs['visibility_grid']) && isset($fieldDefs['visibility_grid']['values']) && isset($fieldDefs['visibility_grid']['trigger']) && !empty($fieldDefs['visibility_grid']['trigger'])) {

                        $triggerField = $fieldDefs['visibility_grid']['trigger'];
                        $triggerType = $this->getFieldType($module, $triggerField);
                        if (in_array($triggerType, array('enum', 'multienum'))) {
                            $triggerListKeys = $this->getFieldOptionKeys($module, $triggerField);
                            $gridListKeys = $this->getFieldOptionKeys($module, $field);


                            //took out the tests here because they broke the code

                            foreach ($fieldDefs['visibility_grid']['values'] as $key => $values) {

                                foreach ($values as $gridIndex => $gridkey) {
                                    //$this->log("Checking visibility_grid '{$gridIndex} / {$gridkey}'...");
                                    $gridIssue = false;
                                    if (!in_array($gridkey, $gridListKeys)) {
                                        $gridIssue = true;
                                        $this->foundVardefIssues[$defKey] = $defKey;
                                    }

                                    if ($gridIssue) {
                                        $testGridKey = $this->getValidLanguageKeyName($gridkey);
                                        //try to fix the key if it was updated in the lang repair script
                                        if ($testGridKey !== $gridkey) {
                                            if (in_array($testGridKey, $gridListKeys)) {
                                                $gridIssue = false;
                                                $this->logChange("-> Vardef '{$defKey}' has an issue with the visibility_grid. The mapping '{$key} / {$gridkey}' uses the grid key '{$gridkey}' which will be updated to '{$testGridKey}'. Available keys in list: " . print_r($gridListKeys, true));

                                                if (!$this->isTesting) {
                                                    $dictionary[$objectName]['fields'][$field]['visibility_grid']['values'][$key][$gridIndex] = $testGridKey;
                                                }
                                            }
                                        }
                                    }

                                    if ($gridIssue) {
                                        $this->logChange("-> Vardef '{$defKey}' has an issue with the visibility_grid. The mapping '{$key} / {$gridkey}' uses the grid key '{$gridkey}' which will be removed. Key does not exist in list: " . print_r($gridListKeys, true));
                                        if (!$this->isTesting) {
                                            $this->foundVardefIssues[$defKey] = $defKey;
                                            if (isset($dictionary[$objectName]['fields'][$field]['visibility_grid']['values'][$key][$gridIndex])) {
                                                unset($dictionary[$objectName]['fields'][$field]['visibility_grid']['values'][$key][$gridIndex]);
                                            }
                                        }
                                    }
                                }

                                $triggerIssue = false;
                                if (!in_array($key, $triggerListKeys)) {
                                    $triggerIssue = true;
                                    $this->foundVardefIssues[$defKey] = $defKey;
                                }

                                if ($triggerIssue) {
                                    $testKey = $this->getValidLanguageKeyName($key);
                                    //try to fix the key if it was updated in the lang repair script
                                    if ($testKey !== $key) {
                                        if (in_array($testKey, $triggerListKeys)) {
                                            $triggerIssue = false;
                                            $this->logChange("-> Vardef '{$defKey}' has an issue with the visibility_grid. The field '{$triggerField}' uses the key '{$key}' which will be updated with '{$testKey}'. Available keys in list: " . print_r($triggerListKeys, true));

                                            if (!$this->isTesting) {
                                                $dictionary[$objectName]['fields'][$field]['visibility_grid']['values'][$testKey] = $dictionary[$objectName]['fields'][$field]['visibility_grid']['values'][$key];
                                                unset($dictionary[$objectName]['fields'][$field]['visibility_grid']['values'][$key]);
                                            }
                                        }
                                    }
                                }

                                if ($triggerIssue) {
                                    $this->logChange("-> Vardef '{$defKey}' has an issue with the visibility_grid. The field '{$triggerField}' uses the key '{$key}' which will be removed. Key does not exist in list: " . print_r($triggerListKeys, true));
                                    if (!$this->isTesting) {
                                        $this->foundVardefIssues[$defKey] = $defKey;
                                        if ($dictionary[$objectName]['fields'][$field]['visibility_grid']['values'][$key]) {
                                            unset($dictionary[$objectName]['fields'][$field]['visibility_grid']['values'][$key]);
                                        }
                                    }
                                }
                            }


                        } else {
                            $this->logAction("-> Vardef '{$defKey}' has an issue with the visibility_grid in '{$fullPath}'. This will need to manually corrected. The trigger field '{$triggerField}' does not have a valid type ({$triggerType}).");
                        }

                    }
                }
            }

            if (!$this->isTesting && $storedDictionary !== $dictionary) {
                $this->writeDictionaryFile($dictionary, $fullPath);
            }
        }

        $foundIssuesCount = count($this->foundVardefIssues);
        $this->log("Found {$foundIssuesCount} bad vardef files.");
    }

    /**
     * Executes the vardef repairs
     * @param array $args
     * @return bool
     */
    public function execute(array $args)
    {
        //kbrill: I think this does apply to CE
//        if ($this->isCE()) {
//            $this->log('Repair ignored as it does not apply to CE');
//            return false;
//        }

        //check for testing an other repair generic params
        parent::execute($args);

        $stamp = time();

        if (
        $this->backupTable('fields_meta_data', $stamp)
        ) {
            $this->repairDefs();
            $this->repairFieldsMetadata();
        }

        if (!$this->isTesting) {
            $this->runQRAR();
        }
    }

}
