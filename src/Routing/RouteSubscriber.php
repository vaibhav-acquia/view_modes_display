<?php

namespace Drupal\view_modes_display\Routing;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for View Mode Display routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * EntityDisplayRepository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;


  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entityDisplayRepository) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entityDisplayRepository;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($viewModes = $this->entityDisplayRepository->getViewModes($entity_type_id)) {
        if ($route = $this->getPreviewList($entity_type)) {
          $collection->add("entity.$entity_type_id.vmd_preview_list", $route);
        }
        if ($route = $this->getPreviewRenderRoute($entity_type)) {
          $collection->add("entity.$entity_type_id.vmd_preview_render" , $route);
        }
      }
    }
  }

  /**
   * Gets the entity load route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getPreviewList(EntityTypeInterface $entity_type) {
    if ($link_template = $entity_type->getLinkTemplate('vmd-preview-list')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($link_template);
      $route
        ->addDefaults([
          '_controller' => '\Drupal\view_modes_display\Controller\PreviewController::previewList',
          '_title' => 'Available View Mode Previews',
          'entity_type' => $entity_type_id,
          'view_mode' => 'all',
        ])
        ->addRequirements([
          '_permission' => 'preview view modes',
        ])
        ->setOption('_admin_route', TRUE)
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);

      return $route;
    }
  }

  /**
   * Gets the entity render route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getPreviewRenderRoute(EntityTypeInterface $entity_type) {
    if ($link_template = $entity_type->getLinkTemplate('vmd-preview-list')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($link_template);
      $route
        ->addDefaults([
          '_controller' => '\Drupal\view_modes_display\Controller\PreviewController::previewEntity',
          '_title' => 'View Mode Preview',
          'entity_type' => $entity_type_id,
        ])
        ->addRequirements([
          '_permission' => 'preview view modes',

        ])
        // Not an admin route - which should allow the frontend theme.
        //->setOption('_admin_route', TRUE)
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);

      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = array('onAlterRoutes', -100);
    return $events;
  }

}
