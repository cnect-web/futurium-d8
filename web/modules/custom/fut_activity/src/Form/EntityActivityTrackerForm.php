<?php

namespace Drupal\fut_activity\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\SubformState;
use Drupal\fut_activity\Entity\EntityActivityTrackerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\fut_activity\Event\TrackerCreateEvent;
use Drupal\Core\Messenger\MessengerInterface;

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
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('fut_activity.plugin.manager.activity_processor'),
      $container->get('form_builder'),
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('messenger')
    );
  }

  /**
   * Overridden constructor to load the plugin.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   Plugin manager for activity processors.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(PluginManagerInterface $manager, FormBuilderInterface $formBuilder, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, MessengerInterface $messenger) {
    $this->manager = $manager;
    $this->formBuilder = $formBuilder;
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->messenger = $messenger;
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

    $entity_type_options = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->entityClassImplements(ContentEntityInterface::class) && in_array($entity_type_id, EntityActivityTrackerInterface::ALLOWED_ENTITY_TYPES)) {
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

    // I need to set the default value when editing already created tracker.
    $entity_type = $entity_activity_tracker->getTargetEntityType();
    if (!empty($entity_type)) {
      $form['entity_bundle_wrapper']['entity_bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Entity Bundle'),
        '#options' => $this->getBundleOptions($entity_type),
      ];
    }

    // Plugin part.
    $form['add_processor_title'] = [
      '#markup' => '<h2>' . $this->t('Config Processors') . '</h2>',
    ];

    $form['activity_processors'] = [
      '#type' => 'details',
      '#title' => $this->t('Processors'),
      '#tree' => TRUE,
      '#open' => TRUE,
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
            ],
          ],
        ];
      }
    }

    $form['activity_records_title'] = [
      '#markup' => '<h2>' . $this->t('@entity_activity_tracker Activity Records', ['@entity_activity_tracker' => $entity_activity_tracker->label()]) . '</h2>',
    ];

    $form['activity_records_list'] = [
      '#type' => 'view',
      '#name' => 'fut_activity_list',
      '#arguments' => [
        $entity_activity_tracker->getTargetEntityType(),
        $entity_activity_tracker->getTargetEntityBundle(),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $properties = [
      'entity_type' => $form_state->getValue('entity_type'),
      'entity_bundle' => $bundle_value = $form_state->getValue('entity_bundle'),
    ];
    $existing = $this->entityTypeManager->getStorage('entity_activity_tracker')->loadByProperties($properties);
    if (count($existing) >= 1 && !array_key_exists($this->entity->id(), $existing)) {
      // There is a Tracker for this entiy/bundle so we set a form error.
      $form_state->setErrorByName('entity_bundle', $this->t('There is already a Tracker for this bundle: @bundle', ['@bundle' => $bundle_value]));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\fut_activity\Entity\EntityActivityTrackerInterface $entity_activity_tracker */
    $entity_activity_tracker = $this->entity;

    foreach ($this->manager->getDefinitions() as $plugin_id => $definition) {
      $processor_plugin = $entity_activity_tracker->getProcessorPlugin($plugin_id);

      // Check if is enabled.
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
        $this->messenger->addMessage($this->t('Created the %label Entity activity tracker.', [
          '%label' => $entity_activity_tracker->label(),
        ]));
        // Dispatch the TrackerCreateEvent.
        $event = new TrackerCreateEvent($entity_activity_tracker);
        $this->eventDispatcher->dispatch(TrackerCreateEvent::TRACKER_CREATE, $event);
        break;

      default:
        $this->messenger->addMessage($this->t('Saved the %label Entity activity tracker.', [
          '%label' => $entity_activity_tracker->label(),
        ]));
    }
    $form_state->setRedirectUrl($entity_activity_tracker->toUrl('collection'));
  }

  /**
   * Ajax callback to load bundle form element.
   *
   * @param array $form
   *   Form definition of parent form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   State of the form.
   *
   * @return array
   *   The form element.
   */
  public function updateBundlesElement(array $form, FormStateInterface $form_state) {
    return $form['entity_bundle_wrapper'];
  }

  /**
   * Get bundle options for selected entity_type.
   *
   * @param string $entity_type_value
   *   Selected entity_type.
   *
   * @return array
   *   List of bundles.
   */
  protected function getBundleOptions(string $entity_type_value) {
    $bundles = $this->entityTypeManager->getStorage($entity_type_value . '_type')->loadMultiple();

    $bundles_options = [];
    foreach ($bundles as $bundle_id => $bundle_type) {
      $bundles_options[$bundle_id] = $bundle_type->get('name') ?? $bundle_id;
    }

    return $bundles_options;
  }

}
