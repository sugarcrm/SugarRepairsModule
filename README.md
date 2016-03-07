# Sugar Repairs Module
This module is designed to help assist with common repair issues. You should always tests any repairs against a cloned enviroment before run on a production instance. This tool is to be used at your own risk.

All repairs currently need to be run from the command line. By default, all repairs are run in test mode until a `-t false` is added to the cli command.

It is highly recommended to remove the Sugar Repairs module before upgrading.

# Running Repairs
Repairs will need to be run differently based on your environment.

##For Local & ION
For local instances and ION, you will need to change to the supp_SugarRepairs directory and run the cli.php directly.

###Testing Command:
`cd "modules/supp_SugarRepairs/" && php cli.php --repair <action>`

###Execute Command:
`cd "modules/supp_SugarRepairs/" && php cli.php --repair <action> --test false`

##For MS
For mothership, you will need to change to the instances directory and run shadow-shell:

###Testing Command:
`$options = array(
'repair' => '<action>'
);
require_once("./modules/supp_SugarRepairs/cli.php");`

###Execute Command:
`$options = array(
'repair' => '<action>',
'testing' => false
);
require_once("./modules/supp_SugarRepairs/cli.php");`

# Language Repairs
Corrects common language file issues. The various issues addressed are shown below:

##Testing Command:
`cd "modules/supp_SugarRepairs/" && php cli.php --repair lang`

##Execute Command:
`cd "modules/supp_SugarRepairs/" && php cli.php --repair lang --test false`

##Issues Addressed
* Health Check Errors:

>[Health Check Error: Bad vardefs - key] (https://support.sugarcrm.com/Knowledge_Base/Administration/Install/Troubleshooting_Health_Check_Output/Health_Check_Error_Bad_Vardefs_Key/)

>[Health Check Error: Bad vardefs - multienum] (https://support.sugarcrm.com/Knowledge_Base/Administration/Install/Troubleshooting_Health_Check_Output/Health_Check_Error_Bad_Vardefs_Multienum/)

>[Health Check Error: Found NULL values in moduleList strings] (https://support.sugarcrm.com/Knowledge_Base/Administration/Install/Troubleshooting_Health_Check_Output/Health_Check_Error_Found_NULL_Values_in_moduleList_Strings/)

* Extra CR/LF issues
>If there are extra CR/LF characters between the lines of a language file they will be eliminated

* Replaces `$GLOBALS` in language files
>Any language string that is defined as global is rewritten in place.  This assures that when duplicates are removed the correct value is left behind. The example being that `$GLOBALS['app_list_strings']['key'] = array(…);` will be converted to `$app_list_strings['key'] = array(…);`

* Duplicate Keys
>If an array key appears more than once in any given language file, only the final one will be retained.

* Redefined module list arrays
>If the entire module list array is redefined in the language file, it is then written to individual indexes rather than a redefined array. This prevents the system from having undefined modules and issues when deploying new modules through Module Builder. 
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
>If a flag is thrown, manual intervention will be required. Scenarios that can throw flags are listed below:
>
>1. All language files are tested for syntax errors and are flagged if they don’t pass.  
>2. Any custom logic hook that contains a modified language key will flagged for manual change.

* Custom Vardef Files are Updated
>If there is a custom vardef that defines a default value for a field that contains a key that needs to be changed then the VarDef file is updated.
 
* Other custom PHP files
>If a file contains a string of characters that matches a key that needs to be changed it will be flagged for possible Manual updating in the log. 
 
* Updates the database keys for enum/multienum fields when a correction is made.
* Runs the vardef repair.
* Runs the workflow repair.
* Runs the report repair.

#Team Set Repairs
Corrects common issues with team sets.

##Testing Command:
`cd "modules/supp_SugarRepairs/" && php cli.php --repair team`

##Execute Command:
`cd "modules/supp_SugarRepairs/" && php cli.php --repair team --test false`

##Issues Addressed
* Duplicate teams in a team set
> Removes any duplicate team relationships to a team set.
       
* Incorrect team counts on team sets
> Correct any team sets with invalid team counts and relationships.
    
#Workflow Repairs
Corrects common issues with workflows.

##Testing Command:
`cd "modules/supp_SugarRepairs/" && php cli.php --repair workflow`

##Execute Command:
`cd "modules/supp_SugarRepairs/" && php cli.php --repair workflow --test false`

##Issues Addressed
* Workflows with invalid fields
> Disables any workflows with missing or invalid fields

#Vardef Repairs
Corrects common issues with vardefs.

##Testing Command:
`cd "modules/supp_SugarRepairs/" && php cli.php --repair vardef`

##Execute Command:
`cd "modules/supp_SugarRepairs/" && php cli.php --repair vardef --test false`

##Issues Addressed
* Enum/Multienum fields with invalid default values.
> Attempts to find a valid default value key. If no value is found, field is left alone.
       
* Enum/Multienum fields with invalid visibility gird.
> Attempts to find a valid key. If no key is found, the grid is removed.
       
#Email Address Repairs
Corrects common issues with Email Addresses.

##Testing Command:
`cd "modules/supp_SugarRepairs/" && php cli.php --repair emailAddresses`

##Execute Command:
`cd "modules/supp_SugarRepairs/" && php cli.php --repair emailAddresses --test false`

##Issues Addressed
* Bean Records without a Primary Email Address
> Any Bean that has at least one email address, and no primary designation will get the oldest email address updated to be primary.
    
# Adding New Repair Actions
* All repair actions should be located in `./modules/supp_SugarRepairs/Classes/Repairs/` and extend the abstract class `supp_Repairs`. 
* Any custom classes should use the `supp_` prefix.

# CLI Usage
This is still under development, however, usage will be as follows:

```
cd "/<sugar>/modules/supp_SugarRepairs/" && php -f cli.php
```

# Unit Tests
PHP unit tests are required for all repair actions. They should all be located in `./custom/tests/modules/supp_SugarRepairs/`.

To setup you environment for unit tests, you will need to do the following:

* Install the Sugar Repairs module to a new Sugar 7.6.1.0 instance. 7.6.1.0 is required as it has published unit tests.
* in the root of the instance, install composer. `composer install`
* Download the 7.6.1.0 unit tests from: https://github.com/sugarcrm/unit-tests/releases
* Extract the content of the tests folder from the zip that are applicable to your edition `<test folder>/<edition>/tests/` into `./tests/`.
* Create your new unit test in `./custom/tests/modules/supp_SugarRepairs/` and make sure to add it to the `@group support` in the header comment:
```
/**
 *@group support
 */
```

* Validate the tests by running:

```
cd "/<sugar>/tests/"
phpunit -v --debug --group support --stop-on-failure
```

* Once finished, commit your test back to the repo.

# Contributors
[Ken Brill](https://github.com/kbrill)

[Jerry Clark](https://github.com/geraldclark)

[Mark Everidge](https://github.com/meveridge)
