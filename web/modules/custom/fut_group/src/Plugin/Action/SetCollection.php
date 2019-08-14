<?php

namespace Drupal\fut_group\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsPreconfigurationInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Set Collection Action.
 *
 * @Action(
 *   id = "fut_group_vbo_set_colleciton",
 *   label = @Translation("Set Collection"),
 *   type = "",
 *   confirm_form_route_name = "group_vbo.views_bulk_operations.confirm",
 * )
 */
class SetCollection extends ViewsBulkOperationsActionBase implements ViewsBulkOperationsPreconfigurationInterface, PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $collection = $this->configuration['collection_to_apply'] == '_none' ? NULL : $this->configuration['collection_to_apply'];
    $entity->set('fut_collection', $collection);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state) {
    return [];
  }

  /**
   * Configuration form builder.
   *
   * If this method has implementation, the action is
   * considered to be configurable.
   *
   * @param array $form
   *   Form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The configuration form.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['collection_to_apply'] = [
      '#type' => 'select',
      '#title' => $this->t('Collection'),
      '#description' => $this->t('The collection to be applied on selected posts.'),
      '#options' => _fut_group_get_group_options(),
    ];


    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return TRUE;
  }

}
