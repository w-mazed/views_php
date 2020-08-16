<?php

/**
 * @file
 * Definition of Drupal\views_php\Plugin\views\access\ViewsPhp.
 */

namespace Drupal\views_php\Plugin\views\access;

use Drupal\views\Plugin\views\access\AccessPluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides access based on PHP code.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "views_php",
 *   title = @Translation("PHP"),
 *   help = @Translation("Use PHP code to grant access.")
 * )
 */
class ViewsPhp extends AccessPluginBase {

  /**
   * Overrides Drupal\views\Plugin\Plugin::$usesOptions.
   */
  protected $usesOptions = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['php_access'] = array('default' => '');

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->t('PHP');
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form += views_php_form_element($this,
      FALSE,
      array('php_access', t('Access code'), t('If the code returns TRUE the requesting user is granted access to the view.'), FALSE),
      array(
        '$view_name' => t('The name of the view to check.'),
        '$display_id' => t('The ID of the display to check.'),
        '$account' => t('The account to check.'),
      )
    );

    $form['#attached']['library'][] = 'views_php/drupal.views_php';
  }

  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $options = $form_state->getValue('options');
    $form_state->setValue('access_options', $options);
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    if (!empty($this->options['php_access'])) {
      return views_php_check_access($this->options['php_access'], $this->view->id(), $this->view->current_display, $account);
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_access', 'TRUE');
  }

}
