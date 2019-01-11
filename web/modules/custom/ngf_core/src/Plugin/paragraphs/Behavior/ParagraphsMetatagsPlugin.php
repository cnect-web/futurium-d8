<?php

namespace Drupal\ngf_core\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Metatag plugin.
 *
 * @see paragraphs/tests/modules/paragraphs_test/src/Plugin/paragraphs/Behavior/TestTextColorBehavior.php
 *
 * @ParagraphsBehavior(
 *   id = "metatag",
 *   label = @Translation("Metatag"),
 *   description = @Translation("Paragraphs Metatag plugin."),
 *   weight = 1
 * )
 */
class ParagraphsMetatagsPlugin extends ParagraphsBehaviorBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $default = $paragraph->getBehaviorSetting($this->getPluginId(), 'metatag_role');

    $form['metatag_role'] = [
      '#title' => t('Role'),
      '#type' => 'select',
      '#options' => $this->getPossibleRoles($paragraph->bundle()),
      '#default_value' => $default,
    ];
    return $form;
  }

  public function getPossibleRoles($bundle) {
    switch($bundle) {
      case 'ngf_text':
        return [
          '_none' => t('None'),
          'og:title' => 'Title',
          'og:description' => 'Description',
        ];
        break;
      case 'ngf_media':
        return [
          '_none' => t('None'),
          'og:image' => 'Image',
        ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $values = $this->filterBehaviorFormSubmitValues($paragraph, $form, $form_state);
    $paragraph->setBehaviorSettings($this->getPluginId(), $values);
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph) {
  }

}
