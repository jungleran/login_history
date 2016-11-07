<?php

/**
 * @file
 * Documentation for Login History module API.
 */

/**
 *
 * Allows modules to do additional detection of a user when they log in.
 *
 * Note: data added here will be part of the device id. If a site adds or removes the set of
 * detections that are performed, the old device id hashes will no longer be valid.
 *
 * @param array $detection
 *   An array of key->value strings that describe a login. Includes user_agent
 *   at the start. Modules can add data.
 * @param array $edit
 *   The $edit data from hook_user_login().
 * @param $account
 *   The user account that is logging in. Implementers should take care not
 *   to modify this data.
 */
function hook_login_history_detect_device_alter($detection, $edit, $account) {
  $detection['my_module_data'] = my_module_detection_function($account, $edit);
}

/**
 * Allow modules to react to the login history data.
 *
 * @param int $login_id
 *   The primary key login_id of login_history.
 * @param array $detection
 *   The array of all detections for this login.
 * @param string $device_id
 *   The current device ID.
 * @param string $old_device_id
 *   The old device ID from the last login.
 * @param object $account
 *   The user object for the user logging in to the site.
 */
function hook_login_history_detection_results($login_id, $detection, $old_device_id, $device_id, $account) {

}
