<?php

namespace Drupal\view_modes_display\Service;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Class PreviewFactory.
 *
 * @package Drupal\view_modes_display\Service
 */
class PreviewFactory {

  /**
   * ConfigFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * EntityDisplayRepository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected EntityDisplayRepositoryInterface $entityDisplayRepository;

  /**
   * BlockManager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected BlockManagerInterface $blockManager;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * DefaultController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config Factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository
   *   Entity Display Repository.
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   *  Block Manager.
   * @param \Drupal\Core\Render\RendererInterface
   *  Renderer.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    EntityTypeManagerInterface $entityTypeManager,
    EntityDisplayRepositoryInterface $entityDisplayRepository,
    BlockManagerInterface $blockManager,
    RendererInterface $renderer
  ) {
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityDisplayRepository = $entityDisplayRepository;
    $this->blockManager = $blockManager;
    $this->renderer = $renderer;
  }

  /**
   * Preview entity view modes.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Content Entity Interface.
   *
   * @return array
   *   Preview content of entity view modes.
   */
  public function preview(ContentEntityInterface $entity): array {
    $entityTypeId = $entity->getEntityType()->get('id');

    $entityDisplays = $this->getEntityDisplays($entityTypeId, $entity->bundle());
    $enabledDisplayModes = $this->getEnabledDisplayModes($entityDisplays);

    $viewModes = $this->entityDisplayRepository->getViewModes($entityTypeId);

    $renderArray = [];
    foreach ($viewModes as $viewMode => $viewModeData) {
      if (!in_array($viewMode, $enabledDisplayModes)) {
        continue;
      }

      $markup = $this->buildMarkup($entity, $viewMode);
      $renderArray[] = [
        '#prefix' => '<div class="view-mode-list-item view-mode-list-item-' . $viewMode . '"><div class="view-mode-list-item-label">' . $viewModeData['label'] . '</div><div class="view-mode-list-item-content">',
        '#markup' => $this->renderer->render($markup),
        '#suffix' => '</div></div>',
      ];
    }

    return $renderArray;
  }

  /**
   * Returns array of enabled displays.
   *
   * @param array $displays
   *   Entity displays.
   *
   * @return array
   *   Array of enabled display modes.
   */
  public function getEnabledDisplayModes(array $displays): array {
    $enabledDisplayModes = [];
    foreach ($displays as $display) {
      if ($display->status()) {
        $enabledDisplayModes[] = $display->get('mode');
      }
    }

    if (!array_key_exists('full', $enabledDisplayModes)) {
      $enabledDisplayModes[] = 'full';
    }

    return $enabledDisplayModes;
  }

  /**
   * Returns all display for an entity.
   *
   * @param string $entityTypeId
   *   Entity id.
   * @param string $entityBundle
   *   Entity bundle.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Array of entity displays.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getEntityDisplays(string $entityTypeId, string $entityBundle): array {
    $configPrefix = 'core.entity_view_display';
    $prefix = $configPrefix . '.' . $entityTypeId . '.' . $entityBundle . '.';

    $configKeys = $this->configFactory->listAll($prefix);

    $displayKeys = [];
    foreach ($configKeys as $configKey) {
      $displayKeys[] = str_replace($configPrefix . '.', '', $configKey);
    }

    return $this->entityTypeManager->getStorage('entity_view_display')->loadMultiple($displayKeys);
  }

  /**
   * Build markup required to render the entity in the desired view mode.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity class.
   * @param string $viewMode
   *   Entity view mode.
   *
   * @return array
   *   Render array for the given entity.
   *
   * @todo Handle block requirements better.
   */
  public function buildMarkup(ContentEntityInterface $entity, string $viewMode): array {
    $entityTypeId = $entity->getEntityType()->get('id');
    $viewBuilder = $this->entityTypeManager->getViewBuilder($entityTypeId);

    if ('block_content' == $entityTypeId) {
      $blockInstance = $this->blockManager->createInstance(
        'block_content:' . $entity->uuid(),
        [
          'view_mode' => $viewMode,
        ]
      );

      return [
        // @todo Should be in BlockBase, wait https://www.drupal.org/node/2931040.
        '#theme' => 'block',
        '#configuration' => $blockInstance->getConfiguration(),
        '#plugin_id' => $blockInstance->getPluginId(),
        '#base_plugin_id' => $blockInstance->getBaseId(),
        '#derivative_plugin_id' => $blockInstance->getDerivativeId(),
        'content' => $blockInstance->build(),
      ];
    }

    return $viewBuilder->view($entity, $viewMode);
  }

}
