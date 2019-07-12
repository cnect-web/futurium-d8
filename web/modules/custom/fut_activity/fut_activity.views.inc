<?php

/**
 * @file
 * Provide views data for fut_activity module.
 */

/**
 * Implements hook_views_data().
 */
function fut_activity_views_data() {
  $data['fut_activity']['table']['group'] = t('Fut Activity');
  $data['fut_activity']['table']['base'] = [
    'field' => 'activity_id',
    'title' => t('Fut Activity'),
    'help' => t('Fut Activity table keeps track of entity activity.'),
    'weight' => -10,
  ];

  $trackers = Drupal::entityTypeManager()->getStorage('entity_activity_tracker')->loadMultiple();

  $id_names = [];
  foreach ($trackers as $tracker) {
    $id_names[$tracker->getTargetEntityType()] = Drupal::entityTypeManager()->getStorage($tracker->getTargetEntityType())->getEntityType()->getKey('id');
  }
  $joins = [];

  foreach ($id_names as $entity_type => $id_name) {
    $joins[$entity_type.'_field_data'] = [
      'left_field' => $id_name,
      'field' => 'entity_id',
    ];
  }

  $data['fut_activity']['table']['join'] = $joins;

  $data['fut_activity']['activity'] = [
    'title' => t('Activity'),
    'help' => t('Entity activity value.'),
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
    'title' => t('Created'),
    'help' => t('when record was created'),
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
    'title' => t('last changed'),
    'help' => t('Last time record was changed'),
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

  return $data;
}
