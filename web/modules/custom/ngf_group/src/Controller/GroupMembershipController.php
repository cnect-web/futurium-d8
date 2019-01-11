<?php

namespace Drupal\ngf_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\group\Controller\GroupMembershipController as BaseController;

/**
 * Provides group membership route controllers.
 *
 * This only controls the routes that are not supported out of the box by the
 * plugin base \Drupal\group\Plugin\GroupContentEnablerBase.
 */
class GroupMembershipController extends BaseController {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * Constructs a new GroupMembershipController.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   */
  public function __construct(AccountInterface $current_user, EntityFormBuilderInterface $entity_form_builder) {
    $this->currentUser = $current_user;
    $this->entityFormBuilder = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity.form_builder')
    );
  }

  /**
   * Provides the form for joining a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to join.
   *
   * @return array
   *   A group join form.
   */
  public function join(GroupInterface $group) {
    /**
     * @todo: check if the below code is still needed for membership controlling.
     */
    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
    $plugin = $group->getGroupType()->getContentPlugin('group_membership');
    $groupVisibility = NGF_GROUP_PUBLIC;
    if ($group->hasField('field_ngf_group_visibility')) {
      $groupVisibility = $group->get('field_ngf_group_visibility')->getString();
    }

    // Membership default to active unless private group.
    if ($groupVisibility == NGF_GROUP_PRIVATE && !$group->getMember($this->currentUser)) {
      $membership_state = $group->getGroupType()->getPendingMembershipStateId();
      $group_content = GroupContent::create([
        'type' => $plugin->getContentTypeConfigId(),
        'gid' => $group->id(),
        'entity_id' => $this->currentUser->id(),
        'group_membership_state' => $group->getGroupType()->getPendingMembershipStateId(),
      ]);

      return $this->entityFormBuilder()->getForm($group_content, 'group-request-membership');
    }
    else {
      $group_content = GroupContent::create([
        'type' => $plugin->getContentTypeConfigId(),
        'gid' => $group->id(),
        'entity_id' => $this->currentUser->id(),
        'group_membership_state' => $group->getGroupType()->getActiveMembershipStateId()
      ]);
      return $this->entityFormBuilder->getForm($group_content, 'group-join');
    }
  }

  /**
   * The _title_callback for the join form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to join.
   *
   * @return string
   *   The page title.
   */
  public function joinTitle(GroupInterface $group) {
    return $this->t('Join group %label', ['%label' => $group->label()]);
  }

  /**
   * Provides the form for requesting a group membership.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to request membership of.
   *
   * @return array
   *   A group membership request form.
   */
  public function requestMembership(GroupInterface $group) {
    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
    $plugin = $group->getGroupType()->getContentPlugin('group_membership');

    // Pre-populate a group membership with the current user.
    $group_content = GroupContent::create([
      'type' => $plugin->getContentTypeConfigId(),
      'gid' => $group->id(),
      'entity_id' => $this->currentUser->id(),
      'group_membership_state' => $group->getGroupType()->getPendingMembershipStateId(),
    ]);

    return $this->entityFormBuilder()->getForm($group_content, 'group-request-membership');
  }

  /**
   * The _title_callback for the request membership form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to request membership of.
   *
   * @return string
   *   The page title.
   */
  public function requestMembershipTitle(GroupInterface $group) {
    return $this->t('Request membership group %label', ['%label' => $group->label()]);
  }

  /**
   * Provides the form for approving a requested group membership.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group containing the requested membership.
   * @param \Drupal\group\Entity\GroupContent $group_content
   *   The requested group membership.
   *
   * @return array
   *   A group membership approval form.
   */
  public function approveMembership(GroupInterface $group, GroupContent $group_content) {
    return $this->entityFormBuilder()->getForm($group_content, 'group-approve-membership');
  }

  /**
   * The _title_callback for the approve requested membership form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group containing the requested membership.
   *
   * @return string
   *   The page title.
   */
  public function approveMembershipTitle(GroupInterface $group) {
    return $this->t('Approve membership request for group %label', ['%label' => $group->label()]);
  }

  /**
   * Provides the form for rejecting a requested group membership.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group containing the requested membership.
   * @param \Drupal\group\Entity\GroupContent $group_content
   *   The requested group membership.
   *
   * @return array
   *   A group membership rejection form.
   */
  public function rejectMembership(GroupInterface $group, GroupContent $group_content) {
    return $this->entityFormBuilder()->getForm($group_content, 'group-reject-membership');
  }

  /**
   * The _title_callback for the reject requested membership form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group containing the requested membership.
   *
   * @return string
   *   The page title.
   */
  public function rejectMembershipTitle(GroupInterface $group) {
    return $this->t('Reject membership request for group %label', ['%label' => $group->label()]);
  }

  /**
   * Provides the form for banning a group membership.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group containing the requested membership.
   * @param \Drupal\group\Entity\GroupContent $group_content
   *   The requested group membership.
   *
   * @return array
   *   A group membership ban form.
   */
  public function banMembership(GroupInterface $group, GroupContent $group_content) {
    return $this->entityFormBuilder()->getForm($group_content, 'group-ban-membership');
  }

  /**
   * The _title_callback for the ban requested membership form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group containing the requested membership.
   *
   * @return string
   *   The page title.
   */
  public function banMembershipTitle(GroupInterface $group) {
    return $this->t('Ban account from having an active or pending membership in group %label', ['%label' => $group->label()]);
  }

  /**
   * Provides the form for leaving a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to leave.
   *
   * @return array
   *   A group leave form.
   */
  public function leave(GroupInterface $group) {
    $group_content = $group->getMember($this->currentUser)->getGroupContent();
    return $this->entityFormBuilder->getForm($group_content, 'group-leave');
  }

}
