<?php

namespace Drupal\fut_group\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\fut_group\RequestEntityExtractor;
use Drupal\Core\Link;

/**
 * Provides a 'FutGroupContentManagementNav' block.
 *
 * @Block(
 *  id = "fut_group_content_management_nav",
 *  admin_label = @Translation("Futurium Group Content Management Nav"),
 * )
 */
class FutGroupContentManagementNav extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The request entity extractor.
   *
   * @var \Drupal\fut_group\RequestEntityExtractor
   */
  protected $requestEntityExtractor;



  /**
   * Constructs a new FutGroupContentManagementNav object.
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

    $group = $this->requestEntityExtractor->getGroup();

    if (!empty($group)) {

      $teste = '';

      $items = [
        Link::createFromRoute('Content', 'fut_group.manage_group_content', ['group' => $group->id()]),
        Link::createFromRoute('Library', 'fut_group.manage_group_content.library', ['group' => $group->id()]),
        Link::createFromRoute('Collections', 'fut_group.manage_group_content.collections', ['group' => $group->id()]),
        Link::createFromRoute('Subgroups', 'fut_group.manage_group_content.subgroups', ['group' => $group->id()]),
      ];

      foreach ($items as $link) {
        if ($link->getUrl()->getRouteName() == $this->requestEntityExtractor->getRouteMatch()->getRouteName()) {
          $options = [
            'attributes' => [
              'class' => [
                'active'
              ]
            ]
          ];
          $link->getUrl()->setOptions($options);
        }
      }

      $build = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => $items,
      ];

      return $build;
    }
  }


}
