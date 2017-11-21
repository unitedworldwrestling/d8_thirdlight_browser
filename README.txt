Third Light Drupal Module 0.3
=============================

Overview
--------
This module provides a plugin for the CKEditor to enable the insertion of images from a Third Light IMS site.

Requirements
------------
  - Drupal 8.x,
  - CKEditor enabled (it is included and enabled by default in Drupal 8.x)

Installation / Configuration
----------------------------

   1. Unzip the module files to the "modules" directory of your Drupal installation. It should now
      contain a "thirdlight_browser" directory.
   2. Enable the module in the "Manage > Extend > Content Authoring" section.
   3. Review configuration permissions in the "Manage > People > Permissions" section.
   4. Configure the plugin by going to "Manage -> Configuration -> Content Authoring -> Third Light Browser".
      At a minimum you'll need to click "Edit Settings" and set the URL to your Third Light site.
      You can also define output formats here, to determine how images from Third Light can be exported.
   5. Drag the Third Light plugin to your CKEditor toolbar in the
      "Manage -> Configuration -> Content Authoring -> Text formats and editors -> Confgure" pages.
      - Ensure that in the "Enabled filters" section, "Restrict images to this site" is NOT checked.
      - If "Limit allowed HTML tags" is checked, ensure that at a minimum, "<img src height width>" is
        allowed in the "Filter settings -> Allowed HTML Tags" section.

Help
-------------------
If you are looking for more information, have any trouble with the configuration of the module
or if you found an issue, please review the Third Light documentation at:
  http://www.thirdlight.com/docs/display/integration/Home

