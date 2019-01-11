<?php

namespace Drupal\ngf_core\Manager;

use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity\EntityTypeManagerInterface;


class HomepageManager {

  /**
   * Entity Type Manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager = NULL;

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  public function getContent() {
    $items = $this->prepareContent();
    $render = [];
    if (count($items) > 0) {
      $page = pager_find_page();
      $num_per_page = 10;
      $offset = $num_per_page * $page;
      $result = array_slice($items, $offset, $num_per_page);

      // Now that we have the total number of results, initialize the pager.
      pager_default_initialize(count($items), $num_per_page);
      $render = [];
      $render['#prefix'] = '<h3>' . t(
        'Public feed') . '</h3><div class="newsfeed">';
      $render['#suffix'] = '</div>';

      foreach ($result as $item) {
        // There is a bug partial is still displayed even it's hidden in the view mode.
        $render['content'][] = $item;
      }
      $render['content'][] = [
        '#type' => 'pager',
      ];
    }

    return $render;
  }

  protected function prepareContent() {
    $items = [];
    $content_items = $this->getDiscussions();
    $content_items += $this->getGroups();
    $content_items += $this->getEvents();

    usort($content_items, ['Drupal\ngf_core\Manager\HomepageManager', 'sort']);
    foreach ($content_items as $content_item) {
      if ($content_item->getEntityTypeId() == 'node') {
        $items[] = $this->entityTypeManager->getViewBuilder('node')
        ->view($content_item, 'teaser');
      }
      elseif ($content_item->getEntityTypeId() == 'group') {
        $items[] = $this->entityTypeManager->getViewBuilder('group')
          ->view($content_item, 'teaser');
      }
    }
    return $items;
  }

  public static function sort($a, $b) {
    $a_created = $a->getCreatedTime();
    $b_created = $b->getCreatedTime();
    if ($a_created == $b_created) {
      return 0;
    }
    return ($a_created > $b_created) ? -1 : 1;
  }

  protected function getGroups() {
    return $this->getGroupItems('ngf_discussion_group');
  }

  protected function getEvents() {
    return $this->getGroupItems('ngf_event');
  }

  protected function getContentItems($bundle = NULL) {
    $query = \Drupal::entityQuery('node');
    if (!empty($bundle)) {
      $query->condition('type', $bundle);
    }
    $query->condition('status', 1);
    $query->sort('created', 'DESC');


    return Node::loadMultiple($query->execute());
  }

  protected function getGroupItems($bundle = NULL) {
    $query = \Drupal::entityQuery('group');
    if (!empty($bundle)) {
      $query->condition('type', $bundle);
    }
    $query->sort('created', 'DESC');
    return Group::loadMultiple($query->execute());
  }

  protected function getDiscussions() {
    return $this->getContentItems( 'ngf_discussion');
  }

}
