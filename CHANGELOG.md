# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

**View all [Unreleased][] changes here**

## [1.1.0]
#### Changed
-   Requiring PHP 7.3 or newer
-   Changed namespace from `ExportSectionSchema\Lib` to `pointybeard\Symphony\Extensions\ExportSectionSchema` to be more consistent with other projects
-   Using `pointybeard/symphony-section-builder` for JSON generation

## [1.0.1]
#### Changed
-   Ensuring that insert SQL for all `tbl_fields_x` tables has a null value for ID field.

## [1.0.1]
#### Added
-   Added JSON export functionality
-   Added Insert class to help with generating SQL statements

## 1.0.0
#### Added
-   Initial release

[Unreleased]: https://github.com/pointybeard/export_data/compare/1.1.0...integration
[1.1.0]: https://github.com/pointybeard/export_data/compare/1.0.2...1.1.0
[1.0.2]: https://github.com/pointybeard/export_data/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/pointybeard/export_data/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/pointybeard/export_data/compare/0.1.0...1.0.0
