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
  public static function defaultSettings() {
    return [
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

//    var_dump($items);
    $links = [];
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      if ($entity->bundle() == 'fut_functional_navigation_link') {
        $type = $entity->fut_predefined_link->value;
        $group_id = $entity->getParentEntity()->id();
        switch ($type) {
          case 'posts':
            $links[] = $this->getLink($this->t('Events'), Url::fromRoute('view.fut_group_posts.page_group_posts', [
              'group' => $group_id
            ])->toString());
            break;

          case 'events':
            $links[] = $this->getLink($this->t('Posts'), Url::fromRoute('view.fut_group_events.page_group_events', [
              'group' => $group_id
            ])->toString());
            break;

          case 'library':
            $links[] = $this->getLink($this->t('Library'), Url::fromRoute('view.fut_group_events.page_group_library', [
              'group' => $group_id
            ])->toString());
            break;
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
