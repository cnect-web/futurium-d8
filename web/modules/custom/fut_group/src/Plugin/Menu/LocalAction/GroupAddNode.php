<?php

namespace Drupal\fut_group\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Set route parameters for "fut_group.group_node_add_post" action link.
 */
class GroupAddNode extends LocalActionDefault {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this
      ->t('Add @type_name', [
        '@type_name' => $this->pluginDefinition['group_content_enabler_plugin_label'],
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {

    $group = $route_match->getParameter('group');
    $route_parameters = [
      'plugin_id' => $this->pluginDefinition['group_content_enabler_plugin_id'],
      'group' => $group->id(),
    ];

    return $route_parameters;

  }

}
