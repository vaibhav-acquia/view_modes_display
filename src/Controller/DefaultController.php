<?php

namespace Drupal\view_modes_display\Controller;

use Drupal\Core\Controller\ControllerBase;
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
class DefaultController extends ControllerBase {

  /**
   * ConfigFactory.
   *
   * @var Drupal\view_modes_display\Service\PreviewFactory
   */
  protected $previewFactory;

  /**
   * DefaultController constructor.
   *
   * @param Drupal\view_modes_display\Service\PreviewFactory $previewFactory
   *   Preview Factory.
   */
  public function __construct(
    PreviewFactory $previewFactory
  ) {
    $this->previewFactory = $previewFactory;
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
      $container->get('view_modes_display.preview_factory')
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
    return $this->previewFactory->preview($node);
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
    return $this->previewFactory->preview($block_content);
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
    return $this->previewFactory->preview($user);
  }

  /**
   * Returns media entity.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media.
   *
   * @return array
   *   Preview media entity.
   */
  public function previewMedia(MediaInterface $media) {
    return $this->previewFactory->preview($media);
  }

}
