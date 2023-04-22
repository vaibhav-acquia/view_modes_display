<?php

namespace Drupal\view_modes_display\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Drupal\block_content\BlockContentInterface;
use Drupal\user\UserInterface;
use Drupal\view_modes_display\Service\PreviewFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DefaultController.
 *
 * @package Drupal\view_modes_display\Controller
 */
class PreviewController extends ControllerBase {

  /**
   * ConfigFactory.
   *
   * @var \Drupal\view_modes_display\Service\PreviewFactory
   */
  protected PreviewFactory $previewFactory;

  /**
   * EntityDisplayRepository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected EntityDisplayRepositoryInterface $entityDisplayRepository;

  /**
   * DefaultController constructor.
   *
   * @param \Drupal\view_modes_display\Service\PreviewFactory $previewFactory
   *   Preview Factory.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository
   *   Entity Display Repository.
   */
  public function __construct(
    PreviewFactory $previewFactory,
    EntityDisplayRepositoryInterface $entityDisplayRepository
  ) {
    $this->previewFactory = $previewFactory;
    $this->entityDisplayRepository = $entityDisplayRepository;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   *
   * @return \Drupal\Core\Controller\ControllerBase
   *   ControllerBase with injected services.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('view_modes_display.preview_factory'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * Returns preview for entity - dedicated view mode or all of them.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Route match.
   *
   * @param string $entity_type
   *
   * @return array
   *   Preview render array.
   */
  public function previewEntity(RouteMatchInterface $route_match, string $entity_type): array {
    $entity = $route_match->getParameter($entity_type);
    $view_mode = $route_match->getParameter('view_mode');
    $view_modes = $this->entityDisplayRepository->getViewModes($entity->getEntityTypeId());
    $renderArray = [];
    // Special view mode placeholder to fetch all. Default in the route
    // definition.
    if ($view_mode == 'all') {
      return $this->previewFactory->preview($entity);
    }

    $markup = $this->previewFactory->buildMarkup($entity, $view_mode);
    $renderArray[] = [
      '#prefix' => '<div class="view-mode-list-item view-mode-list-item-' . $view_mode . '"><div class="view-mode-list-item-label">' . ($view_modes[$view_mode]['label'] ?? $this->t('Default')) . '</div><div class="view-mode-list-item-content">',
      '#markup' => \Drupal::service('renderer')->render($markup),
      '#suffix' => '</div></div>',
    ];

    return $renderArray;
  }

  /**
   * Provides a link list with all available - dedicated - view mode previews.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @param string $entity_type
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function previewList(RouteMatchInterface $route_match, string $entity_type): array {
    $content = [];
    $links = [];
    $view_modes = $this->entityDisplayRepository->getViewModes($entity_type);
    /** @var EntityInterface $entity */
    $entity = $route_match->getParameter($entity_type);
    $entityDisplays = $this->previewFactory->getEntityDisplays($entity_type, $entity->bundle());
    foreach ($this->previewFactory->getEnabledDisplayModes($entityDisplays) as $display) {
      $label = ucfirst($display);
      if ((isset($view_modes[$display]['label']))) {
        $label = $view_modes[$display]['label'];
      }
      $url = $entity->toUrl('vmd-preview-list');
      $url = $url->setRouteParameter('view_mode', $display);
      $links[] = [
        '#type' => 'link',
        '#url' => $url,
        '#title' => t('Preview %label', ['%label' => $label]),
      ];
    }
    $content['preview_links'] = [
      '#theme' => 'item_list',
      '#items' => $links,
      '#title' => t('Available ViewMode Previews:'),
    ];
    return $content;
  }

}
