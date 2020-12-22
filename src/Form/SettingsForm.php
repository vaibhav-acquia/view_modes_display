<?php

namespace Drupal\view_modes_display\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm for configuring View Modes Display module.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Drupal\view_modes_display\Service\PreviewFactory definition.
   *
   * @var \Drupal\view_modes_display\Service\PreviewFactory
   */
  protected $previewFactory;

  /**
   * The entity type manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routerBuilder;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->previewFactory = $container->get('view_modes_display.preview_factory');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityTypeBundleInfo = $container->get('entity_type.bundle.info');
    $instance->routerBuilder = $container->get('router.builder');
    $instance->entityDisplayRepository = $container->get('entity_display.repository');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'view_modes_display.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vmd_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('view_modes_display.settings');
    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings for View Modes Display'),
      '#open' => TRUE,
    ];
    $form['general']['show_preview_directly'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show preview directly'),
      '#description' => $this->t('Instead of showing a list of possible view modes, show the previews directly.'),
      '#default_value' => $config->get('show_preview_directly'),
    ];
    $form['disabled_view_modes'] = [
      '#type' => 'details',
      '#title' => $this->t('Excluded view modes'),
      '#description' => $this->t("Select here which view modes you DO NOT want to preview. Please note that you don't need to pay attention to those entity types you're not really previewing at all."),
      '#open' => TRUE,
    ];

    foreach ($this->entityTypeManager->getDefinitions() as $entityTypeId => $entityType) {
      if ($entityType->getGroup() !== 'content') {
        continue;
      }
      $bundleInfo = $this->entityTypeBundleInfo->getBundleInfo($entityTypeId);
      $values = $config->get('disabled_modes');
      $allViewModes = $this->entityDisplayRepository->getAllViewModes();
      foreach ($bundleInfo as $bundleId => $bundle) {
        $entityDisplays = $this->previewFactory->getEntityDisplays($entityTypeId, $bundleId);
        $enabledDisplayModes = $this->previewFactory->getEnabledDisplayModes($entityDisplays, FALSE);
        $options = [
          'full' => 'Full content',
        ];
        foreach ($enabledDisplayModes as $mode) {
          if ($mode === 'default' || $mode === 'full') {
            continue;
          }
          $options[$mode] = $allViewModes[$entityTypeId][$mode]['label'] ? $allViewModes[$entityTypeId][$mode]['label'] . ' [' . $mode . ']' : $mode;
        }
        $form['disabled_view_modes']['disabled' . '-' . $entityTypeId . '-' . $bundleId] = [
          '#type' => 'checkboxes',
          '#title' => $entityType->getLabel() . ': ' . $bundle['label'],
          '#options' => $options,
          '#default_value' => $values[$entityTypeId][$bundleId] ?? [],
        ];
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('view_modes_display.settings');
    $cacheClear = FALSE;
    $values = $form_state->getValues();

    if ($config->get('show_preview_directly') !== $values['show_preview_directly']) {
      $config->set('show_preview_directly', $values['show_preview_directly']);
      $cacheClear = TRUE;
    }

    $disabledValues = [];
    foreach ($values as $key => $modes) {
      $parts = explode('-', $key);
      if ($parts[0] !== 'disabled') {
        continue;
      }
      $modes = array_filter($modes);
      if (!empty(array_values($modes))) {
        $disabledValues[$parts[1]][$parts[2]] = $modes;
      }
    }

    $config->set('disabled_modes', $disabledValues);
    $config->save();

    if ($cacheClear) {
      $this->routerBuilder->rebuild();
      $this->messenger()->addMessage($this->t('Routing cache was rebuilt due to the configuration change.'));
    }
  }

}
