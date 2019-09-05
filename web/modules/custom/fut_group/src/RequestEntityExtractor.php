<?php

namespace Drupal\fut_group;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\Entity\Node;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;

/**
 * Request entity extractor class.
 */
class RequestEntityExtractor {

  /**
   * Node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * Group.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $group;

  /**
   * Group content.
   *
   * @var \Drupal\group\Entity\GroupContent
   */
  protected $groupContent;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Node getter.
   *
   * @return Drupal\node\Entity\Node
   *   A node entity.
   */
  public function getNode() {
    return $this->node;
  }

  /**
   * Node setter.
   *
   * @param \Drupal\node\Entity\Node $node
   *   A node entity.
   */
  public function setNode(Node $node) {
    $this->node = $node;
  }

  /**
   * Group getter.
   *
   * @return \Drupal\group\Entity\Group
   *   A Group entity.
   */
  public function getGroup() {
    return $this->group;
  }

  /**
   * Group setter.
   *
   * @param \Drupal\group\Entity\Group $group
   *   A Group entity.
   */
  public function setGroup(Group $group) {
    $this->group = $group;
  }

  /**
   * The group content.
   *
   * @return \Drupal\group\Entity\GroupContent
   *   A GroupContent entity.
   */
  public function getGroupContent() {
    return $this->groupContent;
  }

  /**
   * Group content setter.
   *
   * @param \Drupal\group\Entity\GroupContent $groupContent
   *   A GroupContent entity.
   */
  public function setGroupContent(GroupContent $groupContent) {
    $this->groupContent = $groupContent;
  }

  /**
   * Route match getter.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   *   A RouteMatchInterface object.
   */
  public function getRouteMatch() {
    return $this->routeMatch;
  }

  /**
   * Constructs a new FutPageHeaderBlock object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
    $this->extractEntities();
  }

  /**
   * Extract entities.
   */
  private function extractEntities() {
    foreach ($this->routeMatch->getParameters() as $parameter) {
      if ($parameter instanceof EntityInterface) {
        switch ($parameter->getEntityType()->id()) {
          case 'group':
            $this->setGroup($parameter);
            break;

          case 'group_content':
            $this->setGroupContent($parameter);
            if (!empty($parameter)) {
              if (!empty($this->getGroup())) {
                $this->setGroupContent($parameter->getGroup());
              }
            }
            break;

          case 'node':
            $this->setNode($parameter);

            // Set Group content if it exists.
            $group_contents = GroupContent::loadByEntity($parameter);
            if ($group_contents) {
              $group_content = NULL;
              if (empty($this->getGroupContent())) {
                $group_content = reset($group_contents);
                $this->setGroupContent($group_content);
                $this->setGroup($group_content->getGroup());
              }

              // Set Group if it exists.
              if (!empty($group_content)) {
                if (!empty($this->getGroup())) {
                  $this->setGroupContent($group_content->getGroup());
                }
              }
            }
            break;
        }
      }
    }
  }

}
