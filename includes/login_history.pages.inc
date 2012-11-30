<?php

function login_history_report_callback() {
  if (empty($account->uid) && !user_access('view all login histories')) {
    // This should never happen, but be cautious in case calling code is weak.
    global $user;
    $account = $user;
  }
  $header = array(
    array('data' => t('Date'), 'field' => 'lh.login', 'sort' => 'desc'),
    array('data' => t('Username'), 'field' => 'u.name'),
    array('data' => t('IP Address'), 'field' => 'lh.hostname'),
    array('data' => t('One-time login?'), 'field' => 'lh.one_time'),
    array('data' => t('User Agent'), 'field' => 'lh.user_agent'),
  );

  $query = pager_query("SELECT lh.login, u.name, lh.hostname, lh.one_time, lh.user_agent FROM {login_history} lh INNER JOIN {users} u ON lh.uid = u.uid " . tablesort_sql($header), 50);

  $result = array();
  while ($row = db_fetch_object($query)) {
    $result[] = $row;
  }

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
        $one_time = empty($entry->one_time) ? t('Regular login') : t('One-time login');
        $row = array(
          format_date($entry->login, 'small'),
          check_plain($entry->name),
          check_plain($entry->hostname),
          empty($entry->one_time) ? t('Regular login') : t('One-time login'),
          check_plain($entry->user_agent),
        );
        $output .= implode("\t", $row) . "\n";
      }
      break;

    case 'list':
      $output = '';
      foreach ($history as $entry) {
        $one_time = empty($entry->one_time) ? t('Regular login') : t('One-time login');
        $output .= '<li>';
        $output .= '<span class="login-history-info">' . check_plain($entry->name) . ' ' . format_date($entry->login, 'small') . ' ' . check_plain($entry->hostname) . ' ' . $one_time . ' ' . check_plain($entry->user_agent) . '</span>';
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
          check_plain($entry->name),
          check_plain($entry->hostname),
          empty($entry->one_time) ? t('Regular login') : t('One-time login'),
          check_plain($entry->user_agent),
        );
      }
      if (!empty($rows)) {
        $output = theme('table', $header, $rows);
        $output .= theme('pager');
      }
      else {
        $output = t('No login history available.');
      }
      break;
  }

  return $output;
}
