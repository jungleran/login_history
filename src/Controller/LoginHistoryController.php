<?php

/**
 * @file
 * Contains \Drupal\login_history\Controller\LoginHistoryController.
 */

namespace Drupal\login_history\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for Login history routes.
 */
class LoginHistoryController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a \Drupal\login_history\Controller\LoginHistoryController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *    The date formatter service.
   *  @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(DateFormatterInterface $date_formatter, Connection $database) {
    $this->dateFormatter = $date_formatter;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('database')
    );
  }

  /**
   * Displays a report of user logins.
   *
   * @param \Drupal\user\UserInterface $user
   *   (optional) The user to display for individual user reports.
   *
   * @return array
   *   A render array.
   */
  public function report(UserInterface $user = NULL) {
    $header = [
      ['data' => t('Date'), 'field' => 'lh.login', 'sort' => 'desc'],
      ['data' => t('Username'), 'field' => 'ufd.name'],
      ['data' => t('IP Address'), 'field' => 'lh.hostname'],
      ['data' => t('One-time login?'), 'field' => 'lh.one_time'],
      ['data' => t('User Agent')],
    ];

    $query = $this->database->select('login_history', 'lh')
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');

    $query->join('users', 'u', 'lh.uid = u.uid');
    $query->join('users_field_data', 'ufd', 'u.uid = ufd.uid');

    if ($user) {
      $query->condition('lh.uid', $user->id());
    }

    $result = $query
      ->fields('lh')
      ->fields('u', ['uid'])
      ->fields('ufd', ['name'])
      ->orderByHeader($header)
      ->limit(50)
      ->execute()
      ->fetchAll();

    return $this->generateReportTable($result, $header);
  }

  /**
   * Renders login histories as a table.
   *
   * @param array $history
   *   A list of login history objects to output.
   * @param array $header
   *   An array containing table header data.
   *
   * @return array
   *   A table render array.
   */
  function generateReportTable(array $history, array $header) {
    // Load all users first.
    $uids = [];
    foreach ($history as $entry) {
      $uids[] = $entry->uid;
    }
    $users = User::loadMultiple($uids);

    $rows = [];
    foreach ($history as $entry) {
      $rows[] = [
        $this->dateFormatter->format($entry->login, 'small'),
        $users[$entry->uid]->getAccountName(),
        $entry->hostname,
        empty($entry->one_time) ? t('Regular login') : t('One-time login'),
        $entry->user_agent,
      ];
    }
    $output['history'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No login history available.'),
    ];
    $output['pager'] = [
      '#type' => 'pager',
    ];

    return $output;
  }

  /**
   * Checks access for the user login report.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user to check access for.
   */
  public function checkUserReportAccess(UserInterface $user = NULL) {
    // Allow access if the user is viewing their own report and has permission
    // or if the user has permission to view all login history reports.
    $access = ($user->id() == $this->currentUser()->id() && $this->currentUser->hasPermission('view own login history'))
      || $this->currentUser->hasPermission('view all login histories');
    return AccessResult::allowedIf($access);
  }

}
