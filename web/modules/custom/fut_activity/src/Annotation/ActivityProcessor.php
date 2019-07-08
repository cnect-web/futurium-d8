<?php

namespace Drupal\fut_activity\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Activity processor item annotation object.
 *
 * @see \Drupal\fut_activity\Plugin\ActivityProcessorManager
 * @see plugin_api
 *
 * @Annotation
 */
class ActivityProcessor extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
