<?php

/**
 * @file
 * Definition of \Drupal\colorbutton\Plugin\CKEditorPlugin\ThirdLightButton.
 */

namespace Drupal\thirdlight_browser\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "ThirdLightButton" plugin.
 *
 * @CKEditorPlugin(
 *   id = "thirdlight_browser",
 *   label = @Translation("Third Light"),
 *   module = "ckeditor"
 * )
 */
class ThirdLightButton extends PluginBase implements CKEditorPluginInterface, CKEditorPluginButtonsInterface {

    /**
     * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getDependencies().
     */
    function getDependencies(Editor $editor) {
        return [];
    }

    /**
     * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getLibraries().
     */
    function getLibraries(Editor $editor) {
        return [];
    }

    /**
     * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::isInternal().
     */
    function isInternal() {
        return FALSE;
    }

    /**
     * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getFile().
     */
    function getFile() {
        return drupal_get_path('module', 'thirdlight_browser') . '/plugin/plugin.js';
    }

    /**
     * Implements \Drupal\ckeditor\Plugin\CKEditorPluginButtonsInterface::getButtons().
     */
    function getButtons() {
        return [
            // Note: This key MUST match the key given to CKEDITOR.plugins.add in plugin.js
            // Otherwise the button won't actually show up in the editor itself despite
            // its "init" function being run.
            'thirdlight_browser' => [
                'label' => t('Third Light'),
                'image' => drupal_get_path('module', 'thirdlight_browser') . '/plugin/icons/thirdlight_browser.png',
            ]
        ];
    }

    /**
     * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getConfig().
     */
    public function getConfig(Editor $editor) {
        //
        // This exposes settings on the "editor" variable handed to the
        // "init" function of our CKEditor plugings plugin.js file.
        //
        return [
            "thirdlightBrowser" => $this->getTlConfig()
        ];
    }
    private function getTlConfig() {

        //
        // can user use the thirdlight browser?
        //
        $user = \Drupal::currentUser();
        if(!$user->hasPermission('use thirdlight_browser')){
            return [ "enabled" => false ];
        }

        //
        // is the browser configured enough?
        //
        $store = \Drupal::service('thirdlight_browser.store');
        $tlConfig = $store->GetGlobalconfig();
        $tlOutputFormats = $store->GetOutputformats();
        if(empty($tlConfig["url"])){
            return [ "enabled" => true, "configured" => false ];
        }

        $settings = [
            'enabled'    => true,
            'configured' => true,
            'browserUrl' => $tlConfig["url"]."/apps/cmsbrowser/index.html",
            'titleMode'  => (empty($tlConfig["titlemode"]) ? "" : $tlConfig["titlemode"]),
            "options"    => [
                "url"           => $tlConfig["url"],
                "theme"         => $tlConfig["theme"],
                "revisions"     => empty($tlConfig["revisions"]) ? false: true,
                "metadata"      => empty($tlConfig["metadata"]) ? false: true,
                "cropClasses"   => $tlOutputFormats,
                "provideSFFUrl" =>true
            ]
        ];

        //
        // is an API key in use? If not, return current settings.
        //
        if(empty($tlConfig["apikey"])){
            return $settings;
        }

        //
        // bail out if there is some error talking to third light.
        //
        try {

            $client = \Drupal::service("thirdlight_browser.api")->instance($tlConfig["url"], $tlConfig["apikey"]);

            //
            // Check that secure file fetch is enabled.
            //
            $globalSFFConfig = $client->Config_CheckFeatureAvailable(array("featureKey"=> "SECURE_FILE_FETCH_PERMANENT"));
            if(!$globalSFFConfig) {
                return [
                    'enabled' => true,
                    'configured' => false,
                    'reason' => t('Permanent Secure File Fetch is not enabled on the Third Light site, but is required for the Third Light Browser.')
                ];
            }

            //
            // If revisions arent supported, but are asked for, change that setting to false.
            //
            if($settings["options"]["revisions"]) {
                $globalRevConfig = $client->Config_CheckFeatureAvailable(array("featureKey"=> "REVISIONS"));
                if(!$globalRevConfig) {
                    $settings["options"]["revisions"] = false;
                }
            }

            //
            // If no autologin settings, we can return the settings as is.
            //
            if(empty($tlSettings["autologin"])) {
                return $settings;
            }

            switch($tlSettings["autologin"])
            {
                case "username":
                    $mode = "username";
                    $value = $user->getAccountName();
                    break;
                case "email":
                    $mode = "email";
                    $value = $user->getEmail();
                    break;
                default:
                    //unexpected value set here so return settings
                    //without alternation
                    return $settings;
            }

            //
            // Attempt login as IMS user corresponding to username/email
            // of drupal user, and bail out if that fails.
            //
            try {
                $imsUserDetails = $client->Core_ImpersonateUser(["userRef" => $value, "lookupType" => $mode]);
            } catch(\IMSApiActionException $e) {
                return [
                    'enabled' => true,
                    'configured' => false,
                    'reason' => t('No corresponding user account was found in Third Light IMS. Please contact your administrator for access.')
                ];
            }

            $settings["options"]["sessionId"] = $imsUserDetails["sessionId"];

            //
            // Bail out if user does not have SFF:
            //
            $userSFFConfig = $client->Config_checkFeatureAvailableForUser(["featureKey"=> "SECURE_FILE_FETCH_PERMANENT"]);
            if(!$userSFFConfig)
            {
                return [
                    'enabled' => true,
                    'configured' => false,
                    'reason' => t('Your IMS user account does not have the necessary permissions. Please contact your administrator, and ask them to enable publishing for you.')
                ];
            }

            //
            // If revisions asked for, but user does not have revisions, disable that.
            //
            if($settings["options"]["revisions"])
            {
                $userRevConfig = $client->Config_checkFeatureAvailableForUser(["featureKey"=> "REVISIONS_VIEW"]);
                if(!$userRevConfig)
                {
                    $settings["options"]["revisions"] = false;
                }
            }

            return $settings;

        } catch(Exception $e) {
            return [
                'enabled' => true,
                'configured' => false,
                'reason' => t('Error connecting to Third Light IMS site. Ask your administrator to check the Third Light Browser configuration.')
            ];
        }

    }
}