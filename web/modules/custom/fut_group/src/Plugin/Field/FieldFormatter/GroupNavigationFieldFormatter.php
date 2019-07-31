<?php

namespace Drupal\fut_group\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;

/**
 * Plugin implementation of the 'group_navigation_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "fut_group_navigation_field_formatter",
 *   label = @Translation("Group navigation field formatter"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class GroupNavigationFieldFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $links = [];
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      if ($entity->bundle() == 'fut_functional_navigation_link') {
        $type = $entity->fut_predefined_link->value;
        $label = $entity->fut_label->value;
        $route = NULL;
        switch ($type) {
          case 'about':
            if (empty($label)) {
              $label = $this->t('About');
            }
            $route = 'fut_group.about';

            break;
          case 'events':
            if (empty($label)) {
              $label = $this->t('Events');
            }
            $route = 'view.fut_group_events.page_group_events';
            break;

          case 'posts':
            if (empty($label)) {
              $label = $this->t('Posts');
            }
            $route = 'view.fut_group_posts.page_group_posts';
            break;

          case 'library':
            if (empty($label)) {
              $label = $this->t('Library');
            }
            $route = 'view.fut_group_library.page_group_library';
            break;
        }

        if (!empty($route)) {
          $route_params = [
            'group' => $entity->getParentEntity()->id(),
          ];

          $collection = $entity->fut_collection_item->target_id;
          if (!empty($collection)) {
            $route_params['collection'] = $collection;
          }
          $links[] = $this->getLink($label, Url::fromRoute($route, $route_params)->toString());
        }

      }
      else {
        $links[] = $this->getLink($entity->fut_link->first()->title, $entity->fut_link->first()->getUrl()->toString());
      }
    }

    $elements[0] = [
      '#theme' => 'group_navigation',
      '#links' => $links,
    ];
    return $elements;
  }

  private function getLink($title, $url) {
    $std = new \stdClass();
    $std->title = $title;
    $std->url = $url;
    return $std;
  }

}
