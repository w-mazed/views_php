<?php

/**
 * @file
 * Contains \Drupal\views_php\Plugin\views\filter\ViewsPhp.
 */

namespace Drupal\views_php\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views_php\ViewsPhpNormalizedRow;

/**
 * A handler to filter a view using PHP defined by the administrator.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("views_php")
 */
class ViewsPhp extends FilterPluginBase {

  protected $php_static_variable = NULL;

  /**
   * {@inheritdoc}
   */
  function canExpose() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  function adminSummary() {
    return t('PHP');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['use_php_setup'] = array('default' => FALSE);
    $options['php_setup'] = array('default' => '');
    $options['php_filter'] = array('default' => '');

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
      array('php_filter', t('Filter code'), t('If the code returns TRUE the current row is removed from the results.'), FALSE),
      array('$view', '$handler', '$static', '$row', '$data')
    );

    $form['#attached']['library'][] = 'views_php/drupal.views_php';
  }

  /**
   * {@inheritdoc}
   */
  function query() {
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
    // Evaluate the PHP code.
    if (!empty($this->options['php_filter'])) {
        $function = views_php_create_function('$view, $handler, &$static, $row, $data', $this->options['php_filter'] . ';');
      ob_start();

      $normalized_row = new ViewsPhpNormalizedRow();
      foreach ($this->view->result as $i => $result) {
        foreach ($this->view->field as $id => $field) {
          $normalized_row->$id = $this->view->field[$id]->theme($result);
        }

        if ($function($this->view, $this, $this->php_static_variable, $normalized_row, $result)) {
          unset($this->view->result[$i]);
        }
      }
      ob_end_clean();
    }
  }

}

