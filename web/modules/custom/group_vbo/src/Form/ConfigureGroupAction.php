<?php

namespace Drupal\group_vbo\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views_bulk_operations\Form\ConfigureAction;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface;

/**
 * Group Action configuration form.
 */
class ConfigureGroupAction extends ConfigureAction {

  /**
   * The tempstore service.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Views Bulk Operations action manager.
   *
   * @var \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager
   */
  protected $actionManager;

  /**
   * Views Bulk Operations action processor.
   *
   * @var \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface
   */
  protected $actionProcessor;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   User private temporary storage factory.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager $actionManager
   *   Extended action manager object.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface $actionProcessor
   *   Views Bulk Operations action processor.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(
    PrivateTempStoreFactory $tempStoreFactory,
    ViewsBulkOperationsActionManager $actionManager,
    ViewsBulkOperationsActionProcessorInterface $actionProcessor,
    RouteMatchInterface $route_match
  ) {
    $this->tempStoreFactory = $tempStoreFactory;
    $this->actionManager = $actionManager;
    $this->actionProcessor = $actionProcessor;
    $this->routeMatch = $route_match;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('plugin.manager.views_bulk_operations_action'),
      $container->get('views_bulk_operations.processor'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_vbo_configure_action';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_data = $form_state->get('views_bulk_operations');

    $action = $this->actionManager->createInstance($form_data['action_id']);
    if (method_exists($action, 'submitConfigurationForm')) {
      $action->submitConfigurationForm($form, $form_state);
      $form_data['configuration'] = $action->getConfiguration();
    }
    else {
      $form_state->cleanValues();
      $form_data['configuration'] = $form_state->getValues();
    }

    $definition = $this->actionManager->getDefinition($form_data['action_id']);
    if (!empty($definition['confirm_form_route_name'])) {
      // Update tempStore data.
      $this->setTempstoreData($form_data, $form_data['view_id'], $form_data['display_id']);
      // Go to the confirm route.
      $form_state->setRedirect($definition['confirm_form_route_name'], [
        'group' => $this->routeMatch->getParameter('group')->id(),
        'view_id' => $form_data['view_id'],
        'display_id' => $form_data['display_id'],
      ]);
    }
    else {
      $this->deleteTempstoreData($form_data['view_id'], $form_data['display_id']);
      $this->actionProcessor->executeProcessing($form_data);
      $form_state->setRedirectUrl($form_data['redirect_url']);
    }
  }

}
