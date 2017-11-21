<?php

/**
 * @file
 * Contains \Drupal\thirdlight_browser\Controller\Config.
 */

namespace Drupal\thirdlight_browser\Controller;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;

function markup($html){
    return [
        '#markup' => $html
    ];
}
function h3($text){
    return markup("<h3>".$text."</h3>");
}

class Config extends ControllerBase {
    public function configPage() {

        $store = \Drupal::service('thirdlight_browser.store');
        $globalConfig = $store->GetGlobalconfig();
        $outputFormats = $store->GetOutputformats();

        $misc = \Drupal::service('thirdlight_browser.misc');
        $themeOptions = $misc->ThemeKeys();
        $titleOptions = $misc->AltTextKeys();

        $output = [];

        //
        // A table showing our general configuration:
        //
        $output["gc_table"] = [
            '#type' => 'table',
            '#caption' => h3("Global settings"),
            '#header' => [$this->t("Option"), $this->t("Setting")]
        ];

        // PHP arrays are ordered, and all the table needs is more key value pairs
        // which themselves contain 2 entries for the 2 rows of our table.
        $output["gc_table"][] = [
            markup( $this->t('Third Light Site') ),
            markup( (empty($globalConfig["url"]) ? "<i>" . $this->t('Not configured') . "</i>" : $globalConfig["url"]) )
        ];
        $output["gc_table"][] = [
            markup( $this->t('API Key') ),
            markup( "<i>" . $this->t(empty($globalConfig["apikey"]) ? "Not set" : "Set") . "</i>" )
        ];
        $output["gc_table"][] = [
            markup( $this->t('Automatic login') ),
            markup( "<i>" . $this->t(empty($globalConfig["apikey"]) || empty($globalConfig["autologin"]) ? "Disabled" : ($globalConfig["autologin"  ]   == "username" ? "By username" : "By e-mail address")) . "</i>" )
        ];
        if (!empty($globalConfig["theme"]) && array_key_exists($globalConfig["theme"], $themeOptions))
        {
            $output["gc_table"][] = [
                markup( $this->t('Theme') ),
                markup( $themeOptions[$globalConfig["theme"]] )
            ];
        }
        $output["gc_table"][] = [
            markup( $this->t('Display revisions') ),
            markup( empty($globalConfig["revisions"]) ? $this->t('No') : $this->t('Yes') )
        ];
        $output["gc_table"][] = [
            markup( $this->t('Display metadata') ),
            markup( empty($globalConfig["metadata"]) ? $this->t('No') : $this->t('Yes') )
        ];
        $output["gc_table"][] = [
            markup( $this->t('Title for inserted images') ),
            markup( empty($globalConfig["titlemode"]) ? "<i>".$this->t('None')."</i>" : $titleOptions[$globalConfig["titlemode"]] )
        ];
        $output["edit_globalconfig"] = [
            '#type' => 'link',
            '#title' => $this->t('Edit Settings'),
            '#url' => Url::fromRoute('thirdlight_browser.config.globalsettings')
        ];


        //
        // A table showing the output formats currently available.
        //
        $output["of_table"] = [
            '#type' => 'table',
            '#caption' => h3( $this->t("Output formats") ),
            '#header' => [$this->t('Name'), $this->t('Details'), $this->t('Operations')]
        ];
        foreach ($outputFormats as $format)
        {
            $output["of_table"][] = [
                markup( $format["name"] ),
                markup( $format["format"] . " &ndash; " . $format["width"] . " &#10005; " . $format["height"] ),
                [
                    [
                        '#type' => 'link',
                        '#title' => $this->t('edit'),
                        '#url' => Url::fromRoute('thirdlight_browser.config.outputformats.edit', ["name" => $format["name"]])
                    ],
                    markup(" "),
                    [
                        '#type' => 'link',
                        '#title' => $this->t('delete'),
                        '#url' => Url::fromRoute('thirdlight_browser.config.outputformats.delete', ["name" => $format["name"]])
                    ]
                ]
            ];
        }
        $output["add_outputformat"] = [
            '#type' => 'link',
            '#title' => $this->t('Add Output Format'),
            '#url' => Url::fromRoute('thirdlight_browser.config.outputformats.add')
        ];


        return $output;

    }
}