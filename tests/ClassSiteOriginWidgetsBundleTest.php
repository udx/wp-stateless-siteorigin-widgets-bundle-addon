<?php

namespace WPSL\SiteOriginWidgetsBundle;

use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use wpCloud\StatelessMedia\WPStatelessStub;

/**
 * Class ClassSiteOriginWidgetsBundleTest
 */

class ClassSiteOriginWidgetsBundleTest extends TestCase {
  public static $backtrace = [
    'file' => 'so-widgets-bundle',
    'function' => 'sanitize_file_name',
  ];

  const TEST_URL = 'https://test.test';
  const UPLOADS_URL = self::TEST_URL . '/uploads';
  const TEST_FILE = 'siteorigin-widgets/style.css';
  const SRC_URL = self::TEST_URL . '/' . self::TEST_FILE;
  const DST_URL = WPStatelessStub::TEST_GS_HOST . '/' . self::TEST_FILE;
  const TEST_UPLOAD_DIR = [
    'baseurl' => self::UPLOADS_URL,
    'basedir' => '/var/www/uploads'
  ];

  // Adds Mockery expectations to the PHPUnit assertions count.
  use MockeryPHPUnitIntegration;

  public function setUp(): void {
		parent::setUp();
		Monkey\setUp();

    self::$backtrace = [
      'file' => 'so-widgets-bundle',
      'function' => 'sanitize_file_name',
    ];

    // WP mocks
    Functions\when('wp_upload_dir')->justReturn( self::TEST_UPLOAD_DIR );
        
    // WP_Stateless mocks
    Filters\expectApplied('wp_stateless_file_name')
      ->andReturn( self::TEST_FILE );

    Functions\when('ud_get_stateless_media')->justReturn( WPStatelessStub::instance() );
  }
	
  public function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

  public function testShouldInitHooks() {
    $siteOriginWidgetsBundle = new SiteOriginWidgetsBundle();

    Actions\expectDone('sm:sync::register_dir')->once()->with('/siteorigin-widgets/');

    $siteOriginWidgetsBundle->module_init([]);

    self::assertNotFalse( has_filter('set_url_scheme', [ $siteOriginWidgetsBundle, 'set_url_scheme' ]) );
    self::assertNotFalse( has_filter('pre_set_transient_sow:cleared', [ $siteOriginWidgetsBundle, 'clear_file_cache' ]) );
    self::assertNotFalse( has_filter('siteorigin_widgets_sanitize_instance', [ $siteOriginWidgetsBundle, 'delete_file' ]) );
    self::assertNotFalse( has_filter('stateless_skip_cache_busting', [ $siteOriginWidgetsBundle, 'skip_cache_busting' ]) );
  }

  public function testShouldChangeUploadUrl() {
    $siteOriginWidgetsBundle = new SiteOriginWidgetsBundle();

    Actions\expectDone('sm:sync::syncFile')->once();

    $this->assertEquals(
      self::DST_URL,
      $siteOriginWidgetsBundle->set_url_scheme(self::SRC_URL, null, null) 
    );
  }

  public function testShouldClearFileCache() {
    $siteOriginWidgetsBundle = new SiteOriginWidgetsBundle();

    Actions\expectDone('sm:sync::deleteFiles')->once()->with('siteorigin-widgets/');

    $this->assertEquals(
      self::SRC_URL,
      $siteOriginWidgetsBundle->clear_file_cache(self::SRC_URL, null, null) 
    );
  }

  public function testShouldDeleteFile() {
    $siteOriginWidgetsBundle = new SiteOriginWidgetsBundle();

    $so_widget = new class {
      public $id_base = 'test';

      public function modify_instance($new_instance) {
        return $new_instance;
      }

      public function get_style_name($new_instance) {
        return 'style';
      }

      public function get_style_hash($new_instance) {
        return 'hash';
      }
    };

    $filename = 'siteorigin-widgets/' . $so_widget->id_base . '-style-hash.css';

    $this->assertEquals(
      null,
      $siteOriginWidgetsBundle->delete_file(null, null, $so_widget) 
    );
  }

  public function testShouldSkipCacheBusting() {
    $siteOriginWidgetsBundle = new SiteOriginWidgetsBundle();

    $this->assertEquals(
      self::TEST_FILE,
      $siteOriginWidgetsBundle->skip_cache_busting(null, self::TEST_FILE) 
    );
  }

  public function testShouldNotSkipCacheBusting() {
    $siteOriginWidgetsBundle = new SiteOriginWidgetsBundle();

    self::$backtrace = [];

    $this->assertEquals(
      null,
      $siteOriginWidgetsBundle->skip_cache_busting(null, self::TEST_FILE) 
    );
  }
}

function debug_backtrace() {
  return [ ClassSiteOriginWidgetsBundleTest::$backtrace ];
}