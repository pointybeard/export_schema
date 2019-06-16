# Section Export Extension for Symphony CMS

-   Version: 1.1.0
-   Date: 16 June 2019
-   [Release notes](https://github.com/pointybeard/export_schema/blob/master/CHANGELOG.md)
-   [GitHub repository](https://github.com/export_schema/export_schema)

A [Symphony CMS](https://www.getsymphony.com/) extension for exporting sections as either JSON or SQL.

## Installation

This is an extension for Symphony CMS. Add it to your `/extensions` folder in your Symphony CMS installation, run `composer update` to install required packages and then enable it though the interface.

### Requirements

This extension requires PHP 7.3 or greater. For use with earlier version of PHP, please use version 1.0.2 of this extension instead (`git clone -b1.0.2 https://github.com/pointybeard/export_schema.git`).

This extension depends on the following Composer libraries:

-   [SymphonyCMS PDO Connector](https://github.com/pointybeard/symphony-pdo)
-   [Symphony CMS: Section Builder](https://github.com/pointybeard/symphony-section-builder)

Run `composer update` on the `extension/export_schema` directory to install these.

## Usage

This extension adds a new "With Selected" action to all entry tables: "Export Schema". You can choose to export either JSON or SQL.

## Support

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/pointybeard/export_schema/issues),
or better yet, fork the library and submit a pull request.

## Contributing

We encourage you to contribute to this project. Please check out the [Contributing documentation](https://github.com/pointybeard/export_schema/blob/master/CONTRIBUTING.md) for guidelines about how to get involved.

## License

"Section Export Extension for Symphony CMS" is released under the [MIT License](http://www.opensource.org/licenses/MIT).
