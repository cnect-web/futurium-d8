<?php

namespace Drupal\ngf_user_profile;

use Drupal\Core\Messenger\MessengerInterface;

trait MessageTrait {
  protected function addError($message) {
    $this->messenger->addMessage($message, MessengerInterface::TYPE_ERROR);
  }

  protected function addMessage($message) {
    $this->messenger->addMessage($message, MessengerInterface::TYPE_STATUS);
  }

}