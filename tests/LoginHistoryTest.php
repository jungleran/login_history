<?php

require_once dirname(__FILE__) . '/../login_history.inc';

class LoginHistoryCookieTest extends PHPUnit_Framework_TestCase {

  protected $salt;

  protected function setUp() {
    $this->salt = '43add6b85791792a729b1a05010d50f208d1f37680ff0034bac5d654a8d54c67';
  }

  /**
   * Test parsing some good cookies. Yum.
   */
  public function testParsingGoodCookies() {
    $test_good_values = [
      [
        'device_id' => '1426aed16098dc76268e242dd88e325fa9e96ed7b1e88fae65488eece5079cc5',
        'cookie' => [
          'Drupal_visitor_login_history' => '9f99d3be0c5524d6a551b00f1aee6b17ecb1d674ac83571d19093529b9b09736-1426aed16098dc76268e242dd88e325fa9e96ed7b1e88fae65488eece5079cc5-19',
        ],
      ],
      [
        'device_id' => '1426aed16098dc76268e242dd88e325fa9e96ed7b1e88fae65488eece5079cc5',
        'cookie' => [
          'Drupal_visitor_login_history' => '5b58eada6ac8e705e03b4c26c63800f1fdd88e3f93a2fefa04cff04db04ac951-1426aed16098dc76268e242dd88e325fa9e96ed7b1e88fae65488eece5079cc5-1',
        ],
      ]
    ];

    foreach ($test_good_values as $test_good_value) {
      $derived_device_id = login_history_get_device_id_from_cookie($test_good_value['cookie'], $this->salt);
      $this->assertEquals($test_good_value['device_id'], $derived_device_id, 'Valid cookie turns into derived device id');
    }

  }

  /**
   * No login history cookie present.
   */
  public function testMissingCookieException() {
    // The cookie array should contain Drupal_visitor_login_history.
    $this->setExpectedException('Exception', 'Login history device id not present.');
    login_history_get_device_id_from_cookie([], $this->salt);
  }

  /**
   * Cookie present, but invalid structure.
   */
  public function testInvalidCookie() {
    // The cookie should be 3 strings separated by hyphens.
    $this->setExpectedException('Exception', 'Invalid login history cookie data.');
    login_history_get_device_id_from_cookie(['Drupal_visitor_login_history' => '9-1'], $this->salt);
  }

  /**
   * All 3 elements are present, but fail basic sanity check for length.
   */
  public function testCookieDataStructure() {
    // The cookie should be 3 strings separated by hyphens.
    $this->setExpectedException('Exception', 'Login history cookie data not structured properly.');
    login_history_get_device_id_from_cookie(['Drupal_visitor_login_history' => '9-1-0'], $this->salt);
  }

  /**
   * Invalid hmac.
   */
  public function testCookieDataContents() {
    // The cookie should be 3 strings separated by hyphens.
    $this->setExpectedException('Exception', 'Invalid login history hmac');
    $derived_device_id = login_history_get_device_id_from_cookie(['Drupal_visitor_login_history' => '5b58eada6ac8e705e03b4c26c63800f1fdd88e3f93a2fefa04cff04db04ac952-1426aed16098dc76268e242dd88e325fa9e96ed7b1e88fae65488eece5079cc5-1'], $this->salt);
  }

}
