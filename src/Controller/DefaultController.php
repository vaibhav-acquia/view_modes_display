<?php

namespace Drupal\view_modes_display\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\node\NodeInterface;
use Drupal\block_content\BlockContentInterface;
use Drupal\user\UserInterface;

/**
 * Class DefaultController.
 *
 * @package Drupal\view_modes_display\Controller
 */
class DefaultController extends ControllerBase {

  /**
   * Returns content of the node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return array
   *   Preview content of the node.
   */
  public function previewNode(NodeInterface $node) {
    return $this->preview($node);
  }

  /**
   * Returns content of the block.
   *
   * @param \Drupal\block_content\BlockContentInterface $block_content
   *   The block content.
   *
   * @return array
   *   Preview content of the block.
   */
  public function previewBlockContent(BlockContentInterface $block_content) {
    return $this->preview($block_content);
  }

  /**
   * Returns user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   *
   * @return array
   *   Preview user.
   */
  public function previewUser(UserInterface $user) {
    return $this->preview($user);
  }

  /**
   * Preview entity view modes.
   *
   * @param \Drupal\Core\ContentEntityInterface $entity
   *   Content Entity Interface.
   *
   * @return array
   *   Preview content of entity view modes.
   */
  public function preview(ContentEntityInterface $entity) {
    $entityTypeId = $entity->getEntityType()->get('id');

    $entityDisplayRepository = \Drupal::service('entity_display.repository');
    $viewModes = $entityDisplayRepository->getViewModes($entityTypeId);

    $configPrefix = 'core.entity_view_display';

    $configKeys = \Drupal::configFactory()
      ->listAll($configPrefix . '.' . $entityTypeId . '.' . $entity->bundle() . '.');

    $displayKeys = [];
    foreach ($configKeys as $configKey) {
      $displayKeys[] = str_replace($configPrefix . '.', '', $configKey);
    }

    $entityManager = \Drupal::entityTypeManager();
    $displays = $entityManager->getStorage('entity_view_display')->loadMultiple($displayKeys);

    $enabledDisplayModes = [];
    foreach ($displays as $display) {
      if ($display->status()) {
        $enabledDisplayModes[] = $display->get('mode');
      }
    }

    if (!array_key_exists('full', $viewModes)) {
      $viewModes['full'] = [
        'label' => t('Default'),
      ];
    }

    if (!array_key_exists('full', $enabledDisplayModes)) {
      $enabledDisplayModes[] = 'full';
    }

    $viewBuilder = \Drupal::entityTypeManager()
      ->getViewBuilder($entity->getEntityTypeId());

    $renderArray = [];
    foreach ($viewModes as $viewMode => $viewModeData) {
      if (in_array($viewMode, $enabledDisplayModes)) {
        $renderArray[] = [
          '#prefix' => '<div class="view-mode-list-item view-mode-list-item-' . $viewMode . '"><h1>' . $viewModeData['label'] . '</h1>',
          '#markup' => render($viewBuilder->view($entity, $viewMode)),
          '#suffix' => '</div>',
        ];
      }
    }

    return $renderArray;
  }

}
