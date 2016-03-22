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

# Unit Tests
PHP unit tests are required for all repair actions. They should all be located in `./custom/tests/modules/supp_SugarRepairs/`.

To setup you environment for unit tests, you will need to do the following:

* Install the Sugar Repairs module to a new Sugar 7.6.1.0 instance. 7.6.1.0 is required as it has published unit tests.
* In the root of the instance, install composer. `composer install`
* Download the 7.6.1.0 unit tests from: https://github.com/sugarcrm/unit-tests/releases
* Extract the content of the tests folder from the zip that are applicable to your edition `<test folder>/<edition>/tests/` into `./tests/`.
* Install composer to the root of your Sugar directory
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
