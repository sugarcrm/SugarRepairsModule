# Contribution Guidelines
Please ensure your pull request adheres to the following guidelines:

* Please search previous suggestions before making a new one, as yours may be a duplicate.
* Put a link to each library in your pull request ticket, so they're easier to look at.
* There should be no code, e.g. zip files, in the pull request (or the repository itself). This is information and listing purposes only. 
* Use the following format for libraries: \[LIBRARY\]\(LINK\) - DESCRIPTION.
* Keep descriptions short and simple. 
* End all descriptions with a full stop/period.
* Check your spelling and grammar.
* Make sure your text editor is set to remove trailing whitespace.

# Adding New Repair Actions
* All repair actions should be located in `./modules/supp_SugarRepairs/Classes/Repairs/` and extend the abstract class `supp_Repairs`. 
* Any custom classes should use the `supp_` prefix.
* If creating a new repair type, please add it to the drop down list in `./src/SugarModules/language/application/en_us.lang.php`

# Unit Tests
PHP unit tests are required for all repair actions. They should all be located in `./custom/tests/modules/supp_SugarRepairs/`.

To setup you environment for unit tests, you will need to do the following:

* Install the Sugar Repairs module to a new Sugar 7.x instance.
* In the root of the instance, install composer. `composer install`
* Download the 7.x unit tests from: https://github.com/sugarcrm/unit-tests/releases
* Extract the content of the tests folder from the zip that are applicable to your edition `<extract folder>/<edition>/tests/` into `./tests/`.
* Install composer to the root of your Sugar directory. `composer install`
* Set permissions on your entire Sugar instance
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

## PHPUNIT Issues
If you run into any errors with missing files from the tests, you can remove the broken test from your local. My list of changes per version are listed below

### 7.8 
```
cd sugar/tests
rm -Rf clients
rm -Rf include
rm -Rf modules
rm -Rf upgrade
```

#Travis-CI Tests

##Encryption setup
```
cd .travis
rm encrypt.tar.gz
rm encrypt.tar.gz.enc
tar -czvf encrypt.tar.gz encrypt
openssl aes-256-cbc -k "password" -in encrypt.tar.gz -out encrypt.tar.gz.enc

upload file to ~/home
connect to MS

upload scarlett.sugarondemand.com encrypt.tar.gz.enc
ms_support connect scarlett.sugarondemand.com

mv /tmp/encrypt.tar.gz.enc custom/hosted/encrypt.tar.gz.enc
```
