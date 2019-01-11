<?php

namespace Drupal\ngf_core\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\group\Entity\Group;
use Drupal\views\Views;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\group\Cache\Context\GroupCacheContext;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Create content controller.
 */
class CreateContentController extends ControllerBase {

  /**
   * Access check to route.
   */
  public function access($group = 'none') {

    $links = ($group == 'none')
      ? array_filter($this->getGlobalScopeLinks(), [$this, 'accessFilter'])
      : array_filter($this->getGroupScopeLinks($group), [$this, 'accessFilter']);

    if (empty($links)) {
      return AccessResult::forbidden();
    }
    return AccessResult::allowed();

  }

  /**
   * Access check helper.
   */
  private function accessFilter($var) {
    return !empty($var['#links']);
  }

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
    return ['url.path'];
  }

  /**
   * Returns a render-able array for a test page.
   */
  public function createContent($group = 'none') {

    return ($group == 'none')
      ? $this->getGlobalScopeLinks()
      : $this->getGroupScopeLinks($group);

  }

  /**
   * Callback for setting the modal title.
   */
  public function createContentTitle($group = 'none') {
    if ($group == 'none') {
      return $this->t('Create new ...');
    }
    else {
      $group = Group::load($group);
      return $this->t("Add to '@group'", ['@group' => $group->label()]);
    }
  }

  /**
   * Returns links to add content globally.
   */
  private function getGlobalScopeLinks() {

    $user = \Drupal::currentUser();
    $content_links = [];
    $link_attributes = [
      'class' => [
        'btn',
        'btn--blue',
        'btn--large'
      ]
    ];

    // Add content links.
    $contentTypes = \Drupal::service('entity.manager')
      ->getStorage('node_type')
      ->loadMultiple();

    foreach ($contentTypes as $contentType) {
      $content_type_id = $contentType->id();
      if ($user->hasPermission("create $content_type_id content")) {
        $url = new Url('node.add', ['node_type' => $content_type_id]);
        $content_links[] = [
          'title' => t("{$contentType->label()}"),
          'type' => 'link',
          'url' => $url,
          'attributes' => $link_attributes
        ];
      }
    }

    $render[] = [
      '#theme' => 'links',
      '#links' => $content_links,
      '#attributes' => [
        'class' => [
          'add-content-links',
        ]
      ]
    ];

    // Add Group links.
    $group_links = [];
    $groupTypes = \Drupal::service('entity.manager')
      ->getStorage('group_type')
      ->loadMultiple();

    foreach ($groupTypes as $groupType) {
      $group_type_id = $groupType->id();
      if ($group_type_id !== 'ngf_session') {
        if ($user->hasPermission("create $group_type_id group")) {
          $url = new Url('entity.group.add_form', ['group_type' => $group_type_id]);
          $group_links[] = [
            'title' => t("{$groupType->label()}"),
            'type' => 'link',
            'url' => $url,
            'attributes' => $link_attributes
          ];
        }
      }
    }

    $render[] = [
      '#theme' => 'links',
      '#links' => $group_links,
      '#attributes' => [
        'class' => [
          'add-content-links',
        ]
      ]
    ];

    return $render;
  }

  /**
   * Returns links to add content for a group.
   */
  private function getGroupScopeLinks($group_id) {

    $group = Group::load($group_id);

    $render = [];
    $links = [];
    $link_attributes = [
      'class' => [
        'btn',
        'btn--blue',
        'btn--large'
      ]
    ];

    $plugins = $group->getGroupType()->getInstalledContentPlugins();

    // Retrieve the operations from the installed content plugins.
    foreach ($plugins as $plugin) {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      $plugin_type = $plugin->getPluginDefinition()['id'];
      if ($plugin_type != 'group_membership') {
        if (!isset($links[$plugin_type])) {
          $links[$plugin_type] = [];
        }
        $links[$plugin_type] += $plugin->getGroupOperations($group);
      }
    }

    if ($links) {

      foreach ($links as &$plugin_links) {
        // Allow modules to alter the collection of gathered links.
        \Drupal::moduleHandler()->alter('group_operations', $plugin_links, $group);

        // Sort the operations by weight.
        uasort($plugin_links, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
      }

      $links = array_reverse($links);
      foreach ($links as $plugin_type => $plugin_links) {

        foreach ($plugin_links as $key => &$value) {
          $value['attributes'] = $link_attributes;
        }

        $render[$plugin_type] = [
          '#theme' => 'links',
          '#links' => $plugin_links,
          '#attributes' => [
            'class' => [
              'add-content-links',
            ]
          ]
        ];
      }
    }
    return $render;
  }
}
