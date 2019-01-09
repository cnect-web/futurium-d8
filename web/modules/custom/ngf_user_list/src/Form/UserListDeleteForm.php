<?php

namespace Drupal\ngf_user_list\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

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
  }

}
