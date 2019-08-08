<?php

namespace Drupal\group_vbo\Plugin\views\field;


use Drupal\Core\Form\FormStateInterface;

use Drupal\views_bulk_operations\Plugin\views\field\ViewsBulkOperationsBulkForm;

/**
 * Defines the Group compatible Views Bulk Operations field plugin.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("group_vbo_bulk_form")
 */
class GroupViewsBulkOperationsBulkForm extends ViewsBulkOperationsBulkForm {


  /**
   * Submit handler for the bulk form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsFormSubmit(array &$form, FormStateInterface $form_state) {
    if ($form_state->get('step') == 'views_form_views_form') {

      $action_id = $form_state->getValue('action');

      $action = $this->actions[$action_id];

      $this->tempStoreData['action_id'] = $action_id;
      $this->tempStoreData['action_label'] = empty($this->options['preconfiguration'][$action_id]['label_override']) ? (string) $action['label'] : $this->options['preconfiguration'][$action_id]['label_override'];
      $this->tempStoreData['relationship_id'] = $this->options['relationship'];
      $this->tempStoreData['preconfiguration'] = isset($this->options['preconfiguration'][$action_id]) ? $this->options['preconfiguration'][$action_id] : [];
      $this->tempStoreData['clear_on_exposed'] = $this->options['clear_on_exposed'];

      $configurable = $this->isActionConfigurable($action);

      // Get configuration if using AJAX.
      if ($configurable && empty($this->options['form_step'])) {
        $actionObject = $this->actionManager->createInstance($action_id);
        if (method_exists($actionObject, 'submitConfigurationForm')) {
          $actionObject->submitConfigurationForm($form, $form_state);
          $this->tempStoreData['configuration'] = $actionObject->getConfiguration();
        }
        else {
          $form_state->cleanValues();
          $this->tempStoreData['configuration'] = $form_state->getValues();
        }
      }

      // Update list data with the current page selection.
      if ($form_state->getValue('select_all')) {
        foreach ($form_state->getValue($this->options['id']) as $row_index => $bulkFormKey) {
          if ($bulkFormKey) {
            unset($this->tempStoreData['list'][$bulkFormKey]);
          }
          else {
            $row_bulk_form_key = $form[$this->options['id']][$row_index]['#return_value'];
            $this->tempStoreData['list'][$row_bulk_form_key] = $this->getListItem($row_bulk_form_key);
          }
        }
      }
      else {
        foreach ($form_state->getValue($this->options['id']) as $row_index => $bulkFormKey) {
          if ($bulkFormKey) {
            $this->tempStoreData['list'][$bulkFormKey] = $this->getListItem($bulkFormKey);
          }
          else {
            $row_bulk_form_key = $form[$this->options['id']][$row_index]['#return_value'];
            unset($this->tempStoreData['list'][$row_bulk_form_key]);
          }
        }
      }

      // Update exclude mode setting.
      if ($form_state->getValue('select_all') && !empty($this->tempStoreData['list'])) {
        $this->tempStoreData['exclude_mode'] = TRUE;
      }
      else {
        $this->tempStoreData['exclude_mode'] = FALSE;
      }

      // Routing - determine redirect route.
      //
      // Set default redirection due to issue #2952498.
      // TODO: remove the next line when core cause is eliminated.
      $redirect_route = 'group_vbo.views_bulk_operations.execute_batch';

      if ($this->options['form_step'] && $configurable) {
        $redirect_route = 'group_vbo.views_bulk_operations.execute_configurable';
      }
      elseif ($this->options['batch']) {
        if (!empty($action['confirm_form_route_name'])) {
          $redirect_route = $action['confirm_form_route_name'];
        }
      }
      elseif (!empty($action['confirm_form_route_name'])) {
        $redirect_route = $action['confirm_form_route_name'];
      }

      // Redirect if needed.
      if (!empty($redirect_route)) {
        $this->setTempstoreData($this->tempStoreData);



        $form_state->setRedirect($redirect_route, [
          'group' => $this->view->argument['gid']->value[0],
          'view_id' => $this->view->id(),
          'display_id' => $this->view->current_display,
        ]);
      }
      // Or process rows here and now.
      else {
        $this->deleteTempstoreData();
        $this->actionProcessor->executeProcessing($this->tempStoreData, $this->view);
        $form_state->setRedirectUrl($this->tempStoreData['redirect_url']);
      }
    }
  }

}
