<?php

namespace Drupal\data_explorer\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for the Salah module.
 */
class SalahController extends ControllerBase {

  public function content() {
    return [
      '#type' => 'markup',
      '#markup' => 'Hello, world!',
    ];
  }
}
