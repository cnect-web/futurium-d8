<?php

namespace Drupal\ngf_group\Entity\Decorator;

use Drupal\group\Entity\Group;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\views\Views;

class NGFGroup {

  protected $group;

  /**
   * Constructor.
   */
  public function __construct(Group $group) {
    $this->group = $group;
  }

  /**
   * Magic method, propagates the parent class api to the current class.
   */
  public function __call($method, $args) {
    return call_user_func_array(array($this->group, $method), $args);
  }

  /**
   * Get group visibility setting.
   */
  public function getGroupVisibility() {
    $group = $this->group;
    $groupVisibility = NGF_GROUP_PUBLIC;
    if ($group->hasField('field_ngf_group_visibility')) {
      $groupVisibility = $group->get('field_ngf_group_visibility')->getString();
    }
    return $groupVisibility;
  }

  /**
   * Return true if the group is public.
   */
  public function isPublic() {
    return $this->getGroupVisibility() == NGF_GROUP_PUBLIC;
  }

  /**
   * Return true if the group is private.
   */
  public function isPrivate() {
    return $this->getGroupVisibility() == NGF_GROUP_PRIVATE;
  }

  /**
   * Return true if the group is secret.
   */
  public function isSecret() {
    return $this->getGroupVisibility() == NGF_GROUP_SECRET;
  }

  /**
   * Return info about group members.
   */
  public function getMembersInfo() {
    $group = $this->group;

    $members_count_raw    = count($group->getMembers());
    $members_count_string = \Drupal::translation()->formatPlural($members_count_raw, '1 member', '@count members', array('@count' => $members_count_raw));
    $members_page_url     = Url::fromRoute('ngf_group.page.members', ['group' => $group->id()]);

    $members['count'] = $members_count_raw;
    $members['text']  = $members_count_string;
    $members['url']   = $members_page_url;

    return $members;
  }

  /**
   * Return a link to group members page.
   */
  public function getMembersLink() {
    $members = $this->getMembersInfo();
    return Link::fromTextAndUrl($members['text'], $members['url'])->toRenderable();
  }

  /**
   * Return info about group followers.
   */
  public function getFollowersInfo() {
    $group = $this->group;

    $flaggings_count = \Drupal::service('flag.count')->getEntityFlagCounts($group);

    $followers_count_raw    = isset($flaggings_count['ngf_follow_group']) ? $flaggings_count['ngf_follow_group'] : 0;
    $followers_count_string = \Drupal::translation()->formatPlural($followers_count_raw, '1 follower', '@count followers', array('@count' => $followers_count_raw));
    $followers_page_url     = Url::fromRoute('ngf_group.page.followers', ['group' => $group->id()]);

    $followers['count'] = $followers_count_raw;
    $followers['text']  = $followers_count_string;
    $followers['url']   = $followers_page_url;

    return $followers;
  }

  /**
   * Return a link to group followers page.
   */
  public function getFollowersLink() {
    $followers = $this->getFollowersInfo();
    return Link::fromTextAndUrl($followers['text'], $followers['url'])->toRenderable();
  }

  /**
   * Return info about group subgroups.
   */
  public function getSubgroupsInfo() {
    $group = $this->group;

    // Add the view block.
    $view = Views::getView('ngf_group_subgroups');
    $view->setDisplay('subgroups');
    $view->setArguments([$group->id()]);
    $view->preExecute();
    $view->execute();

    $subgroups_count_raw    = $view->total_rows;
    $subgroups_count_string = \Drupal::translation()->formatPlural($subgroups_count_raw, '1 subgroup', '@count subgroups', array('@count' => $subgroups_count_raw));
    $subgroups_page_url     = Url::fromRoute('ngf_group.page.subgroups', ['group' => $group->id()]);

    $subgroups['count'] = $subgroups_count_raw;
    $subgroups['text']  = $subgroups_count_string;
    $subgroups['url']   = $subgroups_page_url;

    return $subgroups;
  }

  /**
   * Return info about group subgroups.
   */
  public function getSubgroupsLink() {
    $subgroups = $this->getSubgroupsInfo();
    return Link::fromTextAndUrl($subgroups['text'], $subgroups['url'])->toRenderable();
  }

  /**
   * Returns group ops links.
   */
  public function getMembershipLinks() {
    $group = $this->group;
    $links = [];

    $operations = [];
    // Retrieve the operations from the installed content plugins.
    foreach ($group->getGroupType()->getInstalledContentPlugins() as $plugin) {
      if ($plugin->getPluginId() == 'group_membership') {
        $operations += $plugin->getGroupOperations($group);
      }
    }

    if ($operations) {
      // Allow modules to alter the collection of gathered links.
      \Drupal::moduleHandler()->alter('group_operations', $links, $group);

      // Sort the operations by weight.
      uasort($operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');

      foreach ($operations as $key => $value) {
        $title = str_replace(' group', '', $value['title']);
        $links[$key]['#title'] = $title;
        $links[$key]['#url'] = $value['url'];
        $links[$key]['#type'] = 'link';
        $links[$key]['#attributes']['class'][] = $key;
      }

      // Remove the join group link for non-public groups.
      if (!$this->isPublic()) {
        unset($links['group-join']);
      }
    }
    return $links;
  }

  /**
   * Return the follow a group flag's render array.
   */
  public function getFollowFlag() {
    if (!\Drupal::currentUser()->isAnonymous()) {
      $group = $this->group;

      return [
        '#lazy_builder' => [
          'flag.link_builder:build',
          [
            $group->getEntityTypeId(),
            $group->id(),
            'ngf_follow_group',
          ],
        ],
        '#create_placeholder' => TRUE,
      ];
    }
  }

  /**
   * Return the group tabs.
   */
  public function getGroupTabs() {
    $block_manager = \Drupal::service('plugin.manager.block');
    $config = [
      'primary' => FALSE,
      'secondary' => TRUE
    ];
    $plugin_block = $block_manager->createInstance('local_tasks_block', $config);
    $access_result = $plugin_block->access(\Drupal::currentUser());
    if (is_object($access_result) && $access_result->isForbidden() || is_bool($access_result) && !$access_result) {
      return [];
    }

    $render['wrapper'] = [
      '#type' => 'html_tag',
      '#tag' => 'nav',
      '#attributes' => [
        'class' => [
          'inpage-nav',
        ]
      ]
    ];

    $render['wrapper']['tabs'] = $plugin_block->build();

    return $render;
  }

}
