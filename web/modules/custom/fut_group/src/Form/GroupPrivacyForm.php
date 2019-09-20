<?php

namespace Drupal\fut_group\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\group\Entity\Group;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group_permissions\Entity\GroupPermission;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the group permissions administration form.
 */
class GroupPrivacyForm extends FormBase {

  /**
   * Group.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $group;

  /**
   * GroupPermission.
   *
   * @var \Drupal\group_permissions\Entity\GroupPermission
   */
  protected $groupPermission;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_privacy';
  }

  /**
   * Gets the group type to build the form for.
   *
   * @return \Drupal\group\Entity\GroupTypeInterface
   *   The group type some or more roles belong to.
   */
  protected function getGroupType() {
    return $this->group->getGroupType();
  }

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new GroupPermissionsTypeSpecificForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Group $group = NULL) {
    $this->group = $group;

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['private'] = [
      '#type' => 'submit',
      '#value' => $this->t('Private'),
      '#submit' => [[$this, 'privateSubmit']],
    ];
    $form['actions']['public'] = [
      '#type' => 'submit',
      '#value' => $this->t('Public'),
      '#submit' => [[$this, 'publicSubmit']],
    ];

    return $form;
  }

  /**
   * Something.
   */
  protected function getGroupPermission() {
    if (empty($this->groupPermission)) {
      $this->groupPermission = GroupPermission::loadByGroup($this->group);
      if (empty($this->groupPermission)) {
        $this->groupPermission = GroupPermission::create([
          'gid' => $this->group->id(),
          'permissions' => [],
        ]);
      }
    }

    return $this->groupPermission;
  }

  /**
   * Alter something.
   */
  protected function alterCustomPermissions($action) {
    $group_type = $this->group->getGroupType();
    $custom_permissions = $this->getGroupPermission()->getPermissions()->first()->getValue();
    $roles = $this->getGroupNonMemberRoles($group_type);
    foreach ($roles as $role_id => $role) {
      $custom_permissions[$role_id] = $role->getPermissions();
      if ($action == 'remove') {
        $this->removeArrayValue($custom_permissions, $role_id, 'view group');
      }
      else {
        $this->addArrayValue($custom_permissions, $role_id, 'view group');
      }

      $plugins = $group_type->getInstalledContentPlugins();
      foreach ($plugins as $plugin) {
        if ($plugin->getEntityTypeId() == 'node' || $plugin->getEntityTypeId() == 'group') {
          if ($action == 'remove') {
            $this->removeArrayValue($custom_permissions, $role_id,
              "view {$plugin->getPluginId()} entity");
          }
          else {
            $this->addArrayValue($custom_permissions, $role_id,
              "view {$plugin->getPluginId()} entity");
          }
        }
      }
    }

    return $this->getGroupPermission()->setPermissions($custom_permissions);
  }

  /**
   * {@inheritdoc}
   */
  public function privateSubmit(array &$form, FormStateInterface $form_state) {
    $this->alterCustomPermissions('remove');
    $violations = $this->getGroupPermission()->validate();
    if (count($violations) == 0) {
      $this->getGroupPermission()->save();
      $this->messenger()->addMessage($this->t('Group is private now.'));
    }
    else {
      foreach ($violations as $violation) {
        $this->messenger()->addError($this->t('@message', ['@message' => $violation->getMessage()]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function publicSubmit(array &$form, FormStateInterface $form_state) {
    $this->alterCustomPermissions('add');
    $violations = $this->getGroupPermission()->validate();
    if (count($violations) == 0) {
      $this->getGroupPermission()->save();
      $this->messenger()->addMessage($this->t('Group is public now.'));
    }
    else {
      foreach ($violations as $violation) {
        $this->messenger()->addError($this->t('@message', ['@message' => $violation->getMessage()]));
      }
    }
  }

  /**
   * Get non-member roles from group type.
   */
  protected function getGroupNonMemberRoles($group_type) {
    $storage = $this->entityTypeManager->getStorage('group_role');
    $roles = $storage->loadSynchronizedByGroupTypes([$group_type->id()]);
    $roles[$group_type->getOutsiderRoleId()] = $group_type->getOutsiderRole();
    $roles[$group_type->getAnonymousRoleId()] = $group_type->getAnonymousRole();
    return $roles;
  }

  /**
   * Remove a value from an array.
   */
  protected function removeArrayValue(&$permissions, $role_id, $permission) {
    if (!empty($permissions[$role_id]) && ($key = array_search($permission, $permissions[$role_id])) !== FALSE) {
      unset($permissions[$role_id][$key]);
    }
  }

  /**
   * Add a value to an array.
   */
  protected function addArrayValue(&$permissions, $role_id, $permission) {
    if (empty($permissions[$role_id]) || (array_search($permission, $permissions[$role_id])) == FALSE) {
      $permissions[$role_id][] = $permission;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
