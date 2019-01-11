<?php

namespace Drupal\ngf_user_list\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting User list entities.
 *
 * @ingroup ngf_user_list
 */
class UserListDeleteForm extends ContentEntityDeleteForm {
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_state->setRedirect('ngf_user_profile.page.user_profile', ['user' => $this->currentUser()->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $entity = $this->getEntity();
    return new Url('ngf_user_list.list_items', [
      'ngf_user_list' => $entity->id(),
    ]);
  }

}
