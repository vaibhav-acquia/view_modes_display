<?php

/**
 * @file
 * Contains \Drupal\view_modes_display\Controller\DefaultController.
 */

namespace Drupal\view_modes_display\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;

/**
 * Class DefaultController.
 *
 * @package Drupal\view_modes_display\Controller
 */
class DefaultController extends ControllerBase {

  /**
   * Preview entity view modes.
   *
   * @return string
   */
  public function preview(NodeInterface $node) {
    $entity_manager = \Drupal::entityManager();
    $view_modes_info = $entity_manager->getViewModes('node');

    // Loop through the view modes
    $build = array();
    foreach ($view_modes_info as $view_mode_name => $view_mode_info) {
      $build[] = [
        '#prefix' => '<div class="view-mode-list-item view-mode-list-item-' . $view_mode_name . '"><h1>' . $view_mode_info['label'] . '</h1>',
        '#markup' => render(entity_view($node, $view_mode_name)),
        '#suffix' => '</div>',
      ];
    }

    return $build;
  }

}
