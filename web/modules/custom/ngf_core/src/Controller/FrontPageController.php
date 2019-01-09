<?php

namespace Drupal\ngf_core\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\views\Views;
use Drupal\Core\Url;

/**
 * Front page controller.
 */
class FrontPageController extends ControllerBase {
  /**
   * Returns a render-able array for a test page.
   */
  public function anonymousFrontPage() {
    $block_manager = \Drupal::service('plugin.manager.block');
    $plugin_block = $block_manager->createInstance('user_login_block', []);

    $login_block = $plugin_block->build();
    $login_block['user_links']['#access'] = FALSE;

    // Taking submit button and div container out of the default drupal render array.
    $submit_copy = $login_block['user_login_form']['actions']['submit'];
    $actions_copy = $login_block['user_login_form']['actions'];
    // Need to add a specific class to align the log-in button.
    $actions_copy['#attributes']['class'] = array_merge($actions_copy['#attributes']['class'], ['btn-list--byside']);

    // Need to reset default drupal render array items as we have taken them out.
    unset($login_block['user_login_form']['actions']['submit']);
    unset($login_block['user_links']['#items']['create_account']);
    unset($login_block['user_login_form']['actions']);

    $login_block['user_login_form']['login_links_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'clearfix'
        ]
      ],
      '#weight' => 10,
    ];

    // Put retrieved submit button and div container into the outer wrapper.
    $login_block['user_login_form']['login_links_wrapper']['actions'] = $actions_copy;
    $login_block['user_login_form']['login_links_wrapper']['actions']['submit'] = $submit_copy;


    // Do the same as above for request password link.
    $reset_password_copy = $login_block['user_links']['#items']['request_password'];

    $login_block['user_login_form']['login_links_wrapper']['reset_password_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'btn-list--left',
          'btn-list--byside'
        ]
      ]
    ];

    $login_block['user_login_form']['login_links_wrapper']['reset_password_wrapper']['p_wrapper'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
    ];

    $login_block['user_login_form']['login_links_wrapper']['reset_password_wrapper']['p_wrapper']['reset_password'] = $reset_password_copy;

//    $login_block['user_login_form']['wrapper-messages'] = [
//      '#markup' => '<div id="messages-wrapper" data-drupal-selector="edit-wrapper-messages" class="js-form-wrapper form-wrapper"></div>',
//      '#weight' => -2,
//    ];
//
//    $login_block['user_login_form']['title'] = [
//      '#type' => 'html_tag',
//      '#tag' => 'h2',
//      '#value' => t("Login"),
//      '#suffix' => '<br>',
//      '#weight' => -1,
//    ];

    $render['login_form'] = $login_block;

    // 'Not registered...' text and register button - start.
    $render['message_wrapper'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
    ];

    $render['message_wrapper']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => t('Not registred yet ?'),
    ];

    $render['message_wrapper']['body_p1'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => t('Have an idea for Europe? Futuriumlab is an open platform dedicated to Europeans discussing EU matters. Please join and start discussions on this platform. Any topic relevant for European Union is welcome to be addressed here.'),
    ];

    $render['message_wrapper']['body_p2'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => t('The name “Futurium” refers to the times ahead us. The more we engage, the more we have impact on our future. Join us at Futuriumlab!'),
    ];

    $render['register_links_wrapper'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => [
          'btn-list--center',
        ]
      ]
    ];

    $url = Url::fromRoute('ngf_user_registration');
    $render['register_links_wrapper']['register_link'] = [
      '#type' => 'link',
      '#title' => t('Register'),
      '#url' => $url,
      '#attributes' => [
        'class' => [
          'btn btn--blue btn--large',
        ]
      ]
    ];
    // 'Not registered...' text and register button - start.

    // Feed text and button - start.
    $render['feed_wrapper']['body_feed_div'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => [
          'extra-space--top',
        ]
      ]
    ];

    $render['feed_wrapper']['feed_div']['feed_p'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#attributes' => [
        'class' => [
          'text-center',
        ]
      ],
      '#value' => t('Do you want to catch a glimpse of what\'s really going on here?'),
    ];

    $render['feed_link_wrapper'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => [
          'btn-list--center',
        ]
      ]
    ];

    $url = Url::fromRoute('ngf_user_registration');
    $render['feed_link_wrapper']['feed_link'] = [
      '#type' => 'link',
      '#title' => t('Check the public feed'),
      '#url' => $url,
      '#attributes' => [
        'class' => [
          'btn btn--grey btn--large',
        ]
      ]
    ];
    // Feed text and button - end.

    return $render;
  }

  public function  authenticatedFrontPage() {
    return ['#markup' => ''];
  }

  public function content() {
    if (\Drupal::currentUser()->isAnonymous()) {
      return $this->anonymousFrontPage();
    }
    else {
      return $this->authenticatedFrontPage();
    }
  }

}
