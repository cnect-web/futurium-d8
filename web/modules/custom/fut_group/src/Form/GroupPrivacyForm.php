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
   * @var \Drupal\group\Entity\Group;
   */
  protected $group;

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
   * {@inheritdoc}
   */
  function privateSubmit(array &$form, FormStateInterface $form_state) {
    $group_type = $this->group->getGroupType();
    $group_permission = GroupPermission::loadByGroup($this->group);

    $custom_permissions = [];
    if (!empty($group_permission)) {
      $custom_permissions = $group_permission->getPermissions()->first()->getValue();
    }

    $roles = $this->getGroupNonMemberRoles($group_type);

    foreach ($roles as $role_id => $role) {
      $this->removeArrayValue($custom_permissions, $role_id, 'view group');

      $plugins = $group_type->getInstalledContentPlugins();
      foreach ($plugins as $plugin) {
        if ($plugin->getEntityTypeId() == 'node') {
          $this->removeArrayValue($custom_permissions, $role_id, "view {$plugin->getPluginId()} entity");
        }
      }
    }

    $group_permission->setPermissions($custom_permissions);
    $violations = $group_permission->validate();
    if (count($violations) == 0) {
      $group_permission->save();
      $this->messenger()->addMessage($this->t('Group is private now.'));
    }
    else {
      foreach ($violations as $violation) {
        $this->messenger()->addError($this->t($violation->getMessage()));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  function publicSubmit(array &$form, FormStateInterface $form_state) {
    $group_type = $this->group->getGroupType();
    $group_permission = GroupPermission::loadByGroup($this->group);

    $custom_permissions = [];
    if (!empty($group_permission)) {
      $custom_permissions = $group_permission->getPermissions()->first()->getValue();
    }

    $roles = $this->getGroupNonMemberRoles($group_type);

    foreach ($roles as $role_id => $role) {
      $this->addArrayValue($custom_permissions, $role_id, 'view group');

      $plugins = $group_type->getInstalledContentPlugins();
      foreach ($plugins as $plugin) {
        if ($plugin->getEntityTypeId() == 'node') {
          $this->addArrayValue($custom_permissions, $role_id, "view {$plugin->getPluginId()} entity");
        }
      }
    }

    $group_permission->setPermissions($custom_permissions);
    $violations = $group_permission->validate();
    if (count($violations) == 0) {
      $group_permission->save();
      $this->messenger()->addMessage($this->t('Group is public now.'));
    }
    else {
      foreach ($violations as $violation) {
        $this->messenger()->addError($this->t($violation->getMessage()));
      }
    }
  }

  protected function getGroupNonMemberRoles($group_type) {
    $storage = $this->entityTypeManager->getStorage('group_role');
    $roles = $storage->loadSynchronizedByGroupTypes([$group_type->id()]);
    $roles[$group_type->getOutsiderRoleId()] = $group_type->getOutsiderRole();
    $roles[$group_type->getAnonymousRoleId()] = $group_type->getAnonymousRole();
    return $roles;
  }

  protected function removeArrayValue(&$permissions, $role_id, $permission) {
    if (($key = array_search($permission, $permissions[$role_id])) !== false) {
      unset($permissions[$role_id][$key]);
    }
  }

  protected function addArrayValue(&$permissions, $role_id, $permission) {
    if (($key = array_search($permission, $permissions[$role_id])) == false) {
      $permissions[$role_id][] = $permission;
    }
  }

  /**
   * {@inheritdoc}
   */
  function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
