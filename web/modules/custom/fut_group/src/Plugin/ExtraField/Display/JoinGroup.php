<?php

namespace Drupal\fut_group\Plugin\ExtraField\Display;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\extra_field\Plugin\ExtraFieldDisplayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Join Group field Display.
 *
 * @ExtraFieldDisplay(
 *   id = "join_group",
 *   label = @Translation("Join Button"),
 *   bundles = {
 *     "group.fut_open",
 *   }
 * )
 */
class JoinGroup extends ExtraFieldDisplayBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a JoinGroup object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(ContentEntityInterface $entity) {
    $elements = [];
    if ($entity->hasPermission('join group', $this->currentUser)) {
      $elements['join-group'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => [
            'extra-field',
            'extra-field--join-group',
          ],
        ],
        [
          '#type' => 'link',
          '#url' => new Url('entity.group.join', ['group' => $entity->id()]),
          '#title' => $this->t('Join group'),
        ],
      ];
    }
    return $elements;
  }

}
