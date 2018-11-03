# Export Section Schema Extension for SymphonyCMS

- Version: 1.0.1
- Date: 3rd Nov 2018
- [Release notes](https://github.com/pointybeard/export_schema/blob/master/CHANGELOG.md)
- [GitHub repository](https://github.com/export_schema/export_schema)

Easily export selected sections as either JSON or SQL.

**The SQL produced will cause existing tables to be first removed. This should not be used to update exiting sections or fields.**

## INSTALLATION

Information about [installing and updating extensions](http://getsymphony.com/learn/tasks/view/install-an-extension/) can be found in the Symphony documentation at <http://getsymphony.com/learn/>.

### Requirements

This extension requires the **[Symphony PDO library](https://github.com/pointybeard/symphony-pdo)** (`pointybeard/symphony-pdo`) to be installed via Composer. Either require this in your main `composer.json` file, or run `composer install` on the `extension/export_schema` directory.

```json
"require": {
  "php": ">=5.6.6",
  "pointybeard/symphony-pdo": "~0.1"
}
```

## Usage

This extension adds a new "With Selected" action to all entry tables: "Export Schema". You can choose to export either JSON or SQL.

## Support

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/pointybeard/export_schema/issues),
or better yet, fork the library and submit a pull request.

## Contributing

We encourage you to contribute to this project. Please check out the [Contributing documentation](https://github.com/pointybeard/export_schema/blob/master/CONTRIBUTING.md) for guidelines about how to get involved.

## License

"Export Data Extension for SymphonyCMS" is released under the [MIT License](http://www.opensource.org/licenses/MIT).