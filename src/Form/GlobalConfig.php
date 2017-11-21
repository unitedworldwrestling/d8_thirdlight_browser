<?php

/**
 * @file
 * Contains Drupal\thirdlight_browser\Form\GlobalConfig.
 */

namespace Drupal\thirdlight_browser\Form;

use Drupal\thirdlight_browser\Service\Api;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Implements Config form controller.
 *
 */
class GlobalConfig extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $store = \Drupal::service('thirdlight_browser.store');
        $misc = \Drupal::service('thirdlight_browser.misc');

        $arrSettings = $store->GetGlobalconfig();
        $theme_options = $misc->ThemeKeys();
        $title_options = $misc->AltTextKeys();

        $boolean_options = ["0" => $this->t("No"), "1" => $this->t("Yes")];

        $form['url'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Third Light Site'),
            '#default_value' => empty($arrSettings['url']) ? '' : $arrSettings['url'],
            '#required' => TRUE,
            '#description' => $this->t('The URL of your Third Light IMS site - e.g. http://imsdemonstration.thirdlight.com/')
        ];


        $form['apikey'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Third Light API Key'),
            '#default_value' => empty($arrSettings['apikey']) ? '' : $arrSettings['apikey'],
            '#description' => $this->t('An optional API key for your Third Light IMS site. Supplying this enables additional authentication and   metadata   options.')
        ];

        $form['autologin'] = [
            '#type' => 'select',
            '#title' => $this->t('Log users in automatically'),
            '#default_value' => empty($arrSettings['autologin']) ? '0' : $arrSettings['autologin'],
            '#options' => ["0" => "Off", "username" => "By Username", "email" => "By e-mail address"],
            '#description' => $this->t('If a valid API key is specified, users can be logged in to the Third Light Browser automatically.')
        ];

        if (!function_exists("curl_init"))
        {
            $form['apikey']['#disabled'] = true;
            $form['autologin']['#disabled'] = true;

            $form['apikey']['#default_value'] = "";
            $form['autologin']['#default_value'] = "0";

            $form['apikey']['#description'] = "<strong>" . $this->t('This requires the PHP cURL extension.') . "</strong> " . $form['apikey']['  #description  '];

        }

        $form['revisions'] = [
            '#type' => 'select',
            '#title' => $this->t('Show file revisions'),
            '#default_value' => empty($arrSettings['revisions']) ? '0' : '1',
            '#options' => $boolean_options,
            '#description' => $this->t('Third Light sites support version control. Enable this to permit access to versions other than the currently     active one.')
        ];

        $form['metadata'] = [
            '#type' => 'select',
            '#title' => $this->t('Display metadata'),
            '#default_value' => empty($arrSettings['metadata']) ? '0' : '1',
            '#options' => $boolean_options,
            '#description' => $this->t('Third Light sites support rich metadata. Enable this to offer metadata to users of the Third Light Browser.')
        ];

        $form['theme'] = [
            '#type' => 'select',
            '#title' => $this->t('Choose a skin'),
            '#default_value' => $arrSettings['theme'],
            '#options' => $theme_options,
        ];

        $form['titlemode'] = [
            '#type' => 'select',
            '#title' => $this->t('Title for inserted images'),
            '#default_value' => empty($arrSettings['titlemode']) ? "" : $arrSettings["titlemode"],
            '#options' => $title_options,
            '#description' => $this->t('This allows you to set the title and alt text of images inserted based on the metadata set in your Third Light   IMS   site.')
        ];


        $form['actions'] = ['#tree' => FALSE, '#type' => 'actions'];
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save')
        ];
        $form['actions']['cancel'] = [
            '#type' => 'link',
            '#title' => $this->t('Cancel'),
            '#url' => Url::fromRoute('thirdlight_browser.config'),
        ];

        return $form;

    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'thirdlight_browser_globalconfig_form';
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {

        $data = $form_state->getValues();
        $keyValid = false;
        $arrURL = parse_url($data["url"]);
        if(empty($arrURL) || !preg_match("|^https?://|", $data["url"]))
        {
            $form_state->setErrorByName("url", $this->t('The URL provided does not appear to be valid.'));
        }
        $strTestURL = $data["url"]."/apps/cmsbrowser/index.html";
        if(function_exists("curl_init"))
        {
            $rCurl = curl_init($strTestURL);
            curl_setopt($rCurl, CURLOPT_RETURNTRANSFER, true);
            $ret = curl_exec($rCurl);
            if(curl_errno($rCurl) != 0)
            {
                $form_state->setErrorByName("url", $this->t('The URL provided does not be a Third Light site supporting the Third Light   Browser.   cURL replied: '.curl_error($rCurl)));
            }
            elseif(false === strpos($ret, "thirdlight_application"))
            {
                $form_state->setErrorByName("url", $this->t('The URL provided does not be a Third Light site supporting the Third Light Browser.'  ));
            }
        }
        elseif(ini_get("allow_url_fopen"))
        {
            $ret = @file_get_contents($strTestURL);
            if(empty($ret) || (false === strpos($ret, "thirdlight_application")))
            {
                $form_state->setErrorByName("url", $this->t('The URL provided does not be a Third Light site supporting the Third Light Browser.'  ));
            }
        }
        if(!empty($data["apikey"]))
        {
            try{
                $client = \Drupal::service("thirdlight_browser.api")->instance($data["url"], $data["apikey"]);
                $keyValid = true;
                $globalSFFConfig = $client->Config_CheckFeatureAvailable(array("featureKey"=> "SECURE_FILE_FETCH_PERMANENT"));
                if(!$globalSFFConfig)
                {
                    $form_state->setErrorByName("url", $this->t('The Third Light site does not have permanent Secure File Fetch enabled - this is required by the Third Light Browser.'));
                }
                $globalRevConfig = $client->Config_CheckFeatureAvailable(array("featureKey"=> "REVISIONS"));
                if(!$globalRevConfig && !empty($data["revisions"]))
                {
                    $form_state->setErrorByName("revisions", $this->t('Version control is disabled on the Third Light site.'));
                }
            }
            catch(\IMSApiActionException $e) {
                $form_state->setErrorByName("apikey", $this->t('The API key was rejected by the Third Light IMS server.'));
            }
            catch(\IMSApiPrerequisiteException $e) {
                $form_state->setErrorByName("apikey", $this->t("API client prerequisite missing: ").$e->getMessage());
            }
            catch(\IMSApiClientException $e) {
                $form_state->setErrorByName("url", $this->t('The URL provided does not appear to be a Third Light site.'));
            }
        }
        if(!empty($data["autologin"]) && !$keyValid)
        {
            $form_state->setErrorByName("autologin", $this->t('Automatic log in requires that the API key be configured correctly.'));
        }

    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $data = $form_state->getValues();

        //save our new global config:
        $store = \Drupal::service('thirdlight_browser.store');
        $wasSet = $store->SetGlobalconfig($data);
        if($wasSet) drupal_set_message(t('The Third Light Browser settings were saved.'));

        // provide our config route so that we redirect here after submitting:
        $form_state->setRedirect("thirdlight_browser.config");

    }

}
