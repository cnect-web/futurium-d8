<?php

namespace Drupal\ngf_content\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\ngf_core\Manager\HomepageManager;
use Drupal\views\Views;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;

/**
 * Front page controller.
 */
class ContentController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ngf_core.home_page_manager')
    );
  }

  /**
   * Returns a render-able array for a test page.
   */
  public function voters($node) {
    $nid = $node->id();
    $tabs = [
      [
        'title' => $this->t('All'),
        'class' => 'active',
        'content' => $this->getView('ngf_content_voters', 'block_all'),
        'more_link' => $this->buildLink('More voters', 'all', $nid)
      ],
      [
        'title' => $this->t('For'),
        'content' => $this->getView('ngf_content_voters', 'block_pluses'),
        'more_link' => $this->buildLink('More voters who have voted for', 'pluses', $nid)
      ],
      [
        'title' => $this->t('Against'),
        'content' => $this->getView('ngf_content_voters', 'block_minuses'),
        'more_link' => $this->buildLink('More voters who have voted against', 'minuses', $nid)
      ],
    ];

//    var_dump($tabs);

    return [
      '#theme' => 'ngf_tabs',
      '#tabs' => $tabs,
    ];
  }

  protected function buildLink($title, $display_name, $nid) {
    return Link::fromTextAndUrl($this->t($title), Url::fromRoute('view.ngf_content_voters.' . $display_name, ['arg_0' => $nid]))->toString();
  }

  protected function getView($view_name, $display_name) {
    // Add the view block.
    $view = Views::getView($view_name);
    $view->setDisplay($display_name);
    $view->preExecute();
    $view->execute();

    // Add the view to the render array.
    $view_content = $view->render();
    return \Drupal::service('renderer')->render($view_content);
  }
}