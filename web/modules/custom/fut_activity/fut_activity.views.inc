<?php

/**
 * @file
 * Provide views data for fut_activity module.
 */

use Drupal\fut_activity\FutActivityViewsOperations;

/**
 * @file
 * Provide views data for fut_activity module.
 */

/**
 * Implements hook_views_data().
 */
function fut_activity_views_data() {
  return \Drupal::service('class_resolver')
    ->getInstanceFromDefinition(FutActivityViewsOperations::class)
    ->getViewsData();
}
