<?php

/**
 * @file
 * Contains \Drupal\thirdlight_browser\Controller\DeleteOutputFormat.
 */

namespace Drupal\thirdlight_browser\Controller;

use Drupal\Core\Controller\ControllerBase;

class DeleteOutputFormat extends ControllerBase {

    // this is called from our outputformats.delete route. All it has
    // to do is tell the Store to delete and redirect back with a message.
    public function delete($name){
        $wasDeleted = \Drupal::service('thirdlight_browser.store')->DeleteOutputFormat($name);
        if($wasDeleted) drupal_set_message($this->t('The output format ":name" has been deleted', [':name' => $name]));
        return $this->redirect('thirdlight_browser.config');
    }

}