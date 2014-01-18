<?php

/**
 * @file
 * Contains \Drupal\login_history\Controller\LoginHistoryController.
 */

namespace Drupal\login_history\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;

/**
 * Controller routines for Login history routes.
 */
class LoginHistoryController extends ControllerBase {

  /**
   * Displays a report of user logins.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function report(UserInterface $user = NULL) {
    $header = array(
      array('data' => t('Date'), 'field' => 'lh.login', 'sort' => 'desc'),
      array('data' => t('Username'), 'field' => 'u.name'),
      array('data' => t('IP Address'), 'field' => 'lh.hostname'),
      array('data' => t('One-time login?'), 'field' => 'lh.one_time'),
      array('data' => t('User Agent'), 'field' => 'lh.user_agent'),
    );

    $query = db_select('login_history', 'lh')
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');

    $query->join('users', 'u', 'lh.uid = u.uid');

    if ($user) {
      $query->condition('lh.uid', $user->id());
    }

    $result = $query
      ->fields('lh')
      ->fields('u', array('name'))
      ->orderByHeader($header)
      ->limit(50)
      ->execute()
      ->fetchAll();

    return $this->generateReport($result, 'table', $header);
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
  function generateReport(array $history, $format = 'table', array $header = array()) {
    switch ($format) {
      case 'text':
        // Output delimiter in first line, since this may change.
        $output = '\t' . "\n";

        foreach ($history as $entry) {
          $one_time = empty($entry->one_time) ? t('Regular login') : t('One-time login');
          $row = array(
            format_date($entry->login, 'small'),
            check_plain(format_username($entry->uid)),
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
          $output .= '<span class="login-history-info">' . check_plain(format_username($entry)) . ' ' . format_date($entry->login, 'small') . ' ' . check_plain($entry->hostname) . ' ' . $one_time . ' ' . check_plain($entry->user_agent) . '</span>';
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
            check_plain(format_username($entry)),
            check_plain($entry->hostname),
            empty($entry->one_time) ? t('Regular login') : t('One-time login'),
            check_plain($entry->user_agent),
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

}
