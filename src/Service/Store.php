<?php

namespace Drupal\thirdlight_browser\Service;

use \Drupal\Core\Database\Connection;
/*
 * Our "thirdlight_browser.store" service manages getting and setting things to the drupal DB.
 */
class Store {

    protected $conn;

    public function __construct(Connection $database_connection) {
        //
        // This makes use of the dependency injection stuff. thirdlight_browser.services.yml
        // defines this service, and says that it requires the "database" service to be injected.
        // other services can then ask for this Store class in their construct calls
        // and automagically have this provided to them, or we can use \Drupal::service('name')
        // (not recommended where it can be avoided).
        //
        $this->conn = $database_connection;
    }

    /*
     * Get and Set Global Configuration details from the DB
     */
    public function GetGlobalconfig() {
        $select = $this->conn->select('thirdlight_browser_config', 'c');
        $result = $select->fields('c', array("parameter","value"))->execute();
        $arrConfig = array();
        while($row = $result->fetchAssoc())
        {
            $arrConfig[$row["parameter"]] = $row["value"];
        }
        return $arrConfig;
    }
    public function SetGlobalconfig($data) {

        try {

            $this->conn->merge('thirdlight_browser_config')
                ->key(array('parameter'=>'url'))
                ->fields(array("value"=>$data["url"]))->execute();
            $this->conn->merge('thirdlight_browser_config')
                ->key(array('parameter'=>'theme'))
                ->fields(array("value"=>$data["theme"]))->execute();
            $this->conn->merge('thirdlight_browser_config')
                ->key(array('parameter'=>'revisions'))
                ->fields(array("value"=>$data["revisions"]))->execute();
            $this->conn->merge('thirdlight_browser_config')
                ->key(array('parameter'=>'metadata'))
                ->fields(array("value"=>$data["metadata"]))->execute();
            $this->conn->merge('thirdlight_browser_config')
                ->key(array('parameter'=>'titlemode'))
                ->fields(array("value"=>$data["titlemode"]))->execute();
            $this->conn->merge('thirdlight_browser_config')
                ->key(array('parameter'=>'apikey'))
                ->fields(array("value"=>$data["apikey"]))->execute();
            $this->conn->merge('thirdlight_browser_config')
                ->key(array('parameter'=>'autologin'))
                ->fields(array("value"=>$data["autologin"]))->execute();
            return true;

        }
        catch (\Exception $e) {
            Store::ShowError("Setting global settings failed", $e);
            return false;
        }

    }

    /*
     * Get/Add/Delete Output Format details from the DB
     */
    public function GetOutputformats() {
        $select = $this->conn->select('thirdlight_browser_variants', 'v');
        $result = $select->fields('v', array("name","width","height","format","className"))->execute();
        $arrVariants = array();
        $key = 0;
        while($row = $result->fetchAssoc())
        {
            $arrVariants[] = [
                "key"=>$key,
                "name"=>$row["name"],
                "width"=>intval($row["width"]),
                "height"=>intval($row["height"]),
                "format"=>$row["format"],
                "className"=>$row["className"]
            ];
            ++$key;
        }
        return $arrVariants;
    }

    public function AddOutputformat($data) {

        try {

            // incase there is already a format with the same
            // name, delete the existing one first. This should
            // not be needed but is here just in case.
            $this->conn
                ->delete('thirdlight_browser_variants')
                ->condition('name', $data['name'])
                ->execute();

            // insert our new output format:
            $this->conn
                ->insert('thirdlight_browser_variants')
                ->fields([
                    "name" => $data['name'],
                    "width" => $data['width'],
                    "height" => $data['height'],
                    "format" => $data['format'],
                    "className" => $data['className'],
                ])
                ->execute();
            return true;
        }
        catch (\Exception $e) {
            Store::ShowError("Adding output format failed", $e);
            return false;
        }

    }
    public function DeleteOutputformat($formatName) {

        try {
            $this->conn->delete('thirdlight_browser_variants')->condition('name', $formatName)->execute();
            return true;
        }
        catch (\Exception $e) {
            Store::ShowError("Deleting output format failed", $e);
            return false;
        }

    }

    private function ShowError($message, $e) {
        drupal_set_message(t($message.'. Message = %message, query = %query', [
            '%message' => $e->getMessage(),
        ]), 'error');
    }

}