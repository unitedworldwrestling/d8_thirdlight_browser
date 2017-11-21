<?php

namespace Drupal\thirdlight_browser\Service;

//
// Just some miscellaneous functions we want in more than one place.
//
class Misc {

    /*
     * Mapping from theme key to theme name:
     */
    public function ThemeKeys()
    {
        return [
            "light" => t("Light"),
            "dark" => t("Dark"),
            "blue" => t("Blue")
        ];
    }

    /*
     * Mapping from alt text key to name:
     */
    public function AltTextKeys()
    {
        return [
            "" => t("None"),
            "caption" => t("Caption"),
            "copyright" => t("Copyright Notice"),
            "instructions" => t("Special Instructions")
        ];
    }

}