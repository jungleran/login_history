<?php

function login_history_report_callback() {
  $header = array(
    array('data' => t('Date'), 'field' => 'lh.login', 'sort' => 'desc'),
    array('data' => t('Username'), 'field' => 'u.name'),
  );

  $query = db_select('login_history', 'lh')->extend('TableSort')->extend('PagerDefault');
  $query->join('users', 'u', 'lh.uid = u.uid');
  $result = $query
    ->fields('lh')
    ->fields('u', array('name'))
    ->orderByHeader($header)
    ->limit(50)
    ->execute()
    ->fetchAll();

  return login_history_output($result, 'table', $header);
}

/**
 * Render login histories.
 * 
 * @param $history
 *   A list of login history objects to output.
 * @param $format
 *   (optional) The format to output log entries in; one of 'table', 'list' or
 *   'text'.
 * @param $header
 *   (optional) An array containing header data for $format 'table'.
 *
 * @todo Add XML output format.
 */
function login_history_output(array $history, $format = 'table', array $header = array()) {
  switch ($format) {
    case 'text':
      // Output delimiter in first line, since this may change.
      $output = '\t' . "\n";
      
      foreach ($history as $entry) {
        $row = array(
          format_date($entry->login, 'small'),
          format_username($entry->uid),
        );
        $output .= implode("\t", $row) . "\n";
      }
      break;
      
    case 'list':
      $output = '';
      foreach ($history as $entry) {
        $output .= '<li>';
        $output .= '<span class="login-history-info">' . format_username($entry) . ' ' . format_date($entry->login, 'small') . ':</span>';
        $output .= '</li>';
      }
      if ($output) {
        $output = '<ul id="login-history-backlog">' . $output . '</ul>';
      }
      break;
    
    case 'table':
    default:
      $rows = array();
      foreach ($history as $entry) {
        $rows[] = array(
          format_date($entry->login, 'small'),
          format_username($entry),
        );
      }
      $output['history'] = array(
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => t('No login history available.'),
      );
      $output['pager'] = array(
        '#theme' => 'pager',
      );
      break;
  }

  return $output;
}