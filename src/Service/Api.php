<?php

namespace Drupal\thirdlight_browser\Service;

require_once(__DIR__ . "/../imsapiclient.php");

//
// A service that exposes an instance of our API client for us.
//
class Api {

    public function instance($strIMSUrl, $strApiKey = null, $arrOpts = null){
        return new \IMSApiClient($strIMSUrl, $strApiKey, $arrOpts);
    }

}
