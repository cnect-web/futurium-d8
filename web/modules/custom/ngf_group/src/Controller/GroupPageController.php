<?php

namespace Drupal\ngf_group\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\ngf_group\Entity\Decorator\NGFGroup;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\group\Cache\Context\GroupCacheContext;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Discover page controller.
 */
class GroupPageController extends ControllerBase {

  /**
   * The current group.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $currentGroup;

  /**
   * The current group decorator.
   *
   * @var \Drupal\ngf_group\Entity\Decorator\NGFGroup
   */
  protected $gd;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    GroupCacheContext $group
  ) {
    $this->currentGroup = $group->getBestCandidate();
    $this->gd = new NGFGroup($this->currentGroup);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cache_context.group')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function accessCheck(EntityInterface $group) {
    if ($group->bundle() == 'ngf_discussion_group') {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function landingPage(EntityInterface $group) {
    switch ($group->bundle()) {
      case 'ngf_discussion_group':
      case 'ngf_session':
        return $this->publicationsPage($group);

      case 'ngf_event':
        return $this->eventPage($group);

    }
  }

  /**
   * {@inheritdoc}
   */
  public function groupInfo(EntityInterface $group) {
    return $this->getPageContent($group, $this->groupDisplay($group, 'ngf_about'));
  }

  /**
   * {@inheritdoc}
   */
  public function publicationsPage(EntityInterface $group) {
    return $this->getViewContent($group, 'publications');
  }

  /**
   * {@inheritdoc}
   */
  public function eventsPage(EntityInterface $group) {
    return $this->getViewContent($group, 'events');
  }

  /**
   * {@inheritdoc}
   */
  public function libraryPage(EntityInterface $group) {
    return $this->getViewContent($group, 'library');
  }

  /**
   * {@inheritdoc}
   */
  public function sharedContentPage(EntityInterface $group) {
    // Add the group header.
    return $this->getPageContent($group, [
      '#type' => 'markup',
      '#markup' => 'There is no shared content'
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function membersPage(EntityInterface $group) {
    return $this->getViewContent($group, 'members');
  }

  /**
   * {@inheritdoc}
   */
  public function followersPage(EntityInterface $group) {
    return $this->getViewContent($group, 'followers');
  }

  /**
   * {@inheritdoc}
   */
  public function subgroupsPage(EntityInterface $group) {
    return $this->getViewContent($group, 'subgroups');
  }

  /**
   * {@inheritdoc}
   */
  public function eventPage(EntityInterface $event) {
    $render[] = $this->groupDisplay($event, 'full');
    $render[] = $this->getContentView('ngf_sessions', 'sessions', $event->id(), FALSE);
    return $render;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewContent(EntityInterface $group, $page) {
    return $this->getPageContent($group, $this->getContentView("ngf_group_$page", $page, $group->id()));
  }

  /**
   *
   */
  public function getPageContent(EntityInterface $group, $content) {
    // Add the group.
    $render_array['header'] = $this->groupDisplay($group);

    if ($this->accessCheck($group) == AccessResult::allowed()) {
      // Add the group tabs.
      $render_array['group_tabs'] = $this->gd->getGroupTabs();

      // Add the view block.
      $render_array['content'] = $content;
    }

    return $render_array;
  }

  /**
   * {@inheritdoc}
   */
  public function groupDisplay(EntityInterface $group, $view_mode = 'ngf_header') {
    $view_builder = $this->entityTypeManager()->getViewBuilder('group');
    return $view_builder->view($group, $view_mode);
  }

  /**
   * {@inheritdoc}
   */
  public function getContentView($view_name, $display_name, $group_id, $display_empty = TRUE) {

    // Add the view block.
    $view = Views::getView($view_name);
    $view->setDisplay($display_name);
    $view->setArguments([$group_id]);
    $view->preExecute();
    $view->execute();

    $render_array = [];
    if ($view->total_rows || $display_empty) {
      $render_array['view'] = [
        '#type' => 'container',
        '#tree' => TRUE,
      ];

      // Add the groups view title to the render array.
      $title = $view->getTitle();
      if ($title) {
        $render_array['title'] = [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#value' => $title,
        ];
      }

      // Add the groups view to the render array.
      $render_array['content'] = $view->render();

      return $render_array;
    }

    return $render_array;
  }

  public function reportedContentPage(EntityInterface $group) {
    return $this->getViewContent($group, 'reported_content');
  }

  public function reportedCommentsPage(EntityInterface $group) {
    return $this->getViewContent($group, 'reported_comments');
  }

}
