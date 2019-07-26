<?php

namespace Drupal\fut_activity;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines FutActivityViewsOperations class to react to views related operaions.
 */
class FutActivityViewsOperations implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new FutActivityViewsOperations instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Discribes fut_activity data table to Views module.
   *
   * @return array
   *   An associative array describing the data structure.
   */
  public function getViewsData() {
    $data['fut_activity']['table']['group'] = $this->t('Fut Activity');
    $data['fut_activity']['table']['base'] = [
      'field' => 'activity_id',
      'title' => $this->t('Fut Activity'),
      'help' => $this->t('Fut Activity table keeps track of entity activity.'),
      'weight' => -10,
    ];

    $data['fut_activity']['table']['join'] = $this->getImplicitRelations();

    $data['fut_activity'] = $data['fut_activity'] + $this->getRelationsFields();

    $data['fut_activity']['activity_id'] = [
      'title' => $this->t('Activity ID'),
      'help' => $this->t('Activity Record ID.'),
      'field' => [
        'id' => 'numeric',
        'click sortable' => TRUE,
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['fut_activity']['entity_type'] = [
      'title' => $this->t('Entity Type'),
      'help' => $this->t('Tracked entity type.'),
      'field' => [
        'id' => 'standard',
        'click sortable' => TRUE,
      ],
      'filter' => [
        'id' => 'string',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['fut_activity']['bundle'] = [
      'title' => $this->t('Entity Bundle'),
      'help' => $this->t('Tracked entity bundle.'),
      'field' => [
        'id' => 'standard',
        'click sortable' => TRUE,
      ],
      'filter' => [
        'id' => 'string',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['fut_activity']['entity_id'] = [
      'title' => $this->t('Entity ID'),
      'help' => $this->t('Tracked Entity ID.'),
      'field' => [
        'id' => 'numeric',
        'click sortable' => TRUE,
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['fut_activity']['activity'] = [
      'title' => $this->t('Activity'),
      'help' => $this->t('Entity activity value.'),
      'field' => [
        'id' => 'numeric',
        'click sortable' => TRUE,
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['fut_activity']['created'] = [
      'title' => $this->t('Created'),
      'help' => $this->t('when record was created'),
      'field' => [
        'id' => 'date',
        'click sortable' => TRUE,
      ],
      'filter' => [
        'id' => 'date',
      ],
      'argument' => [
        'id' => 'date',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['fut_activity']['changed'] = [
      'title' => $this->t('Last changed'),
      'help' => $this->t('Last time record was changed'),
      'field' => [
        'id' => 'date',
        'click sortable' => TRUE,
      ],
      'filter' => [
        'id' => 'date',
      ],
      'argument' => [
        'id' => 'date',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    // Custom field to show tracked entity.
    $data['fut_activity']['tracked_entity'] = [
      'title' => $this->t('Tracked entity'),
      'help' => $this->t('Display tracked entity title and link'),
      'field' => [
        'id' => 'tracked_entity',
      ],
    ];

    return $data;
  }

  /**
   * Get all existing EntityActivityTrackers.
   *
   * @return \Drupal\fut_activity\Entity\EntityActivityTrackerInterface[]
   *   Array containing all trackers config entities.
   */
  protected function getTrackers() {
    return $this->entityTypeManager->getStorage('entity_activity_tracker')->loadMultiple();
  }

  /**
   * Get implicit relations to Trackers entity types.
   *
   * @return array
   *   Associative array describing relation when,
   *   tracked entity type is the base table.
   */
  protected function getImplicitRelations() {
    $id_names = [];
    foreach ($this->getTrackers() as $tracker) {
      $id_names[$tracker->getTargetEntityType()] = $this->entityTypeManager->getStorage($tracker->getTargetEntityType())->getEntityType()->getKey('id');
    }
    $joins = [];

    foreach ($id_names as $entity_type => $id_name) {
      $joins[$entity_type . '_field_data'] = [
        'left_field' => $id_name,
        'field' => 'entity_id',
      ];
    }
    return $joins;
  }

  /**
   * Get relations to Trackers.
   *
   * @return array
   *   Associative array describing relation fields.
   */
  protected function getRelationsFields() {
    $data = [];
    if (count($this->getTrackers())) {
      foreach ($this->getTrackers() as $tracker) {
        $entity_type = $this->entityTypeManager->getStorage($tracker->getTargetEntityType())->getEntityType();

        $data[$entity_type->getKey('id')] = [
          'title' => $this->t('Entity ID'),
          'help' => $this->t('Tracked Entity ID.'),
          'relationship' => [
            'id' => 'standard',
            'title' => $this->t('@entity_type', ['@entity_type' => $entity_type->getLabel()]),
            'help' => $this->t('Relate activity to the @entity_type  that is being tracked.', ['@entity_type' => $entity_type->getLabel()]),
            'handler' => 'views_handler_relationship',
            'base' => $entity_type->getDataTable(),
            'base field' => $entity_type->getKey('id'),
            'field' => 'entity_id',
            'label' => $this->t('Entity: @entity_type', ['@entity_type' => $entity_type->getLabel()]),
          ],
        ];
      }
    }
    return $data;
  }

}
