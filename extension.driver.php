<?php

require_once __DIR__ . '/vendor/autoload.php';

use ExportSectionSchema\Lib;

class extension_export_schema extends Extension
{
    const EXPORT_TYPE_SQL = 'sql';
    const EXPORT_TYPE_JSON = 'json';

    public function getSubscribedDelegates()
    {
        return [
            [
              'page'     => '/blueprints/sections/',
              'delegate' => 'AddCustomActions',
              'callback' => 'appendWithSelected'
            ],
            [
              'page'     => '/blueprints/sections/',
              'delegate' => 'CustomActions',
              'callback' => 'checkWithSelected'
            ]
        ];
    }

    public function appendWithSelected($context)
    {
        $context['options'][] = [
            "label" => "Export Schema",
            "options" => [
                ['extension-export-schema-sql', false, __('SQL')],
                ['extension-export-schema-json', false, __('JSON')],
            ]
        ];
    }

    public function checkWithSelected($context)
    {
        if (!isset($_POST['with-selected']) || !preg_match("@^extension-export-schema-(json|sql)$@", $_POST['with-selected'], $matches)) {
            return;
        }

        $type = $matches[1];

        $output = $this->export($type, $context['checked']);

        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary");
        header(sprintf(
          'Content-disposition: attachment; filename="export_schema-%s.%s',
          date('Ymd_His'),
          $type
      ));
        print $output;
        exit;
    }


    public function export($type, array $sectionIds)
    {
        if ($type == self::EXPORT_TYPE_JSON) {
            return $this->__toJSON($sectionIds);
        } else {
            foreach ($sectionIds as $id) {
                $sql .= PHP_EOL . PHP_EOL . $this->__toSQL($id);
            }

            $sectionNames = array_map(function ($id) {
                return \SectionManager::fetch($id)->get('name');
            }, $sectionIds);

            return sprintf("-- ****************************************************
-- Export schema
--
-- Generated At: %s
-- Sections Included: %s
-- ****************************************************
            ", date(DATE_RFC2822), implode(', ', $sectionNames)) . $sql;
        }
    }

    private function __toJSON(array $sections)
    {
        $db = SymphonyPDO\Loader::instance();
        $result = ['sections' => []];
        foreach ($sections as $id) {

            // `tbl_sections`
            $query = $db->query("SELECT * FROM tbl_sections WHERE `id` = {$id}");
            $section = $query->fetch(\PDO::FETCH_ASSOC);

            // `tbl_sections_association`
            $query = $db->query("SELECT * FROM tbl_sections_association WHERE `child_section_id` = {$id}");

            $section['associations'] = [];
            foreach ($query->fetchAll(\PDO::FETCH_ASSOC) as $a) {
                $association = [
                    "hide_association" => $a['hide_association'],
                    "interface" => $a['interface'],
                    "editor" => $a['editor'],
                ];

                $association['parent'] = [
                    'section' => \SectionManager::fetch($a['parent_section_id'])->get('handle'),
                    'field' => \FieldManager::fetch($a['parent_section_field_id'])->get('element_name')
                ];

                $association['child'] = [
                    'section' => \SectionManager::fetch($a['child_section_id'])->get('handle'),
                    'field' => \FieldManager::fetch($a['child_section_field_id'])->get('element_name')
                ];

                $section['associations'][] = $association;
            }

            $query = $db->query(
                "SELECT * FROM tbl_fields WHERE `parent_section` = {$id}"
            );

            $fields = [];
            while (($row = $query->fetch(\PDO::FETCH_ASSOC)) !== false) {

                // tbl_fields_XX
                $customFieldsTableQuery = $db->query(sprintf(
                    "SELECT * FROM tbl_fields_%s WHERE `field_id` = " . $row['id'],
                    $row['type']
                ));

                $row['custom'] = $customFieldsTableQuery->fetch(\PDO::FETCH_ASSOC);

                unset($row['parent_section']);
                unset($row['custom']['field_id']);

                foreach ($row['custom'] as $key => $value) {
                    if (preg_match("@_field_id$@i", $key)) {
                        $row['custom']['related'] = [];

                        $f = \FieldManager::fetch($row['custom'][$key]);
                        $row['custom']['related']['section'] = \SectionManager::fetch(
                            $f->get('parent_section')
                        )->get('handle');

                        unset($row['custom'][$key]);

                        $row['custom']['related']['field'] = $f->get('element_name');
                        unset($row['custom'][$key]);
                    }
                }

                unset($row['custom']['id']);
                unset($row['id']);
                $fields[] = $row;
            }

            $section['fields'] = $fields;

            unset($section['id']);
            $result['sections'][] = $section;
        }

        return json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function buildInsert($table, $fields, $exclude=[], $numericFields = [
        'id',
        '.+_id',
        'sortorder',
        'parent_section'
    ], $nullFields = [
        'date', 'relation_id'
    ])
    {
        $insert = new Lib\Insert(
            $table,
            $exclude,
            $numericFields,
            $nullFields
        );

        foreach ($fields as $name => $value) {
            $insert->$name = $value;
        }

        return (string)$insert;
    }

    private function __toSQL($sectionId)
    {
        $sqlResult = "-- -------------------------------------------------
-- *** ".\SectionManager::fetch($sectionId)->get('name')." ***
-- -------------------------------------------------" . PHP_EOL;

        $db = SymphonyPDO\Loader::instance();

        // Build the section row and associated data that this section must have:
        // `tbl_sections`
        $query = $db->prepare("SELECT * FROM tbl_sections WHERE `id` = :id");
        $query->execute(array(':id' => $sectionId));
        $sqlResult .= $this->buildInsert(
            'tbl_sections',
            $query->fetch(\PDO::FETCH_ASSOC)
        ) . PHP_EOL;

        // `tbl_sections_association`
        $query = $db->prepare("SELECT * FROM tbl_sections_association WHERE `child_section_id` = :id");
        $query->execute(array(':id' => $sectionId));

        foreach ($query->fetchAll(\PDO::FETCH_ASSOC) as $a) {
            $sqlResult .= $this->buildInsert(
                'tbl_sections_association',
                $a,
                [],
                [],
                ['interface', 'editor']
            ) . PHP_EOL;
        }

        // @todo check for dependencies. Other sections might rely on this section and its fields

        $fields = $db->prepare("SELECT * FROM tbl_fields WHERE `parent_section` = :parent");
        $fields->execute(array(':parent' => $sectionId));

        while (($row = $fields->fetch(\PDO::FETCH_ASSOC)) !== false) {
            $sqlResult .= PHP_EOL . "-- `{$row['label']}` " . PHP_EOL;

            // Generate the data import for tbl_fields
            $sqlResult .= $this->buildInsert(
              'tbl_fields',
              $row
          ) . PHP_EOL;

            // Get the field specific data
            $customFieldsTableQuery = $db->prepare(sprintf(
                "SELECT * FROM tbl_fields_%s WHERE `field_id` = :id",
                $row['type']
            ));

            $customFieldsTableQuery->execute(array(':id' => $row['id']));
            $d = $customFieldsTableQuery->fetch(\PDO::FETCH_ASSOC);
            $d['id'] = null;
            $sqlResult .= $this->buildInsert(
                'tbl_fields_' . $row['type'],
                $d,
                [],
                [
                    'id', '.+_id', 'sortorder', 'parent_section'
                ],
                [
                    'id', 'date', 'relation_id'
                ]
            ) . PHP_EOL;

            // Build the schema for all of the entry_data_* tables
            $table = "tbl_entries_data_".$row['id'];
            $createTableQuery = $db->prepare("SHOW CREATE TABLE `{$table}`");
            $createTableQuery->execute();

            $sqlResult .= "DROP TABLE IF EXISTS `{$table}`;" . PHP_EOL;
            $sqlResult .= $createTableQuery->fetch()[1] . ";" . PHP_EOL . PHP_EOL;
        }

        return $sqlResult;
    }
}
