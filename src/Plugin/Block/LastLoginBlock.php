<?php

/**
 * @file
 * Contains \Drupal\login_history\Plugin\Block\LastLoginBlock.
 */

namespace Drupal\login_history\Plugin\Block;

use Drupal\Core\Session\AccountInterface;
use Drupal\block\BlockBase;

/**
 * Provides a block with information about the user's last login.
 *
 * @Block(
 *   id = "last_login_block",
 *   admin_label = @Translation("Last login"),
 *   category = @Translation("User"),
 * )
 */
class LastLoginBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $account->isAuthenticated();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      // @todo What is 'administrative' doing if anything?
      'properties' => array(
        'administrative' => TRUE,
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = array();
    if ($last_login = login_history_last_login()) {
      $request = \Drupal::request();
      $hostname = $last_login->hostname == $request->getClientIP() ? t('this IP address') : $last_login->hostname;
      $user_agent = $last_login->user_agent == $request->server->get('HTTP_USER_AGENT') ? t('this browser') : $last_login->user_agent;
      $build['last_login']['#markup'] = '<p>' . t('You last logged in from @hostname using @user_agent.', array('@hostname' => $hostname, '@user_agent' => $user_agent)) . '</p>';
      $user = \Drupal::currentUser();
      if ($user->hasPermission('view own login history')) {
        $build['view_report']['#markup'] = '<span class="read-more">' . l(t('View your login history'), 'user/'. $user->id() . '/login-history') . '</span>';
      }
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequiredCacheContexts() {
    // This block needs to be cached per user.
    return array('cache_context.user');
  }

}
