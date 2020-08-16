<?php

/**
 * @file
 * Definition of Drupal\views_php\Plugin\views\area\ViewsPhp.
 */

namespace Drupal\views_php\Plugin\views\area;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\area\TokenizeAreaPluginBase;

/**
 * Views area PHP text handler.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("views_php")
 */
class ViewsPhp extends TokenizeAreaPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['php_output'] = array('default' => "<h4>Example PHP code</h4>\n<p>Time: <?php print date('H:i', time());?></p>\n");
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form += views_php_form_element($this,
      FALSE,
      array('php_output', t('Output code'), t('Code to construct the output of this area.'), TRUE),
      array('$view', '$handler', '$results')
    );

    $form['#attached']['library'][] = 'views_php/drupal.views_php';
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {
      return array(
        '#markup' => $this->renderViewsPhp($this->options['content']),
      );
    }

    return array();
  }

  /**
   * Render a text area with PHP code.
   */
  public function renderViewsPhp($value = FALSE) {// Execute output PHP code.
    if ((!$value || !empty($this->options['empty'])) && !empty($this->options['php_output'])) {
        $function = views_php_create_function('$view, $handler, $results', ' ?>' . $this->options['php_output'] . '<?php ');
      ob_start();
      $function($this->view, $this, $this->view->result);
      return ob_get_clean();
    }
    return '';
  }

}

