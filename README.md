# Sugar Repairs Module [![Build Status](https://travis-ci.com/sugarcrm/SugarRepairsModule.svg?token=ApQ7hyuyE1rftpStfgbN&branch=master)](https://travis-ci.com/sugarcrm/SugarRepairsModule)
This module is designed to help assist with repairing common issues in a Sugar instance. You should always tests any repairs against a cloned enviroment before run on a production instance. This tool is to be used at your own risk.

# Usage
This repo is a module loadable package that can be installed to Sugar through the module loader. Once installed repairs can only be run from the command line. By default, all repairs are run in test mode. Test mode will not make any changes to your system and only output information on changes that will be made. To turn off test mode you will need to pass `--test false` to the cli command.

When testing mode is off, the repair actions will backup any modified tables to `<table name>_srm_<timestamp>`. Any files modified or created will have their contents store in a record under the Sugar Repairs module that you can access through the UI. All log messages will be output to your terminal window as well as stored in the Sugar log file. Any items noted as `[Sugar Repairs][<cycle>][<action>][Change]` are information about file rewrites and database updates. Any items noted as `[Sugar Repairs][<cycle>][<action>][Action]` will require a manual change to correct from a developer or administrator of the system.

##Things to note
* It is highly recommended to remove the Sugar Repairs module before upgrading.
* This package can not be installed to the Sugar OnDemand envrionment as it will not pass package scanner and you will not have access to the command line. If you are experiencing an issue with your instance, please open a [support ticket](https://web.sugarcrm.com/support/cases).

## Running Repairs
Repairs will need to be run differently based on your environment and can only be executed from the command line.

##For Local & OnDemand ION
For local instances and ION, you will need to change to the supp_SugarRepairs directory and run the cli.php directly.

###Testing Command
```
cd "modules/supp_SugarRepairs/" && php cli.php --repair <action>
```

###Execute Command
```
cd "modules/supp_SugarRepairs/" && php cli.php --repair <action> --test false
```

##For OnDemand MS
For mothership, you will need to change to the instances directory and run shadow-shell:

###Testing Command
```
$options = array(
     'repair' => '<action>'
);
require_once("./modules/supp_SugarRepairs/cli.php");
```

###Execute Command
```
$options = array(
    'repair' => '<action>',
    'test' => false
);
require_once("./modules/supp_SugarRepairs/cli.php");
```




# Language Repairs
Corrects common language file issues. The various issues addressed are shown below:

##Testing Command
`cd "modules/supp_SugarRepairs/" && php cli.php --repair lang`

##Execute Command
`cd "modules/supp_SugarRepairs/" && php cli.php --repair lang --test false`

##Issues Addressed
* Health Check Errors:

  * [Health Check Error: Bad vardefs - key] (https://support.sugarcrm.com/Knowledge_Base/Administration/Install/Troubleshooting_Health_Check_Output/Health_Check_Error_Bad_Vardefs_Key/)

  * [Health Check Error: Bad vardefs - multienum] (https://support.sugarcrm.com/Knowledge_Base/Administration/Install/Troubleshooting_Health_Check_Output/Health_Check_Error_Bad_Vardefs_Multienum/)

  * [Health Check Error: Found NULL values in moduleList strings] (https://support.sugarcrm.com/Knowledge_Base/Administration/Install/Troubleshooting_Health_Check_Output/Health_Check_Error_Found_NULL_Values_in_moduleList_Strings/)

* Extra CR/LF issues
If there are extra CR/LF characters between the lines of a language file they will be eliminated.

* Replaces `$GLOBALS` in language files
Any language string that is defined as global is rewritten in place.  This assures that when duplicates are removed the correct value is left behind. The example being that `$GLOBALS['app_list_strings']['key'] = array(…);` will be converted to `$app_list_strings['key'] = array(…);`.

* Duplicate Keys
If an array key appears more than once in any given language file, only the final one will be retained.

* Redefined module list arrays
If the entire module list array is redefined in the language file, it is then written to individual indexes rather than a redefined array. This prevents the system from having undefined modules and issues when deploying new modules through Module Builder. 
```php
$app_list_strings['moduleList'] = array(
    'module1' => 'Module 1'
    'module2' => 'Module 2'
);
```
is converted to:
```php
$app_list_strings['moduleList']['module1'] = 'Module 1';
$app_list_strings['moduleList']['module2'] = 'Module 2';
```
* Flags Errors
If a flag is thrown, manual intervention will be required. Scenarios that can throw flags are listed below:

1. All language files are tested for syntax errors and are flagged if they don’t pass.  
2. Any custom logic hook that contains a modified language key will flagged for manual change.

* Custom Vardef Files are Updated
  * If there is a custom vardef that defines a default value for a field that contains a key that needs to be changed then the VarDef file is updated.
 
* Other custom PHP files
  * If a file contains a string of characters that matches a key that needs to be changed it will be flagged for possible Manual updating in the log. 
 
* Updates the database keys for enum/multienum fields when a correction is made.
* Runs the vardef repair.
* Runs the workflow repair.
* Runs the report repair.
* Runs the process author repairs



#Team Set Repairs
Corrects common issues with team sets.

##Testing Command
`cd "modules/supp_SugarRepairs/" && php cli.php --repair team`

##Execute Command
`cd "modules/supp_SugarRepairs/" && php cli.php --repair team --test false`

##Issues Addressed
* Duplicate teams in a team set
  * Removes any duplicate team relationships to a team set.
       
* Incorrect team counts on team sets
  * Correct any team sets with invalid team counts and relationships.



#Workflow Repairs
Corrects common issues with workflows.

##Testing Command
`cd "modules/supp_SugarRepairs/" && php cli.php --repair workflow`

##Execute Command
`cd "modules/supp_SugarRepairs/" && php cli.php --repair workflow --test false`

##Issues Addressed
* Workflows with invalid fields
  * Disables any workflows with missing or invalid fields.



#Process Author Repairs
Corrects common issues with Process Author Definitions.

##Testing Command
`cd "modules/supp_SugarRepairs/" && php cli.php --repair processAuthor`

##Execute Command
`cd "modules/supp_SugarRepairs/" && php cli.php --repair processAuthor --test false`

##Issues Addressed
* Event Criteria with invalid fields.
  * Disables any Process Author Definition with criteria referencing missing or invalid fields.
  * Works on Start, Wait, and Receive Message events.
* Activities and Actions with invalid fields.
  * Disables any Process Author Definition with an activity or action referencing missing or invalid fields.



#Vardef Repairs
Corrects common issues with vardefs.

##Testing Command
`cd "modules/supp_SugarRepairs/" && php cli.php --repair vardef`

##Execute Command
`cd "modules/supp_SugarRepairs/" && php cli.php --repair vardef --test false`

##Issues Addressed
* Enum/Multienum fields with invalid default values.
  * Attempts to find a valid default value key. If no value is found, field is left alone.
       
* Enum/Multienum fields with invalid visibility gird.
  * Attempts to find a valid key. If no key is found, the grid is removed.
   



#Report Repairs
Corrects common issues with reports.

##Testing Command
`cd "modules/supp_SugarRepairs/" && php cli.php --repair reports`

##Execute Command
`cd "modules/supp_SugarRepairs/" && php cli.php --repair reports --test false`

##Issues Addressed
* If a report is using a deleted field, it will be marked as broken.
* If a field has a corrected language key, it will be updated.
* If a report is using an invalid language key, it will be marked as broken,
   
   
   
#Email Address Repairs
Corrects common issues with Email Addresses.

##Testing Command
`cd "modules/supp_SugarRepairs/" && php cli.php --repair emailAddresses`

##Execute Command
`cd "modules/supp_SugarRepairs/" && php cli.php --repair emailAddresses --test false`

##Issues Addressed
* Bug [75588](https://web.sugarcrm.com/support/issues/75588) - Any Bean that has at least one email address, and no primary designation will get the oldest email address updated to be the primary.
    
#Contributing
Everyone is welcome to be involved by creating or improving existing Sugar repairs. If you would like to contribute, please make sure to review the [CONTRIBUTOR TERMS](CONTRIBUTOR TERMS.pdf). When you update this [README](README.md), please check out the [contribution guidelines](CONTRIBUTING.md) for helpful hints and tips that will make it easier to accept your pull request.

## Contributors
[Ken Brill](https://github.com/kbrill)

[Jerry Clark](https://github.com/geraldclark)

[Mark Everidge](https://github.com/meveridge)

# Licensed under Apache
© 2016 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
