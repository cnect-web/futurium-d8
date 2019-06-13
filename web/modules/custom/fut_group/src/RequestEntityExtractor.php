<?php

namespace Drupal\fut_group;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
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
   * @return Drupal\node\Entity\Node
   */
  public function getNode() {
    return $this->node;
  }

  /**
   * @param \Drupal\node\Entity\Node $node
   */
  public function setNode($node) {
    $this->node = $node;
  }

  /**
   * @return \Drupal\group\Entity\Group
   */
  public function getGroup() {
    return $this->group;
  }

  /**
   * @param \Drupal\group\Entity\Group $group
   */
  public function setGroup($group) {
    $this->group = $group;
  }

  /**
   * @return \Drupal\group\Entity\GroupContent
   */
  public function getGroupContent() {
    return $this->groupContent;
  }

  /**
   * @param \Drupal\group\Entity\GroupContent $groupContent
   */
  public function setGroupContent($groupContent) {
    $this->groupContent = $groupContent;
  }

  /**
   * @return \Drupal\Core\Routing\RouteMatchInterface
   */
  public function getRouteMatch() {
    return $this->routeMatch;
  }

  /**
   * Constructs a new FutPageHeaderBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\fut_group\Breadcrumb\GroupBreadcrumbBuilder $breadcrumb_builder
   *   The breadcrumb builder service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
    $this->extractEntities();
  }

  /**
   *
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
