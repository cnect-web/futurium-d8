<?php

namespace Drupal\ngf_core\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Url;
use Drupal\Core\Link;

class CreateContent extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlObject($title_attribute = TRUE) {

    $options = [
      'attributes' => [
        'data-dialog-options' => json_encode(['width' => 400, 'height' => 300]),
        'data-dialog-type' => "modal",
        'class' => [
          "use-ajax",
          "button",
          "create-new",
        ],
        'title' => "Click here to add content",
        'data-drupal-link-system-path' => "create"
      ]
    ];

    $context = \Drupal::service('context.repository');
    $groupContext = $context->getRuntimeContexts(['@group.group_route_context:group']);
    if ($group = $groupContext['@group.group_route_context:group']->getContextData()->getValue()) {
      return Url::fromRoute('ngf_core.create_content', ['group' => $group->id()], $options);
    }
    else {
      return Url::fromRoute('ngf_core.create_content', ['group' => 'none'], $options);
    }
  }

}
