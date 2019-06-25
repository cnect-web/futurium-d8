<?php

namespace Drupal\fut_group\Plugin\views\filter;

use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filter by group where current user is member.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("fut_group_accessible_groups")
 */
class AccessibleGroups extends FilterPluginBase {

  /**
   * The membership loader service.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $membershipLoader;

  /**
   * Constructs a Bundle object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\group\GroupMembershipLoaderInterface $membership_loader
   *   The group membership loader service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GroupMembershipLoaderInterface $membership_loader) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->membershipLoader = $membership_loader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('group.membership_loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['not'] = ['default' => FALSE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['not'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Inverse'),
      '#description' => $this->t('If checked, will display groups where current user is not a member.'),
      '#default_value' => $this->options['not'],
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Display the filter on the administrative summary.
   */
  public function adminSummary() {
    if ($this->options['not']) {
      return $this->t('Showing groups where user is not member.');
    }
    return $this->t('Showing groups where user is member.');
  }

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Make sure that the entity base table is in the query.
    $this->ensureMyTable();

    // Get the current user account and groups it belongs to.
    $account = $this->view->getUser();
    $account_groups = $this->membershipLoader->loadByUser($account);

    $account_group_ids = [];
    foreach ($account_groups as $group_membership) {
      // Store group Id.
      $account_group_ids[] = $group_membership->getGroup()->id();
    }

    // Add condition (Group IDs where current user is member).
    if (!empty($account_group_ids)) {
      $operator = !($this->options['not']) ? 'IN' : 'NOT IN';
      $this->view->query->addWhere($this->options['group'], "{$this->tableAlias}.{$this->realField}", $account_group_ids, $operator);
    }
  }

}
