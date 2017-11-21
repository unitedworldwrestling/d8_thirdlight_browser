<?php

/**
 * @file
 * Contains Drupal\thirdlight_browser\Form\OutputFormats.
 */

namespace Drupal\thirdlight_browser\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Implements Config form controller.
 *
 */
class OutputFormats extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormID()
    {
        return 'thirdlight_browser_outputformats_form';
    }

    /**
     * {@inheritdoc}
     *
     * Note that the '$name' parameter's name must match that passed in to the
     * route; some dependency injection magic happens here to inspect the funciton
     * and pass in the parameter if names match.
     *
     */
    public function buildForm(array $form, FormStateInterface $form_state, $name = null)
    {

        // $name is passed in to the form route if we are editing a variant
        // rather than just adding one. If it is provided, we set a _variant entry
        // in the form (pointing to the name of the variant we are editing) so that we
        // know what to do when validating and submitting. else, we start with
        // empty details.
        if (empty($name))
        {
            $details = [
                "name" => "",
                "width" => "",
                "height" => "",
                "format" => "",
                "className" => "",
            ];
        }
        else
        {
            $store = \Drupal::service('thirdlight_browser.store');
            $outputFormats = $store->GetOutputformats();
            foreach ($outputFormats as $format)
            {
                if (0 == strcasecmp($format["name"], $name))
                {
                    $details = $format;
                    break;
                }
            }
            if (empty($details))
            {
                drupal_set_message($this->t('The output format could not be found.'), 'error');
                $this->redirect('thirdlight_browser.config');
            }
            $form['_variant'] = [
                "#type" => 'value',
                "#value" => $details["name"]
            ];
        }

        $form['name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Name'),
            '#default_value' => $details["name"],
            '#required' => true
        ];

        $form['width'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Width'),
            '#default_value' => $details["width"],
            '#required' => true,
            '#description' => $this->t('Width of the image, in pixels')
        ];

        $form['height'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Height'),
            '#default_value' => $details["height"],
            '#required' => true,
            '#description' => $this->t('Height of the image, in pixels')
        ];

        $form['format'] = [
            '#type' => 'select',
            '#title' => $this->t('Format'),
            '#default_value' => $details["format"],
            '#options' => ["JPG" => "JPG", "PNG" => "PNG", "GIF" => "GIF"],
            '#description' => $this->t('File format for the image')
        ];

        $form['className'] = [
            '#type' => 'textfield',
            '#title' => $this->t('CSS Class'),
            '#default_value' => $details["className"],
            '#description' => $this->t('An optional CSS class to apply to images inserted using this category.')
        ];

        // buttons to save and cancel:
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
    public function validateForm(array &$form, FormStateInterface $form_state) {

        $data = $form_state->GetValues();
        $store = \Drupal::service('thirdlight_browser.store');

        // either we are not editing an existing variant, or we are and we have
        // changed its name, so let's check that the new name is not taken.
        if(empty($data["_variant"]) || ($data["_variant"] != $data["name"])) {
            $outputFormats = $store->GetOutputformats();
            foreach($outputFormats as $format)
            {
                if(0 == strcasecmp($format["name"], $data["name"]))
                {
                    $form_state->setErrorByName("name", t('An output format already exists with that name'));
                    break;
                }
            }
        }

        // check dimensions are valid ints within acceptable range.
        foreach(array("width", "height") as $dim) {
            $intDim = intval($data[$dim], 10);
            if($intDim != $data["$dim"]) {
                $form_state->setErrorByName($dim, t(ucfirst($dim).' must be specified as a number in pixels.'));
            }
            elseif($intDim < 1) {
                $form_state->setErrorByName($dim, t(ucfirst($dim).' must be a positive number of pixels.'));
            }
            elseif($intDim > 10000) {
                $form_state->setErrorByName($dim, t(ucfirst($dim).' exceeds supported limit of 10,000px.'));
            }
        }
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $data = $form_state->GetValues();
        $store = \Drupal::service('thirdlight_browser.store');

        $deleteWorked = true;
        if(!empty($data["_variant"]))
        {
            // we are provided _variant, which means we are editing an existing
            // output format, so delete that one and insert the new data instead.
            $deleteWorked = $store->DeleteOutputformat($data["_variant"]);
        }

        if($deleteWorked){
            $wasAdded = $store->AddOutputformat($data);
            if($wasAdded) drupal_set_message(t('The output format settings were saved.'));
        }

        $form_state->setRedirect("thirdlight_browser.config");
    }

}
