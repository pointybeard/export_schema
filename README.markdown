# Symphony Export Schema

- Version: 1.0.0
- Author: Alistair Kearney (hi@alistairkearney.com)
- Build Date: 9th May 2016
- Requirements: Symphony 2.6 or greater

This extension adds an "Export Schama" option to the "With Selected..." drop box on the Blueprints > Section page. It will produce an SQL schema file for the section and its fields. Useful for porting a section to a different installation.

**The SQL produced will cause existing tables to be first removed. This should not be used to update exiting sections or fields.**

## INSTALLATION

Information about [installing and updating extensions](http://getsymphony.com/learn/tasks/view/install-an-extension/) can be found in the Symphony documentation at <http://getsymphony.com/learn/>.

### Requirements

This extension requires the **[Symphony PDO library](https://github.com/pointybeard/symphony-pdo)** (`pointybeard/symphony-pdo`) to be installed via Composer. Either require this in your main composer.json file, or run `composer install` on the `extension/export_schema` directory.

    "require": {
      "php": ">=5.6.6",
      "pointybeard/symphony-pdo": "~0.1"
    }

## CHANGE LOG
    1.0.0 - Initial release

## TODO

- Check for any dependancies in other sections.
- SQL inserts are not handling NULL values
