<?php

require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_Repairs.php');

class supp_VardefRepairs extends supp_Repairs
{
    protected $loggerTitle = "Vardef";
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
            $this->log("Processing fields metadata for $defKey");
            $listKeys = $this->getFieldOptionKeys($module, $field);
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
                    $this->log("-> Metadata '{$defKey}' has an invalid default value '{$selectedKey}'. Allowed keys for {$module} / {$field} are: " . print_r($listKeys, true));
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
                    $this->log("-> Metadata '{$defKey}' has an invalid default value '{$row['default_value']}' that was updated to '{$default_value}'. Allowed keys for {$module} / {$field} are: " . print_r($listKeys, true));
                    $this->updateQuery("UPDATE fields_meta_data SET default_value = '{$default_value}' WHERE deleted = 0 AND custom_module = '{$module}' AND name = '{$field}'");
                } else {
                    $this->log("-> Metadata '{$defKey}' has an invalid default value '{$row['default_value']}' that will be updated to '{$default_value}'. Allowed keys for {$module} / {$field} are: " . print_r($listKeys, true));
                }
            }
        }

        $foundIssuesCount = count($this->foundMetadataIssues);
        $this->log("Found {$foundIssuesCount} bad metadata records.");
    }

    /**
     * Repairs any broken default values in vardefd
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
                $this->log("-> File contains multiple variables. This will need to be manually corrected. Variables present are: " . print_r($variables));
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
            foreach ($dictionary as $objectName => $modDefs) {

                $module = $this->getModuleName($objectName);
                foreach ($modDefs['fields'] as $field => $fieldDefs) {

                    $defKey = "{$module} / {$field}";
                    $this->log("-> Looking at {$defKey}...");

                    $type = $this->getFieldType($module, $field);

                    //check for invalid default values
                    if ($type && isset($fieldDefs['default'])) {
//                        if ($type == 'multienum') {
//                            if (!$this->isTesting) {
//                                //multienum is stored in the db and this is unused
//                                $this->log("-> Vardef '{$defKey}' is a multienum and should not have a default value in the vardef file. Index was removed.");
//                                unset($dictionary[$module]['fields'][$field]['default']);
//                            } else {
//                                $this->log("-> Vardef '{$defKey}' is a multienum and should not have a default value in the vardef file. The default index will be removed.");
//                            }
//                        } else
                        if (in_array($type, array('enum', 'multienum'))) {
                            $listKeys = $this->getFieldOptionKeys($module, $field);
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
                                    $this->log("-> Vardef '{$defKey}' has an invalid key '{$selectedKey}'. Allowed keys for {$module} / {$field} are: " . print_r($listKeys, true));
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

                                if (!$this->isTesting) {
                                    $dictionary[$objectName]['fields'][$field]['default'] = $default_value;
                                    $this->log("-> Vardef '{$defKey}' has an invalid default value '{$fieldDefs['default']}' that was updated to '{$default_value}'. Allowed keys for {$module} / {$field} are: " . print_r($listKeys, true));

                                    $this->writeDictionaryFile($objectName, $field, $dictionary[$objectName]['fields'][$field], $fullPath);

                                } else {
                                    $this->log("-> Vardef '{$defKey}' has an invalid default value '{$fieldDefs['default']}' that will be updated to '{$default_value}'. Allowed keys for {$module} / {$field} are: " . print_r($listKeys, true));
                                }
                            }
                        }
                    }
                }
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
        if ($this->isCE()) {
            $this->log('Repair ignored as it does not apply to CE');
            return false;
        }

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
