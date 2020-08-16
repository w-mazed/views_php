<?php

/**
 * @file
 * Contains \Drupal\views_php\Plugin\views\cache\ViewsPhp.
 */

namespace Drupal\views_php\Plugin\views\cache;
use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Caching of query results for Views displays based on custom PHP code.
 *
 * @ingroup views_cache_plugins
 *
 * @ViewsCache(
 *   id = "views_php",
 *   title = @Translation("PHP"),
 *   help = @Translation("Use PHP code to determine whether a should be cached.")
 * )
 */
class ViewsPhp extends CachePluginBase {

  /**
   * Overrides Drupal\views\Plugin\Plugin::$usesOptions.
   */
  protected $usesOptions = TRUE;

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->t('PHP');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['php_cache_results'] = array('default' => '');
    $options['php_cache_output'] = array('default' => '');

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form += views_php_form_element($this,
      FALSE,
      array('php_cache_results', t('Result cache code'), t('The code must return TRUE if the cache is still fresh, FALSE otherwise.'), FALSE),
      array('$view', '$plugin', '$cache')
    );
    $form += views_php_form_element($this,
      FALSE,
      array('php_cache_output', t('Output cache code'), t('The code must return TRUE if the cache is still fresh, FALSE otherwise.'), FALSE),
      array('$view', '$plugin', '$cache')
    );

    $form['#attached']['library'][] = 'views_php/drupal.views_php';
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $options = $form_state->getValue('options');
    $form_state->setValue('cache_options', $options);
  }

  /**
   * {@inheritdoc}
   */
  public function cacheGet($type) {
    switch ($type) {
      case 'query':
        // Not supported currently, but this is certainly where we'd put it.
        return FALSE;

      case 'results':
        $cache = \Drupal::cache($this->resultsBin)->get($this->generateResultsKey());
        $fresh = !empty($cache);
        if ($fresh && !empty($this->options['php_cache_results'])) {
            $function = views_php_create_function('$view, $plugin, $cache', $this->options['php_cache_results'] . ';');
          ob_start();
          $fresh = $function($this->view, $this, $cache);
          ob_end_clean();
        }
        // Values to set: $view->result, $view->total_rows, $view->execute_time,
        // $view->current_page.
        if ($fresh) {
          $this->view->result = $cache->data['result'];
          // Load entities for each result.
          $this->view->query->loadEntities($this->view->result);
          $this->view->total_rows = $cache->data['total_rows'];
          $this->view->setCurrentPage($cache->data['current_page']);
          $this->view->execute_time = 0;
          return TRUE;
        }
        return FALSE;

      case 'output':
        $cache = \Drupal::cache($this->outputBin)->get($this->generateOutputKey());
        $fresh = !empty($cache);
        if ($fresh && !empty($this->options['php_cache_output'])) {
            $function = views_php_create_function('$view, $plugin, $cache', $this->options['php_cache_output'] . ';');
          ob_start();
          $fresh = $function($this->view, $this, $cache);
          ob_end_clean();
        }
        if ($fresh) {
          $this->storage = $cache->data;
          $this->view->display_handler->output = $this->storage;
          $this->view->element['#attached'] = &$this->view->display_handler->output['#attached'];
          $this->view->element['#cache']['tags'] = &$this->view->display_handler->output['#cache']['tags'];
          $this->view->element['#post_render_cache'] = &$this->view->display_handler->output['#post_render_cache'];
          return TRUE;
        }
        return FALSE;
    }
  }
}
