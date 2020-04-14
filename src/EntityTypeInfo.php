<?php

namespace Drupal\view_modes_display;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manipulates entity type information.
 *
 * This class contains primarily bridged hooks for compile-time or
 * cache-clear-time hooks. Runtime hooks should be placed in EntityOperations.
 */
class EntityTypeInfo implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * EntityDisplayRepository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * EntityTypeInfo constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   */
  public function __construct(AccountInterface $current_user, EntityDisplayRepositoryInterface $entityDisplayRepository) {
    $this->currentUser = $current_user;
    $this->entityDisplayRepository = $entityDisplayRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * Adds preview links to appropriate entity types.
   *
   * This is an alter hook bridge.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   The master entity type list to alter.
   *
   * @see hook_entity_type_alter()
   */
  public function entityTypeAlter(array &$entity_types) {
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if ($viewModes = $this->entityDisplayRepository->getViewModes($entity_type_id)) {
        $entity_type->setLinkTemplate('vmd-preview-list', "/$entity_type_id/{{$entity_type_id}}/view-mode/preview/list");
        $entity_type->setLinkTemplate('vmd-preview-list', "/$entity_type_id/{{$entity_type_id}}/view-mode/preview/{view_mode}");
      }
    }
  }

  /**
   * Adds preview operations on entity that supports it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which to define an operation.
   *
   * @return array
   *   An array of operation definitions.
   *
   * @see hook_entity_operation()
   */
  public function entityOperation(EntityInterface $entity) {
    $operations = [];
    if ($this->currentUser->hasPermission('preview view modes')) {
      if ($entity->hasLinkTemplate('vmd-preview-list')) {
        $operations['view-mode-display'] = [
          'title' => $this->t('Preview'),
          'weight' => 100,
          'url' => $entity->toUrl('vmd-preview-list'),
        ];
      }
    }
    return $operations;
  }

}
