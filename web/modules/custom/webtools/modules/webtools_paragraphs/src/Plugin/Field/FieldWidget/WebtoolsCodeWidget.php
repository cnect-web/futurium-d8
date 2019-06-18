<?php

namespace Drupal\webtools_paragraphs\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\webtools_paragraphs\WebtoolsCodeHelperTrait;
use Drupal\webtools_paragraphs\Ajax\RenderWebtools;

/**
 * Plugin implementation of the 'webtools_code_widget' widget.
 *
 * @FieldWidget(
 *   id = "webtools_code_widget",
 *   label = @Translation("Webtools code widget"),
 *   field_types = {
 *     "string_long"
 *   }
 * )
 */
class WebtoolsCodeWidget extends WidgetBase {

  use WebtoolsCodeHelperTrait;
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $preview_id = 'widget-preview-'.implode('-',$element["#field_parents"]);

    $element = $element + [
      'preview' => [
        '#markup' => '<div id="'.$preview_id.'"></div>',
      ],
      'value' => [
        '#title' => $element['#title'],
        '#description' => $element['#description'],
        '#type' => 'textarea',
        '#rows' => 20,
        '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
        '#field_parents' => $element["#field_parents"],
        '#ajax' => [
          'callback' => [$this, 'previewWebtools'],
          'event' => 'change',
        ],
        '#attached' => [
          'library' => [
            'webtools_paragraphs/webtools_paragraphs-render-webtools',
          ],
        ],
      ],
    ];

    return $element;
  }

  public function previewWebtools(array $form, FormStateInterface $form_state) {

    $value = $form_state->getTriggeringElement()['#value'];

    $response = new AjaxResponse();

    $element_id = '#widget-preview-' . implode('-',$form_state->getTriggeringElement()['#field_parents']);

    if ($this->isValidCode($value)) {

      $json = $this->extractJson($value);

      $css = [
        'border' => 'none',
        'color' => 'black',
        'padding' => '0px',
      ];
      $response->addCommand(new CssCommand($element_id, $css));
      $response->addCommand(new RenderWebtools($element_id,$json));
    }
    else {

      $css = [
        'border' => '2px solid red',
        'color' => 'red',
        'padding' => '5px',
      ];
      $message = $this->t('Provided code is not a Webtools valid Unified Embed Code.');
      $response->addCommand(new CssCommand($element_id, $css));
      $response->addCommand(new HtmlCommand($element_id, $message));
    }

    return $response;
   }

}
