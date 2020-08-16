<?php

/**
 * @file
 * Definition of Drupal\views_php\Plugin\views\sort\ViewsPhp.
 */

namespace Drupal\views_php\Plugin\views\sort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\sort\SortPluginBase;
use Drupal\views_php\ViewsPhpNormalizedRow;

/**
 * A handler to sort a view using PHP defined by the administrator.
 *
 * @ViewsSort("views_php")
 */
class ViewsPhp extends SortPluginBase {

  protected $php_static_variable = NULL;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['use_php_setup'] = array('default' => FALSE);
    $options['php_setup'] = array('default' => '');
    $options['php_sort'] = array('default' => '');

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form += views_php_form_element($this,
      array('use_php_setup', t('Use setup code'), t('If checked, you can provide PHP code to be run once right before execution of the view. This may be useful to define functions to be re-used in the value and/or output code.')),
      array('php_setup', t('Setup code'), t('Code to run right before execution of the view.'), FALSE),
      array('$view', '$handler', '$static')
    );
    $form += views_php_form_element($this,
      FALSE,
      array('php_sort', t('Sort code'), t('The comparison code must return an integer less than, equal to, or greater than zero if the first row should respectively appear before, stay where it was compared to, or appear after the second row.'), FALSE),
      array(
        '$view', '$handler', '$static',
        '$row1' => t('Data of row.'),
        '$row2' => t('Data of row to compare against.'),
      )
    );

    $form['#attached']['library'][] = 'views_php/drupal.views_php';
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Inform views_php_views_pre_execute() to seize control over the query.
    $this->view->views_php = TRUE;
  }

  /**
   *
   * @see views_php_views_pre_execute()
   */
  function phpPreExecute() {
    // Execute static PHP code.
    if (!empty($this->options['php_setup'])) {
        $function = views_php_create_function('$view, $handler, &$static', $this->options['php_setup'] . ';');
      ob_start();
      $function($this->view, $this, $this->php_static_variable);
      ob_end_clean();
    }
  }

  /**
   *
   * @see views_php_views_post_execute()
   */
  function phpPostExecute() {
    if (!empty($this->options['php_sort']) && $this->view->style_plugin->buildSort()) {
        $this->php_sort_function = views_php_create_function('$view, $handler, &$static, $row1, $row2', $this->options['php_sort'] . ';');
      ob_start();
      usort($this->view->result, array($this, 'phpSort'));
      ob_end_clean();
    }
  }

  /**
   * Helper function; usort() callback for sort support.
   */
  function phpSort($row1, $row2) {
    $factor = strtoupper($this->options['order']) == 'ASC' ? 1 : -1;
    $function = $this->php_sort_function;
    foreach (array('row1' => 'normalized_row1', 'row2' => 'normalized_row2') as $name => $normalized_name) {
      $$normalized_name = new ViewsPhpNormalizedRow();
      foreach ($this->view->display_handler->getHandlers('field') as $field => $handler) {
        $$normalized_name->$field = isset($$name->{$handler->field_alias}) ? $$name->{$handler->field_alias} : NULL;
      }
    }
    $result = (int)$function($this->view, $this, $this->php_static_variable, $normalized_row1, $normalized_row2);
    return $factor * $result;
  }
}
