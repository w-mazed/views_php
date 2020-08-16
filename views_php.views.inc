<?php

/**
 * @file
 * Provide views handlers that allow usage of PHP.
 */

/**
 * Implements hook_views_data().
 */
function views_php_views_data() {
  $data['views']['php'] = array(
    'title' => t('PHP'),
    'help' => t('Use PHP code.'),
    'area' => array(
      'id' => 'views_php',
    ),
    'field' => array(
      'id' => 'views_php',
    ),
    'filter' => array(
      'id' => 'views_php',
    ),
    'sort' => array(
      'id' => 'views_php',
    ),
  );

  return $data;
}


