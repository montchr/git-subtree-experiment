<?php
/**
 * Publish to Apple News Tests: Metadata_Test class
 *
 * @package Apple_News
 * @subpackage Tests
 */

/**
 * A class which is used to test the Apple_Exporter\Builders\Metadata class.
 *
 * @package Apple_News
 * @subpackage Tests
 */
class Metadata_Test extends Apple_News_Testcase {

	/**
	 * A data provider for the test_video function.
	 *
	 * @return array An array of arrays representing function arguments.
	 */
	public function data_video() {
		return [
			[
				'https://example.com/wp-content/uploads/2017/02/example-poster.jpg',
				'https://example.com/wp-content/uploads/2017/02/example-video.mp4',
			],
			[
				'https://example.com/wp-content/uploads/2017/02/example-poster.jpg',
				'https://example.com/wp-content/uploads/2017/02/example-video.m3u8',
			],
		];
	}

	/**
	 * Ensures authors are properly added using Co-Authors Plus.
	 *
	 * @access public
	 */
	public function test_cap_authors() {
		// Setup.
		$this->enable_coauthors_support();
		global $apple_news_coauthors;
		$apple_news_coauthors = [ 'Test Author 1', 'Test Author 2' ];
		$author   = self::factory()->user->create( [ 'display_name' => 'Test Author' ] );
		$post_id  = self::factory()->post->create( [ 'post_author'  => $author ] );
		$result   = $this->get_json_for_post( $post_id );
		$metadata = $result['metadata'];

		// Assertions.
		$this->assertEquals(
			[ 'Test Author 1', 'Test Author 2' ],
			$metadata['authors']
		);

		// Cleanup.
		$apple_news_coauthors = [];
		$this->disable_coauthors_support();
	}

	/**
	 * Ensures that metadata is properly set.
	 */
	public function test_metadata() {
		// Setup.
		$author  = self::factory()->user->create( [ 'display_name' => 'Test Author' ] );
		$post_id = self::factory()->post->create(
			[
				'post_author'  => $author,
				'post_content' => '<p>Hello, World!</p>',
				'post_date'    => '2016-04-01 00:00:00',
				'post_excerpt' => 'Sample excerpt.',
				'post_title'   => 'My Title',
			]
		);
		$image   = $this->get_new_attachment( $post_id );
		set_post_thumbnail( $post_id, $image );
		$result   = $this->get_json_for_post( $post_id );
		$metadata = $result['metadata'];

		// Assertions.
		$this->assertEquals(
			[ 'Test Author' ],
			$metadata['authors']
		);
		$this->assertEquals(
			'2016-04-01T00:00:00+00:00',
			$metadata['dateCreated']
		);
		$this->assertEquals(
			'2016-04-01T00:00:00+00:00',
			$metadata['dateModified']
		);
		$this->assertEquals(
			'2016-04-01T00:00:00+00:00',
			$metadata['datePublished']
		);
		$this->assertEquals(
			'Sample excerpt.',
			$metadata['excerpt']
		);
		$this->assertEquals(
			wp_get_attachment_url( $image ),
			$metadata['thumbnailURL']
		);
	}

	/**
	 * Ensures video metadata is properly added.
	 *
	 * @param string $poster The URL to the poster image for the video.
	 * @param string $video  The URL to the video.
	 *
	 * @dataProvider data_video
	 */
	public function test_video( $poster, $video ) {
		// Setup.
		$post_id  = self::factory()->post->create(
			[
				'post_content' => '<figure class="wp-block-video"><video controls="" poster="' . $poster . '" src="' . $video . '"></video></figure>',
			]
		);
		$result   = $this->get_json_for_post( $post_id );
		$metadata = $result['metadata'];

		// Assertions.
		$this->assertEquals( $poster, $metadata['thumbnailURL'] );
		$this->assertEquals( $video, $metadata['videoURL'] );
	}
}
