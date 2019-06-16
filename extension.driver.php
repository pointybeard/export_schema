<?php

declare(strict_types=1);

if (!file_exists(__DIR__.'/vendor/autoload.php')) {
    throw new Exception(sprintf(
        'Could not find composer autoload file %s. Did you run `composer update` in %s?',
        __DIR__.'/vendor/autoload.php',
        __DIR__
    ));
}

require_once __DIR__.'/vendor/autoload.php';

use pointybeard\Symphony\Extensions\ExportSectionSchema;
use pointybeard\Symphony\SectionBuilder;

// This file is included automatically in the composer autoloader, however,
// Symphony might try to include it again which would cause a fatal error.
// Check if the class already exists before declaring it again.
if (!class_exists('\\Extension_Export_Schema')) {
    class Extension_Export_Schema extends Extension
    {
        public const EXPORT_TYPE_SQL = 'sql';
        public const EXPORT_TYPE_JSON = 'json';

        public function getSubscribedDelegates(): array
        {
            return [
            [
              'page' => '/blueprints/sections/',
              'delegate' => 'AddCustomActions',
              'callback' => 'appendWithSelected',
            ],
            [
              'page' => '/blueprints/sections/',
              'delegate' => 'CustomActions',
              'callback' => 'checkWithSelected',
            ],
        ];
        }

        public function appendWithSelected(array &$context): void
        {
            $context['options'][] = [
            'label' => 'Export Schema',
            'options' => [
                ['extension-export-schema-sql', false, __('SQL')],
                ['extension-export-schema-json', false, __('JSON')],
            ],
        ];
        }

        public function checkWithSelected(array &$context): void
        {
            if (!isset($_POST['with-selected']) || !preg_match('@^extension-export-schema-(json|sql)$@', $_POST['with-selected'], $matches)) {
                return;
            }

            $type = $matches[1];

            $output = $this->export($type, $context['checked']);

            header('Content-Type: application/octet-stream');
            header('Content-Transfer-Encoding: Binary');
            header(sprintf(
                'Content-disposition: attachment; filename="export_schema-%s.%s',
                date('Ymd_His'),
                $type
            ));
            echo $output;
            exit;
        }

        public function export(string $type, array $sectionIds): string
        {
            if (self::EXPORT_TYPE_JSON == $type) {
                return $this->__toJSON($sectionIds);
            } else {
                foreach ($sectionIds as $id) {
                    $sql .= PHP_EOL.PHP_EOL.$this->__toSQL($id);
                }

                $sectionNames = array_map(function ($id) {
                    return \SectionManager::fetch($id)->get('name');
                }, $sectionIds);

                return sprintf('-- ****************************************************
-- Export schema
--
-- Generated At: %s
-- Sections Included: %s
-- ****************************************************
            ', date(DATE_RFC2822), implode(', ', $sectionNames)).$sql;
            }
        }

        private function __toJSON(array $sections): string
        {
            $output = ["sections" => []];
            foreach($sections as $sectionId) {
                $output["sections"][] = json_decode(
                    (string)SectionBuilder\Models\Section::loadFromId($sectionId),
                    true
                );
            }
            return json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        private function buildInsert(
            string $table,
            array $fields,
            array $exclude = [],
            array $numericFields = ['id', '.+_id', 'sortorder', 'parent_section'],
            array $nullFields = ['date', 'relation_id']
        ): string {
            $insert = new ExportSectionSchema\Insert(
                $table,
                $exclude,
                $numericFields,
                $nullFields
            );

            foreach ($fields as $name => $value) {
                $insert->$name = $value;
            }

            return (string) $insert;
        }

        private function __toSQL($sectionId)
        {
            $sqlResult = '-- -------------------------------------------------
-- *** '.\SectionManager::fetch($sectionId)->get('name').' ***
-- -------------------------------------------------'.PHP_EOL;

            $db = SymphonyPDO\Loader::instance();

            // Build the section row and associated data that this section must have:
            // `tbl_sections`
            $query = $db->prepare('SELECT * FROM tbl_sections WHERE `id` = :id');
            $query->execute(array(':id' => $sectionId));
            $sqlResult .= $this->buildInsert(
                'tbl_sections',
                $query->fetch(\PDO::FETCH_ASSOC)
            ).PHP_EOL;

            // `tbl_sections_association`
            $query = $db->prepare('SELECT * FROM tbl_sections_association WHERE `child_section_id` = :id');
            $query->execute(array(':id' => $sectionId));

            foreach ($query->fetchAll(\PDO::FETCH_ASSOC) as $a) {
                $sqlResult .= $this->buildInsert(
                    'tbl_sections_association',
                    $a,
                    [],
                    [],
                    ['interface', 'editor']
                ).PHP_EOL;
            }

            // @todo check for dependencies. Other sections might rely on this section and its fields

            $fields = $db->prepare('SELECT * FROM tbl_fields WHERE `parent_section` = :parent');
            $fields->execute(array(':parent' => $sectionId));

            while (false !== ($row = $fields->fetch(\PDO::FETCH_ASSOC))) {
                $sqlResult .= PHP_EOL."-- `{$row['label']}` ".PHP_EOL;

                // Generate the data import for tbl_fields
                $sqlResult .= $this->buildInsert(
                    'tbl_fields',
                    $row
                ).PHP_EOL;

                // Get the field specific data
                $customFieldsTableQuery = $db->prepare(sprintf(
                    'SELECT * FROM tbl_fields_%s WHERE `field_id` = :id',
                    $row['type']
            ));

                $customFieldsTableQuery->execute(array(':id' => $row['id']));
                $d = $customFieldsTableQuery->fetch(\PDO::FETCH_ASSOC);
                $d['id'] = null;
                $sqlResult .= $this->buildInsert(
                    'tbl_fields_'.$row['type'],
                    $d,
                    [],
                    ['id', '.+_id', 'sortorder', 'parent_section'],
                    ['id', 'date', 'relation_id']
                ).PHP_EOL;

                // Build the schema for all of the entry_data_* tables
                $table = 'tbl_entries_data_'.$row['id'];
                $createTableQuery = $db->prepare("SHOW CREATE TABLE `{$table}`");
                $createTableQuery->execute();

                $sqlResult .= "DROP TABLE IF EXISTS `{$table}`;".PHP_EOL;
                $sqlResult .= $createTableQuery->fetch()[1].';'.PHP_EOL.PHP_EOL;
            }

            return $sqlResult;
        }
    }
}
