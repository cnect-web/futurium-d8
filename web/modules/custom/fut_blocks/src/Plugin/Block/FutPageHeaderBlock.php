<?php

namespace Drupal\fut_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\fut_group\Breadcrumb\GroupBreadcrumbBuilder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\fut_group\RequestEntityExtractor;

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
   * The request entity extractor.
   *
   * @var \Drupal\fut_group\RequestEntityExtractor
   */
  protected $requestEntityExtractor;

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
   * @param \Drupal\fut_group\RequestEntityExtractor $request_entity_extractor
   *   The request entity extractor.
   * @param \Drupal\fut_group\Breadcrumb\GroupBreadcrumbBuilder $breadcrumb_builder
   *   The breadcrumb builder service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestEntityExtractor $request_entity_extractor, GroupBreadcrumbBuilder $breadcrumb_builder, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->requestEntityExtractor = $request_entity_extractor;
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
      $container->get('fut_group.request_entity_extractor'),
      $container->get('fut_group.breadcrumb'),
      $container->get('module_handler'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $group = $this->requestEntityExtractor->getGroup();

    if (!empty($group)) {

      $node = $this->requestEntityExtractor->getNode() ?? '';
      $title = $group->label();
      $visual_identity = $this->getVisualIdentity($group);
      $breadcrumb = $this->breadcrumbBuilder->build($this->requestEntityExtractor->getRouteMatch());
      $group_operations = $this->getGroupOperations($group);

      $build = [
        '#theme' => 'page_header_block',
        '#breadcrumb' => $breadcrumb->getLinks(),
        '#identity' => $this->configFactory->get('system.site')->get('name'),
        '#title' => $title,
        '#visual_identity' => $visual_identity,
        '#group_operations' => $group_operations,
        '#group_url' => $group->toUrl(),
        '#group' => $group,
        '#node' => $node,
      ];

      return $build;
    }
  }

  /**
   * Constructs array with src path for image and alt text.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group Entity.
   *
   * @return array
   *   Array with src and alt.
   */
  protected function getVisualIdentity(GroupInterface $group) {
    if (empty($group->fut_logo->first()->entity)) {
      return '';
    }

    $file_entity = $group->fut_logo->first()->entity;

    $image_src = ImageStyle::load('fut_group_logo')
      ->buildUrl($file_entity
        ->get('uri')
        ->first()
        ->getString());
    $alt = $group->fut_logo->alt ?? '';

    $image = [
      'src' => $image_src,
      'alt' => $alt,
    ];

    return $image;
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
