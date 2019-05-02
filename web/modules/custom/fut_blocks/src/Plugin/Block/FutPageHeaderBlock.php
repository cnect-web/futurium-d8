<?php

namespace Drupal\fut_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\fut_group\Breadcrumb\GroupBreadcrumbBuilder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\image\Entity\ImageStyle;

/**
 * Provides a 'FutPageHeaderBlock' block.
 *
 * @Block(
 *  id = "fut_page_header_block",
 *  admin_label = @Translation("Futurium Page Header"),
 * )
 */
class FutPageHeaderBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The breadcrumb builder.
   *
   * @var \Drupal\fut_group\Breadcrumb\GroupBreadcrumbBuilder
   */
  protected $breadcrumbBuilder;

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, GroupBreadcrumbBuilder $breadcrumb_builder, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->routeMatch = $route_match;
    $this->breadcrumbBuilder = $breadcrumb_builder;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('fut_group.breadcrumb'),
      $container->get('module_handler'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $parameters = $this->getRouteParameters();

    if (isset($parameters['group'])) {
      $group = $parameters['group'];
      $node = $parameters['node'] ?? '';
      $title = $group->label();

      if (!empty($group->fut_visual_identity->first()->entity)) {
        $visual_identity = $this->getVisualIdentity($group->fut_visual_identity->first()->entity);
      }

      $breadcrumb = $this->breadcrumbBuilder->build($this->routeMatch);

      $group_operations = $this->getGroupOperations($group);

      $build = [
        '#theme' => 'page_header_block',
        '#breadcrumb' => $breadcrumb->getLinks() ?? '',
        '#identity' => $this->configFactory->get('system.site')->get('name'),
        '#title' => $title ?? '',
        '#visual_identity' => $visual_identity ?? '',
        '#group_operations' => $group_operations ?? '',
        '#group_url' => $group->toUrl() ?? '',
        '#group' => $group ?? '',
        '#node' => $node ?? '',
      ];

      return $build;
    }
  }

  /**
   * Constructs array with src path for image and alt text.
   *
   * @param \Drupal\media\MediaInterface $media_entity
   *   Media Entity holding group visual identity.
   *
   * @return array
   *   Array with src and alt.
   */
  protected function getVisualIdentity(MediaInterface $media_entity) {
    if ($img_entity_list = $media_entity->get('field_media_image')) {
      if ($img_entity = $img_entity_list->first()) {
        if ($file_entity = $img_entity->get('entity')->getTarget()) {

          $image_style_name = 'fut_default_thumbnail';
          $visual_identity_image = ImageStyle::load($image_style_name)
            ->buildUrl($file_entity
              ->get('uri')
              ->first()
              ->getString());
          $visual_identity_alt = $img_entity->get('alt')->getString();

          $visual_identity = [
            'src' => $visual_identity_image,
            'alt' => $visual_identity_alt,
          ];

          return $visual_identity;
        }
      }
    }
    return NULL;
  }

  /**
   * Returns the route parameters needed (group and node if available).
   *
   * @return array
   *   An array of parameter names and values.
   */
  protected function getRouteParameters() {
    $parameters = [];

    foreach ($this->routeMatch->getParameters() as $parameter) {
      if ($parameter instanceof EntityInterface) {
        $parameters[$parameter->getEntityType()->id()] = $parameter;
        if ($parameter->getEntityType()->id() == 'node') {
          $group_contents = GroupContent::loadByEntity($parameter);
          foreach ($group_contents as $group_content) {
            $parameters['group'] = $group_content->getGroup();
          }
        }
      }
    }

    return $parameters;

  }

  /**
   * Provides a list of operations for a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to generate the operations for.
   *
   * @return array
   *   An associative array of operation links to show in the block.
   */
  protected function getGroupOperations(GroupInterface $group) {
    $group_operations = [];

    foreach ($group->getGroupType()->getInstalledContentPlugins() as $plugin) {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      $group_operations += $plugin->getGroupOperations($group);
    }

    if ($group_operations) {
      // Allow modules to alter the collection of gathered links.
      $this->moduleHandler->alter('group_operations', $group_operations, $group);

      // Sort the operations by weight.
      uasort($group_operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');

      return $group_operations;
    }
  }

}
