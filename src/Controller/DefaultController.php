<?php

namespace Drupal\view_modes_display\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\block_content\BlockContentInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DefaultController.
 *
 * @package Drupal\view_modes_display\Controller
 */
class DefaultController extends ControllerBase {

  /**
   * ConfigFactory.
   *
   * @var Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * EntityTypeManager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * EntityDisplayRepository.
   *
   * @var Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * DefaultController constructor.
   *
   * @param Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config Factory.
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager.
   * @param Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository
   *   Entity Display Repository.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    EntityTypeManagerInterface $entityTypeManager,
    EntityDisplayRepositoryInterface $entityDisplayRepository
  ) {
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityDisplayRepository = $entityDisplayRepository;
  }

  /**
   * {@inheritdoc}
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   *
   * @return Drupal\Core\Controller\ControllerBase
   *   ControllerBase with injected services.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository')
    );
  }

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

    $viewModes = $this->entityDisplayRepository->getViewModes($entityTypeId);

    $configPrefix = 'core.entity_view_display';

    $configKeys = $this->configFactory->listAll($configPrefix . '.' . $entityTypeId . '.' . $entity->bundle() . '.');

    $displayKeys = [];
    foreach ($configKeys as $configKey) {
      $displayKeys[] = str_replace($configPrefix . '.', '', $configKey);
    }

    $displays = $this->entityTypeManager->getStorage('entity_view_display')->loadMultiple($displayKeys);

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

    $viewBuilder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());

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
