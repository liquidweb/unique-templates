<?php
/**
 * Tests for the template naming feature.
 *
 * @package LiquidWeb\UniqueTemplates
 * @author  Liquid Web
 */

namespace LiquidWeb\Tests;

use LiquidWeb\UniqueTemplates\Theme;
use ReflectionMethod;
use WP_UnitTestCase;

class TemplateNamingTest extends WP_UnitTestCase {

	/**
	 * @testWith ["index.php", "Default template"]
	 *           ["page.php", "Single page template"]
	 *           ["single.php", "Single post template"]
	 *           ["singular.php", "Singular post template"]
	 *           ["home.php", "Homepage"]
	 *           ["front-page.php", "Front page"]
	 *           ["category.php", "Category archive"]
	 *           ["tag.php", "Tag archive"]
	 *           ["archive.php", "Archive template"]
	 *           ["author.php", "Author archive"]
	 *           ["date.php", "Date-based archive"]
	 *           ["search.php", "Search results"]
	 *           ["404.php", "404 page"]
	 *           ["attachment.php", "Attachment template"]
	 *           ["embed.php", "Embed template"]
	 */
	public function test_default_templates( $filename, $expected ) {
		$this->assertEquals( $expected, $this->name_template( $filename ) );
	}

	public function test_single_templates_with_core_post_types() {
		$this->assertEquals(
			'Template for the page post with slug "foo-bar"',
			$this->name_template( 'single-page-foo-bar.php' ),
			'Failed to match single-{post-type}-{slug}.php.'
		);

		$this->assertEquals(
			'Template for single page posts',
			$this->name_template( 'single-page.php' ),
			'Failed to match single-{post-type}.php.'
		);
	}

	public function test_single_templates_with_custom_post_types() {
		register_post_type( 'my-cpt' );

		$this->assertEquals(
			'Template for the my-cpt post with slug "foo-bar"',
			$this->name_template( 'single-my-cpt-foo-bar.php' ),
			'Failed to match single-{post-type}-{slug}.php.'
		);

		$this->assertEquals(
			'Template for single my-cpt posts',
			$this->name_template( 'single-my-cpt.php' ),
			'Failed to match single-{post-type}.php.'
		);
	}

	public function test_page_templates() {
		$this->assertEquals(
			'Template for page with slug "some-page"',
			$this->name_template( 'page-some-page.php' ),
			'Failed to match page-{slug}.php.'
		);

		$this->assertEquals(
			'Template for page with ID 42',
			$this->name_template( 'page-42.php' ),
			'Failed to match page-{id}.php.'
		);
	}

	public function test_category_templates() {
		$this->assertEquals(
			'Archive for category with slug "some-term"',
			$this->name_template( 'category-some-term.php' ),
			'Failed to match category-{slug}.php.'
		);

		$this->assertEquals(
			'Archive for category with ID 42',
			$this->name_template( 'category-42.php' ),
			'Failed to match category-{id}.php.'
		);
	}

	public function test_tag_templates() {
		$this->assertEquals(
			'Archive for tag with slug "some-term"',
			$this->name_template( 'tag-some-term.php' ),
			'Failed to match tag-{slug}.php.'
		);

		$this->assertEquals(
			'Archive for tag with ID 42',
			$this->name_template( 'tag-42.php' ),
			'Failed to match tag-{id}.php.'
		);
	}

	public function test_taxonomy_templates() {
		register_taxonomy( 'my-tax', 'post' );

		$this->assertEquals(
			'Archive for my-tax term with slug "my-term"',
			$this->name_template( 'taxonomy-my-tax-my-term.php' ),
			'Failed to match taxonomy-{taxonomy}-{slug}.php.'
		);

		$this->assertEquals(
			'Archive for my-tax terms',
			$this->name_template( 'taxonomy-my-tax.php' ),
			'Failed to match taxonomy-{my-tax}.php.'
		);
	}

	public function test_post_type_templates() {
		register_post_type( 'my-cpt' );

		$this->assertEquals(
			'Post type archive for my-cpt',
			$this->name_template( 'archive-my-cpt.php' ),
			'Failed to match archive-{post-type}.php.'
		);
	}

	public function test_author_templates() {
		$author = $this->factory()->user->create_and_get();

		$this->assertEquals(
			sprintf( 'Archive for author "%1$s"', $author->user_nicename ),
			$this->name_template( sprintf( 'author-%s.php', $author->user_nicename ) ),
			'Failed to match author-{slug}.php.'
		);

		$this->assertEquals(
			sprintf( 'Archive for author with ID %1$d', $author->ID ),
			$this->name_template( sprintf( 'author-%d.php', $author->ID ) ),
			'Failed to match author-{id}.php.'
		);
	}

	/**
	 * @testWith ["image.php", "image"]
	 *           ["image-jpeg.php", "image/jpeg"]
	 *           ["jpeg.php", "image/jpeg"]
	 *           ["image-gif.php", "image/gif"]
	 *           ["video.php", "video"]
	 *           ["pdf.php", "application/pdf"]
	 *           ["application-pdf.php", "application/pdf"]
	 */
	public function test_mime_templates( $filename, $expected ) {
		$this->assertEquals(
			"Template for $expected MIME-type attachments",
			$this->name_template( $filename )
		);
	}

	/**
	 * WordPress has special handling for text/plain MIME types.
	 *
	 * @testWith ["plain.php"]
	 *           ["text-plain.php"]
	 */
	public function test_plain_text_templates( $filename ) {
		$this->assertEquals(
			"Template for plain-text attachments",
			$this->name_template( $filename )
		);
	}

	public function test_embed_templates() {
		$author = $this->factory()->user->create_and_get();

		$this->assertEquals(
			'Template for embedding post posts with the quote format',
			$this->name_template( 'embed-post-quote.php' ),
			'Failed to match embed-{post-type}-{post-format}.php.'
		);

		$this->assertEquals(
			'Template for embedding post posts',
			$this->name_template( 'embed-post.php' ),
			'Failed to match embed-{post-type}.php.'
		);
	}

	public function test_named_templates() {
		$this->markTestIncomplete();
	}

	public function test_theme_compatibility_templates() {
		$this->markTestIncomplete();
	}

	protected function name_template( $filename ) {
		$instance = new Theme();
		$method   = new ReflectionMethod( $instance, 'name_template' );
		$method->setAccessible( true );

		return $method->invoke( $instance, $filename );
	}
}
