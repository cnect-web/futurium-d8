<?php

namespace Drupal\ngf_user_profile\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\views\Views;

/**
 * Class UserProfileControllerBase.
 */
class UserProfileControllerBase extends ControllerBase {

  protected function getEntityForm($form_view_mode, $entity, $entity_type = 'user') {
    $form = $this->entityTypeManager()
      ->getFormObject($entity_type, $form_view_mode)
      ->setEntity($entity);

    return $this->formBuilder()->getForm($form);
  }


  protected function getContent($content, $user = NULL) {
    return [
      'header' => $this->getUserDisplay($user ?? $this->getCurrentUserAccount(), 'ngf_profile'),
      'tabs' => $this->getTabs(),
      'content' => $content,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getUserDisplay(EntityInterface $entity, $view_mode = 'ngf_profile') {
    $view_builder = $this->entityTypeManager()->getViewBuilder('user');
    return $view_builder->view($entity, $view_mode);
  }

  /**
   * {@inheritdoc}
   */
  protected function getView($view_name, $display_name, $user_id) {
    // Add the view block.
    $view = Views::getView($view_name);
    $view->setDisplay($display_name);
    $view->setArguments([$user_id]);
    $view->preExecute();
    $view->execute();

    $render_array['view'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    // Add the groups view title to the render array.
    if ($title = $view->getTitle()) {
      $render_array['view']['title'] = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $title,
      ];
    }

    // Add the view to the render array.
    $render_array['view']['content'] = $view->render();

    return $render_array['view'];

  }

  /**
   * Return the tabs.
   */
  protected function getTabs() {
    $block_manager = \Drupal::service('plugin.manager.block');
    $config = [
      'primary' => FALSE,
      'secondary' => TRUE
    ];
    $plugin_block = $block_manager->createInstance('local_tasks_block', $config);
    $access_result = $plugin_block->access($this->currentUser());
    if (is_object($access_result) && $access_result->isForbidden() || is_bool($access_result) && !$access_result) {
      return [];
    }

    $render['wrapper'] = [
      '#type' => 'html_tag',
      '#tag' => 'nav',
      '#attributes' => [
        'class' => [
          'inpage-nav',
        ]
      ],
      'tabs' => $plugin_block->build(),
    ];

    return $render;
  }

  protected function getViewContent($content_name, EntityInterface $user = NULL) {
    $prefix = !empty($user) ? 'user_' : 'your_';
    return $this->getContent($this->getView(
      'ngf_user_' . $content_name,
      $prefix . $content_name,
      !empty($user) ? $user->id() : $this->currentUser()->id()
    ), $user);
  }


  protected function getRenderMarkup($text) {
    return [
      '#theme' => 'markup',
      '#markup' => $text,
    ];
  }

}
