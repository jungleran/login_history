<?php

/**
 * @file
 * Contains \Drupal\login_history\Controller\LoginHistoryController.
 */

namespace Drupal\login_history\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;

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
      array('data' => t('Username'), 'field' => 'ufd.name'),
      array('data' => t('IP Address'), 'field' => 'lh.hostname'),
      array('data' => t('One-time login?'), 'field' => 'lh.one_time'),
      array('data' => t('User Agent')),
    );

    $query = db_select('login_history', 'lh')
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');

    $query->join('users', 'u', 'lh.uid = u.uid');
    $query->join('users_field_data', 'ufd', 'u.uid = ufd.uid');

    if ($user) {
      $query->condition('lh.uid', $user->id());
    }

    $result = $query
      ->fields('lh')
      ->fields('u', array('uid'))
      ->fields('ufd', array('name'))
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
    // Load all users first.
    $uids = array();
    foreach ($history as $entry) {
      $uids[] = $entry->uid;
    }
    $users = User::loadMultiple($uids);

    switch ($format) {
      case 'text':
        // Output delimiter in first line, since this may change.
        $output = '\t' . "\n";

        foreach ($history as $entry) {
          $one_time = empty($entry->one_time) ? t('Regular login') : t('One-time login');
          $row = array(
            format_date($entry->login, 'small'),
            Html::escape($users[$entry->uid]->getUsername()),
            Html::escape($entry->hostname),
            empty($entry->one_time) ? t('Regular login') : t('One-time login'),
            Html::escape($entry->user_agent),
          );
          $output .= implode("\t", $row) . "\n";
        }
        break;

      case 'list':
        $output = '';
        foreach ($history as $entry) {
          $one_time = empty($entry->one_time) ? t('Regular login') : t('One-time login');
          $output .= '<li>';
          $output .= '<span class="login-history-info">' . Html::escape($users[$entry->uid]->getUsername()) . ' ' . format_date($entry->login, 'small') . ' ' . Html::escape($entry->hostname) . ' ' . $one_time . ' ' . Html::escape($entry->user_agent) . '</span>';
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
            $users[$entry->uid]->getUsername(),
            $entry->hostname,
            empty($entry->one_time) ? t('Regular login') : t('One-time login'),
            $entry->user_agent,
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

  /**
   * Checks access for the user login report.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request to check access for.
   */
  public function checkUserReportAccess(UserInterface $user = NULL) {
    // Allow access if the user is viewing their own report and has permission
    // or if the user has permission to view all login history reports.
    $access = ($user->id() == $this->currentUser()->id() && $this->currentUser->hasPermission('view own login history'))
      || $this->currentUser->hasPermission('view all login histories');
    return AccessResult::allowedIf($access);
  }

}
