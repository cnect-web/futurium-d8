<?php

namespace Drupal\fut_activity\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\views\Plugin\views\field\Url;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\SubformState;

/**
 * Class EntityActivityTrackerForm.
 */
class EntityActivityTrackerForm extends EntityForm {


  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Plugin manager for constraints.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('fut_activity.plugin.manager.activity_processor'),
      $container->get('form_builder')
    );
  }

  /**
   * Overridden constructor to load the plugin.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   Plugin manager for activity processors.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
   */
  public function __construct(PluginManagerInterface $manager, FormBuilderInterface $formBuilder) {
    $this->manager = $manager;
    $this->formBuilder = $formBuilder;
  }


  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\fut_activity\Entity\EntityActivityTrackerInterface $entity_activity_tracker */
    $entity_activity_tracker = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity_activity_tracker->label(),
      '#description' => $this->t("Label for the Entity activity tracker."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity_activity_tracker->id(),
      '#machine_name' => [
        'exists' => '\Drupal\fut_activity\Entity\EntityActivityTracker::load',
      ],
      '#disabled' => !$entity_activity_tracker->isNew(),
    ];

    // TODO: Clean this up (use dependency injection and put this code on separte method)
    $entity_type_options = [];
    foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->entityClassImplements(ContentEntityInterface::class)) {
        $entity_type_options[$entity_type_id] = $entity_type->get('label');
      }
    }

    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Type'),
      '#description' => $this->t('Select entity type for this config.'),
      '#default_value' => $entity_activity_tracker->getTargetEntityType(),
      '#options' => $entity_type_options,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'updateBundlesElement'],
        'event' => 'change',
        'wrapper' => 'entity-bundle-wrapper',
      ],
    ];



    $form['entity_bundle_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'entity-bundle-wrapper'],
    ];

    // I need to set the default value when editing already created tracker

    $entity_type = $form_state->getValue('entity_type');
    if (!empty($entity_type)) {
      $form['entity_bundle_wrapper']['entity_bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Entity Bundle'),
        '#options' => $this->getBundleOptions($entity_type),
      ];
    }


    // plugin part

    $form['add_processor_title'] = [
      '#markup' => '<h2>' . $this->t('Config Processors') . '</h2>',
    ];

    $form['activity_processors'] = [
      '#type' => 'details',
      '#title' => $this->t('Processors'),
      '#tree' => TRUE,
      '#open' => TRUE
    ];

    $processor_config = $entity_activity_tracker->get('activity_processors');
    foreach ($this->manager->getDefinitions() as $plugin_id => $definition) {

      $form['activity_processors'][$plugin_id]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $definition['label'],
        '#title_display' => 'after',
        '#default_value' => !empty($processor_config[$plugin_id]['enabled']),
      ];
      $form['activity_processors'][$plugin_id]['settings'] = [];
      $subform_state = SubformState::createForSubform($form['activity_processors'][$plugin_id]['settings'], $form, $form_state);

      $processor = $entity_activity_tracker->getProcessorPlugin($plugin_id);

      if ($settings = $processor->buildConfigurationForm($form['activity_processors'][$plugin_id]['settings'], $subform_state)) {
        $form['activity_processors'][$plugin_id]['settings'] = $settings + [
          '#type' => 'fieldset',
          '#title' => $definition['label'],
          '#states' => [
            'visible' => [
                ':input[name="activity_processors[' . $plugin_id . '][enabled]"]' => ['checked' => TRUE],
            ]
          ]
        ];
      }
    }



    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // $form_state->setRebuild(TRUE);

    /** @var \Drupal\fut_activity\Entity\EntityActivityTrackerInterface $entity_activity_tracker */
    $entity_activity_tracker = $this->entity;



    foreach ($this->manager->getDefinitions() as $plugin_id => $definition) {
      $processor_plugin = $entity_activity_tracker->getProcessorPlugin($plugin_id);

      // Check if is enabled
      if ($form_state->getValue(['activity_processors', $plugin_id, 'enabled'])) {
        $processor_plugin->setConfiguration(['enabled' => TRUE]);
        if (isset($form['activity_processors'][$plugin_id]['settings'])) {
          $subform_state = SubformState::createForSubform($form['activity_processors'][$plugin_id]['settings'], $form, $form_state);
          $processor_plugin->submitConfigurationForm($form['activity_processors'][$plugin_id]['settings'], $subform_state);
        }
      }
      else {
        // The plugin is not enabled, reset to default configuration.
        $processor_plugin->setConfiguration([]);
      }
    }







    $status = $entity_activity_tracker->save();

    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()->addMessage($this->t('Created the %label Entity activity tracker.', [
          '%label' => $entity_activity_tracker->label(),
        ]));
        break;

      default:
        \Drupal::messenger()->addMessage($this->t('Saved the %label Entity activity tracker.', [
          '%label' => $entity_activity_tracker->label(),
        ]));
    }
    $form_state->setRedirectUrl($entity_activity_tracker->toUrl('collection'));
  }

  /**
   * updateBundlesElement
   *
   * @param  mixed $form
   * @param  mixed $form_state
   *
   * @return void
   */
  public function updateBundlesElement(array $form, FormStateInterface $form_state) {
    return $form['entity_bundle_wrapper'];
  }

  /**
   * getBundleOptions
   *
   * @param  mixed $entity_type_value
   *
   * @return array
   */
  public function getBundleOptions($entity_type_value) {
    $bundles = \Drupal::entityTypeManager()->getStorage($entity_type_value.'_type')->loadMultiple();

    $bundles_options = [];
    foreach ($bundles as $bundle_id => $bundle_type) {
      $bundles_options[$bundle_id] = $bundle_type->get('name') ?? $bundle_id;
    }

    return $bundles_options;
  }

}
