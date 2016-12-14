<?php


require_once('modules/supp_SugarRepairs/Classes/Repairs/supp_Repairs.php');

class supp_MetadataRepairs extends supp_Repairs {

    protected $loggerTitle = "Metadata";
    protected $foundIssues = array();

    function __construct()
    {
        parent::__construct();
    }

    /**
     * Executes the TeamSet repairs
     * @param array $args
     */
    public function execute(array $args)
    {
        global $sugar_version;
        if (version_compare($sugar_version,'7.0','<')){
            $this->logAll('Metadata repairs only available for Sugar 7 instances.');
            return;
        }

        //check for testing an other repair generic params
        parent::execute($args);

        $this->logAll('Begin Metadata repairs');

        if (version_compare($sugar_version,'7.7','>=')){
            $this->repairMissingAuditButtons();
        }


    }

    public function repairMissingAuditButtons(){
        $customModules = $this->getCustomModules();

        if (empty($customModules)){
            return;
        }

        foreach ($customModules as $moduleName => $modulePath) {
            $bean = BeanFactory::getBean($moduleName);
            if ($bean->is_AuditEnabled()) {
                $recordViewPaths = SugarAutoLoader::existingCustom("modules/$moduleName/clients/base/views/record/record.php");
                if (!empty($recordViewPaths)) {
                    $viewdefs = array();
                    foreach ($recordViewPaths as $recordViewDef) {
                        include $recordViewDef;
                        if (!empty($viewdefs[$moduleName]['base']['view']['record'])) {
                            $defsToWrite = $viewdefs[$moduleName]['base']['view']['record'];
                            $auditBtnUpdate = 'full';
                            if (!empty($defsToWrite['buttons'])){
                                if ($this->hasAuditBtnDef($defsToWrite['buttons']) !== FALSE){
                                    $auditBtnUpdate = FALSE;
                                }else {
                                    $auditBtnUpdate = 'merge';
                                }
                            }
                            if ($auditBtnUpdate !== FALSE){
                                if ($this->isTesting){
                                    $this->logChange("Will update $recordViewDef to include Change Log Button.");
                                    continue;
                                }else{
                                    $this->logChange("Updating $recordViewDef to include Change Log Button.");
                                }
                                $templateFile = $this->getTemplateFile($moduleName);
                                if ($templateFile) {
                                    $viewdefs = array();
                                    include $templateFile;
                                    if (!empty($viewdefs['<module_name>']['base']['view']['record']['buttons'])) {
                                        $templateButtonsDef = $viewdefs['<module_name>']['base']['view']['record']['buttons'];

                                        if ($auditBtnUpdate == 'merge'){
                                            $auditBtnDef = $this->hasAuditBtnDef($templateButtonsDef);
                                            foreach($defsToWrite['buttons'] as $key => $button){
                                                if ($button['type'] == 'actiondropdown' && $button['name'] == 'main_dropdown'){
                                                    $defsToWrite['buttons'][$key][] = array(
                                                        'type' => 'divider',
                                                    );
                                                    $defsToWrite['buttons'][$key][] = $auditBtnDef;
                                                }
                                            }
                                        }else{
                                            $defsToWrite['buttons'] = $templateButtonsDef;
                                        }
                                        $contents = "<?php\n\n\$module_name = '$moduleName';\n\$viewdefs[\$module_name]['base']['view']['record'] = ".var_export($defsToWrite,true).";";
                                        $this->writeFile($recordViewDef,$contents);
                                    }
                                }
                            }
                        }
                        unset($viewdefs);
                    }
                }
                unset($bean);
            }
        }
    }

    protected function hasAuditBtnDef(array $definition){
        foreach($definition as $buttonDef){
            if ($buttonDef['name'] == 'audit_button'){
                return $buttonDef;
            }
            if ($buttonDef['type'] == 'actiondropdown'){
                if (isset($buttonDef['buttons']) && is_array($buttonDef['buttons'])){
                    $definition = $this->hasAuditBtnDef($buttonDef['buttons']);
                    if ($definition !== FALSE){
                        return $definition;
                    }
                }
            }
        }
        return FALSE;
    }

    protected function getTemplateFile($moduleName)
    {
        $template = StudioModuleFactory::getStudioModule($moduleName)->getType();
        $templateFile = "include/SugarObjects/templates/$template/clients/base/views/record/record.php";
        if (file_exists($templateFile)) {
            return $templateFile;
        }
        return null;
    }
}