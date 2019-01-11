<?php

namespace Drupal\ngf_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\views\Views;

/**
 * Discover page controller.
 */
class DiscoverPageController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function discoverPage() {

    $render_array = [];

    $view = Views::getView('ngf_groups');
    $view->setDisplay('groups_block');

    // Add the groups view title to the render array.
    $render_array[] = [
      '#type' => 'markup',
      '#markup' => '<h1>' . $view->getTitle() . '</h1>',
    ];

    // Add the groups view to the render array.
    $render_array[] = $view->render();

    return $render_array;
  }
}
