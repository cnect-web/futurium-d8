<?php

namespace Drupal\fut_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\fut_group\RequestEntityExtractor;

/**
 * Provides a 'FutCreateContentLinkBlock' block.
 *
 * @Block(
 *  id = "fut_create_content_link_block",
 *  admin_label = @Translation("Futurium Create Content Link"),
 * )
 */
class FutCreateContentLinkBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The request entity extractor.
   *
   * @var \Drupal\fut_group\RequestEntityExtractor
   */
  protected $requestEntityExtractor;

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestEntityExtractor $request_entity_extractor) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->requestEntityExtractor = $request_entity_extractor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('fut_group.request_entity_extractor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $node = $this->requestEntityExtractor->getNode();
    $group = $this->requestEntityExtractor->getGroup();

    if (!empty($group) && !empty($node)) {
      $link = $this->getGroupOperation($group, $node);
      if ($link) {
        $build = [
          '#theme' => 'create_content_link_block',
          '#link' => $link,
        ];
        return $build;
      }
    }
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
  protected function getGroupOperation(GroupInterface $group, NodeInterface $node) {
    $group_operations = [];
    foreach ($group->getGroupType()->getInstalledContentPlugins() as $plugin) {
      if ($plugin->getPluginId() == "group_node:{$node->getType()}") {
        $group_operations = $plugin->getGroupOperations($group);
        $group_operations = reset($group_operations);
      }
    }
    return $group_operations;
  }

}
