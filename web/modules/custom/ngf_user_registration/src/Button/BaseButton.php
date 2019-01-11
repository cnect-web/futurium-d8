<?php

namespace Drupal\ngf_user_registration\Button;

/**
 * Class BaseButton.
 *
 * @package Drupal\ngf_user_registration\Button
 */
abstract class BaseButton implements ButtonInterface {

  /**
   * {@inheritdoc}
   */
  public function ajaxify() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmitHandler() {
    return FALSE;
  }

}
