<?php
/**
 * Publish to Apple News tests: Advertisement_Test class
 *
 * @package Apple_News
 * @subpackage Tests
 */

use Apple_Exporter\Components\Advertisement;

/**
 * A class to test the behavior of the
 * Apple_Exporter\Components\Advertisement class.
 *
 * @package Apple_News
 * @subpackage Tests
 */
class Advertisement_Test extends Component_TestCase {

	/**
	 * Tests basic JSON generation.
	 */
	public function testGeneratedJSON() {
		$component = new Advertisement(
			null,
			$this->workspace,
			$this->settings,
			$this->styles,
			$this->layouts
		);
		$json = $component->to_array();

		$this->assertEquals( 'banner_advertisement', $json['role'] );
		$this->assertEquals( 'standard', $json['bannerType'] );
	}

	/**
	 * Tests the behavior of the apple_news_advertisement_json filter.
	 */
	public function testFilter() {
		$component = new Advertisement(
			null,
			$this->workspace,
			$this->settings,
			$this->styles,
			$this->layouts
		);

		add_filter(
			'apple_news_advertisement_json',
			function( $json ) {
				$json['bannerType'] = 'double_height';
				return $json;
			}
		);

		$json = $component->to_array();
		$this->assertEquals( 'double_height', $json['bannerType'] );
	}
}
