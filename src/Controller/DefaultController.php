<?php

/**
 * @file
 * Contains \Drupal\view_modes_display\Controller\DefaultController.
 */

namespace Drupal\view_modes_display\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
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
  public function preview(ContentEntityInterface $node) {
    $entity_manager = \Drupal::entityManager();
    $view_modes_info = $entity_manager->getViewModes('node');

    $config_prefix = 'core.entity_view_display';
    $entity_type_id = $node->getEntityType()->id();

    $ids = \Drupal::configFactory()->listAll($config_prefix . '.' . $entity_type_id . '.' . $node->bundle() . '.');

    $load_ids = array();
    foreach ($ids as $id) {
      $config_id = str_replace($config_prefix . '.', '', $id);
      list(,, $display_mode) = explode('.', $config_id);
      $load_ids[] = $config_id;
    }

    $enabled_display_modes = array();
    $displays = $entity_manager->getStorage('entity_view_display')->loadMultiple($load_ids);
    foreach ($displays as $display) {
      if ($display->status()) {
        $enabled_display_modes[] = $display->get('mode');
      }
    }

    // Loop through the view modes and render in-place
    $build = array();
    foreach ($view_modes_info as $view_mode_name => $view_mode_info) {
      if (in_array($view_mode_name, $enabled_display_modes)) {
        $build[] = [
          '#prefix' => '<div class="view-mode-list-item view-mode-list-item-' . $view_mode_name . '"><h1>' . $view_mode_info['label'] . '</h1>',
          '#markup' => render(entity_view($node, $view_mode_name)),
          '#suffix' => '</div>',
        ];
      }
    }

    return $build;
  }

}
