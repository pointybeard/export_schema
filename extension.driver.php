<?php

require_once __DIR__ . '/vendor/autoload.php';
use SymphonyPDO;

Class extension_export_schema extends Extension
{
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

    public function appendWithSelected($context) {
      $context['options'][] = ['extension-export-schema', false, __('Export Schema')];
    }

    public function checkWithSelected($context) {
      if(!isset($_POST['with-selected']) || $_POST['with-selected'] != 'extension-export-schema') {
        return;
      }

      $sql = "";
      foreach($context['checked'] as $sectionId) {
        $sql .= $this->export($sectionId);
      }

      header('Content-Type: application/octet-stream');
      header("Content-Transfer-Encoding: Binary");
      header("Content-disposition: attachment; filename=\"export_schema-" . date(Ymd_His) . ".sql\"");
      print $sql;
      exit;

    }

    public function export($sectionId) {

      $sqlResult = "";

      $db = SymphonyPDO\Loader::instance();

      // Build the section row and associated data that this section must have:
      // `tbl_sections`
      $query = $db->prepare("SELECT * FROM tbl_sections WHERE `id` = :id");
      $query->execute(array(':id' => $sectionId));
      $sectionRecord = $query->fetch(\PDO::FETCH_ASSOC);

      $sqlResult = sprintf(
        "INSERT INTO `tbl_sections` (`%s`) VALUES ('%s');",
        implode("`, `", array_keys($sectionRecord)),
        implode("', '", array_values($sectionRecord))
      ) . PHP_EOL;

      // `tbl_sections_association`
      $query = $db->prepare("SELECT * FROM tbl_sections_association WHERE `child_section_id` = :id");
      $query->execute(array(':id' => $sectionId));
      $sectionAssociations = $query->fetchAll(\PDO::FETCH_ASSOC);

      foreach($sectionAssociations as $assoc) {
        $sqlResult .= sprintf(
          "INSERT INTO `tbl_sections_association` (`%s`) VALUES ('%s');",
          implode("`, `", array_keys($assoc)),
          implode("', '", array_values($assoc))
        ) . PHP_EOL;
      }

      // @todo check for dependencies. Other sections might rely on this section and its fields

      $fields = $db->prepare("SELECT * FROM tbl_fields WHERE `parent_section` = :parent");
      $fields->execute(array(':parent' => $sectionId));

      while (($row = $fields->fetch(\PDO::FETCH_ASSOC)) !== false) {

        $sqlResult .= PHP_EOL . "-- *** `{$row['label']}` ***" . PHP_EOL;

        // Generate the data import for tbl_fields
        $sqlResult .= sprintf(
          "INSERT INTO `tbl_fields` (`%s`) VALUES ('%s');",
          implode("`, `", array_keys($row)),
          implode("', '", array_values($row))
        ) . PHP_EOL;

        // Get the field specific data
        $customFieldsTableQuery = $db->prepare(sprintf(
          "SELECT * FROM tbl_fields_%s WHERE `field_id` = :id",
          $row['type']
        ));

        $customFieldsTableQuery->execute(array(':id' => $row['id']));
        $data = $customFieldsTableQuery->fetch(\PDO::FETCH_ASSOC);
        $sqlResult .= sprintf(
          "INSERT INTO `tbl_fields_%s` (`%s`) VALUES ('%s');",
          $row['type'],
          implode("`, `", array_keys($data)),
          implode("', '", array_values($data))
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
