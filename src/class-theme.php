<?php

namespace LiquidWeb\UniqueTemplates;

use WP_CLI\Utils as Utils;
use WP_CLI_Command;

class Theme extends WP_CLI_Command {

	/**
	 * A cache of named templates.
	 *
	 * @var array
	 */
	protected $named_templates;

	/**
	 * An array of directories from which templates can be loaded.
	 *
	 * @var array
	 */
	protected $template_paths;

	public function __construct() {
		$this->template_paths = array_map( 'trailingslashit', [
			get_stylesheet_directory(),
			get_template_directory(),
			ABSPATH . WPINC . '/theme-compat/',
		] );
	}

	/**
	 * List unique templates within a theme.
	 *
	 * ## OPTIONS
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields. Separate multiple values with a comma.
	 *
	 *   Available options are:
	 *   - all
	 *   - name
	 *   - filename
	 *   - url
	 * ---
	 * default: all
	 * ---
	 *
	 * [--orderby=<field>]
	 * : The field to order results by.
	 * ---
	 * default: filename
	 * ---
	 *
	 * [--order=<order>]
	 * : The sort order. Default is "asc".
	 * ---
	 * default: asc
	 * options:
	 *   - asc
	 *   - desc
	 * ---
	 */
	public function unique_templates( $args = [], $assoc_args = [] ) {
		$assoc_args = wp_parse_args( $assoc_args, [
			'fields'  => 'all',
			'orderby' => 'filename',
			'order'   => 'asc',
		] );
		$templates  = [];

		// Expand the fields parameter.
		if ( 'all' === $assoc_args['fields'] ) {
			$assoc_args['fields'] = 'name,filename,url';
		}
		$fields = array_intersect(
			explode( ',', $assoc_args['fields'] ),
			[ 'name', 'filename', 'url' ]
		);

		// Collect the list of templates.
		$template_files = $this->get_all_templates();

		// Collect additional details about each template, as necessary.
		foreach ( $template_files as $filename ) {
			$templates[] = [
				'name'     => in_array( 'name', $fields, true ) ? $this->name_template( $filename ) : null,
				'filename' => str_replace( $this->template_paths, '', $filename ),
				'url'      => null,
			];
		}

		// Order the results.
		$orderby = $assoc_args['orderby'];
		usort( $templates, function ( $a, $b ) use ( $orderby ) {
			return strcmp( $a[ $orderby ], $b[ $orderby ] );
		} );

		if ( 'desc' === $assoc_args['order'] ) {
			$templates = array_reverse( $templates );
		}

		Utils\format_items( 'table', $templates, $fields );
	}

	/**
	 * Find all templates within the current theme.
	 *
	 * @return array An array of template filenames, relative to the system root.
	 */
	protected function get_all_templates() {
		$template_files = [
			get_index_template(),
			get_404_template(),
			get_archive_template(),
			get_post_type_archive_template(),
			get_author_template(),
			get_category_template(),
			get_tag_template(),
			get_taxonomy_template(),
			get_date_template(),
			get_home_template(),
			get_front_page_template(),
			get_page_template(),
			get_search_template(),
			get_single_template(),
			get_embed_template(),
			get_singular_template(),
			get_attachment_template(),
		];

		// Create a single list of template files and filter it to unique files.
		return array_filter( array_unique( array_merge(
			$template_files,
			array_keys( $this->get_named_templates() )
		) ) );
	}

	/**
	 * Get a flat array of all named (e.g. containing the "Template Name" file header comment)
	 * templates in the current theme.
	 *
	 * The resulting value will be cached in $this->named_templates.
	 *
	 * @return array An array of template names, keyed by their absolute filepaths.
	 */
	protected function get_named_templates() {
		if ( empty( $this->named_templates ) ) {
			$this->named_templates = array_reduce( wp_get_theme()->get_post_templates(), function ( $templates, $group ) {
				return array_merge( $templates, $group );
			}, [] );
		}

		return $this->named_templates;
	}

	/**
	 * Given a filepath, determine the "friendly" name for a template.
	 *
	 * @param string $filepath The filename or path to evaluate.
	 *
	 * @return string A friendly name for the template.
	 */
	protected function name_template( $filepath ) {
		$filename        = str_replace( $this->template_paths, '', $filepath );
		$named_templates = $this->get_named_templates();
		$names           = [
			'index.php'                      => _x( 'Default template', 'template name', 'unique-templates' ),
			'home.php'                       => _x( 'Homepage', 'template name', 'unique-templates' ),
			'front-page.php'                 => _x( 'Front page', 'template name', 'unique-templates' ),
			'single.php'                     => _x( 'Single post template', 'template name', 'unique-templates' ),
			'singular.php'                   => _x( 'Singular post template', 'template name', 'unique-templates' ),
			'page.php'                       => _x( 'Single page template', 'template name', 'unique-templates' ),
			'category.php'                   => _x( 'Category archive', 'template name', 'unique-templates' ),
			'tag.php'                        => _x( 'Tag archive', 'template name', 'unique-templates' ),
			'taxonomy.php'                   => _x( 'Taxonomy archive', 'template name', 'unique-templates' ),
			'archive.php'                    => _x( 'Archive template', 'template name', 'unique-templates' ),
			'author.php'                     => _x( 'Author archive', 'template name', 'unique-templates' ),
			'date.php'                       => _x( 'Date-based archive', 'template name', 'unique-templates' ),
			'search.php'                     => _x( 'Search results', 'template name', 'unique-templates' ),
			'404.php'                        => _x( '404 page', 'template name', 'unique-templates' ),
			'attachment.php'                 => _x( 'Attachment template', 'template name', 'unique-templates' ),
			'plain.php'                      => _x( 'Template for plain-text attachments', 'template-name', 'unique-templates' ),
			'text-plain.php'                 => _x( 'Template for plain-text attachments', 'template-name', 'unique-templates' ),
			'embed.php'                      => _x( 'Embed template', 'template name', 'unique-templates' ),

			/* Translators: %1$s is the post type, %2$s is the post slug. */
			'single-{post-type}-{slug}.php'  => _x( 'Template for the %1$s post with slug "%2$s"', 'template name', 'unique-templates' ),

			/* Translators: %1$s is the post type. */
			'single-{post-type}.php' => _x( 'Template for single %1$s posts', 'template name', 'unique-templates' ),

			/* Translators: %1$s is the page slug. */
			'page-{slug}.php'                => _x( 'Template for page with slug "%1$s"', 'template name', 'unique-templates' ),

			/* Translators: %1$d is the page ID. */
			'page-{id}.php'                  => _x( 'Template for page with ID %1$d', 'template name', 'unique-templates' ),

			/* Translators: %1$s is the category slug. */
			'category-{slug}.php'            => _x( 'Archive for category with slug "%1$s"', 'template name', 'unique-templates' ),

			/* Translators: %1$d is the category ID. */
			'category-{id}.php'              => _x( 'Archive for category with ID %1$s', 'template name', 'unique-templates' ),

			/* Translators: %1$s is the tag slug. */
			'tag-{slug}.php'                 => _x( 'Archive for tag with slug "%1$s"', 'template name', 'unique-templates' ),

			/* Translators: %1$d is the tag ID. */
			'tag-{id}.php'                   => _x( 'Archive for tag with ID %1$s', 'template name', 'unique-templates' ),

			/* Translators: %1$s is the taxonomy, %2$s is the term slug. */
			'taxonomy-{taxonomy}-{slug}.php' => _x( 'Archive for %1$s term with slug "%2$s"', 'template name', 'unique-templates' ),

			/* Translators: %1$s is the taxonomy. */
			'taxonomy-{taxonomy}.php'        => _x( 'Archive for %1$s terms', 'template name', 'unique-templates' ),

			/* Translators: %1$s is the post type. */
			'archive-{post-type}.php'        => _x( 'Post type archive for %1$s', 'template name', 'unique-templates' ),

			/* Translators: %1$s is the author slug/nice-name. */
			'author-{slug}.php'              => _x( 'Archive for author "%1$s"', 'template name', 'unique-templates' ),

			/* Translators: %1$d is the author ID. */
			'author-{id}.php'                => _x( 'Archive for author with ID %1$d', 'template name', 'unique-templates' ),

			/* Translators: %1$s is the attachment MIME-type slug. */
			'{mime}.php'                     => _x( 'Template for %1$s MIME-type attachments', 'template name', 'unique-templates' ),

			/* Translators: %1$s is the post type, %2$s is the post format. */
			'embed-{post-type}-{slug}.php'  => _x( 'Template for embedding %1$s posts with the %2$s format', 'template name', 'unique-templates' ),

			/* Translators: %1$s is the post type. */
			'embed-{post-type}.php'          => _x( 'Template for embedding %1$s posts', 'template name', 'unique-templates' ),
		];

		// Exact matches.
		if ( isset( $names[ $filename ] ) ) {
			return $names[ $filename ];

		} elseif ( isset( $named_templates[ $filepath ] ) ) {
			return $named_templates[ $filepath ];
		}

		// Assemble a list of patterns to match against the filename.
		$prefixes = [
			'archive',
			'author',
			'attachment',
			'category',
			'page',
			'single',
			'singular',
			'tag',
			'taxonomy',
		];

		// Append post types and taxonomies.
		foreach ( get_post_types( null, 'names' ) as $post_type ) {
			$prefixes[] = 'single-' . $post_type;
			$prefixes[] = 'archive-' . $post_type;
			$prefixes[] = 'embed-' . $post_type;

			// Clone the labels for specific post types.
			$names['single-' . $post_type . '-{slug}.php'] = sprintf(
				$names['single-{post-type}-{slug}.php'],
				$post_type,
				'%1$s'
			);

			$names['single-' . $post_type . '.php'] = sprintf(
				$names['single-{post-type}.php'],
				$post_type
			);

			$names['archive-' . $post_type . '.php'] = sprintf(
				$names['archive-{post-type}.php'],
				$post_type
			);

			$names['embed-' . $post_type . '-{slug}.php'] = sprintf(
				$names['embed-{post-type}-{slug}.php'],
				$post_type,
				'%1$s'
			);

			$names['embed-' . $post_type . '.php'] = sprintf(
				$names['embed-{post-type}.php'],
				$post_type
			);
		}

		foreach ( get_taxonomies( null, 'names' ) as $taxonomy ) {
			$prefixes[] = 'taxonomy-' . $taxonomy;

			// Clone the labels for specific taxonomies.
			$names['taxonomy-' . $taxonomy . '-{slug}.php'] = sprintf(
				$names['taxonomy-{taxonomy}-{slug}.php'],
				$taxonomy,
				'%1$s'
			);

			$names['taxonomy-' . $taxonomy . '.php'] = sprintf(
				$names['taxonomy-{taxonomy}.php'],
				$taxonomy
			);
		}

		// Check again if we now have an exact match.
		if ( isset( $names[ $filename ] ) ) {
			return $names[ $filename ];
		}

		// Find known patterns, based on the $prefixes array.
		rsort( $prefixes );
		$pattern = '/^(' . implode( '|', array_map( 'preg_quote', $prefixes ) ) . ')-(.+)\.php$/i';

		if ( preg_match( $pattern, $filename, $matches ) ) {
			$file_pattern = sprintf(
				'%s-{%s}.php',
				$matches[1],
				is_numeric( $matches[2] ) ? 'id' : 'slug'
			);

			if ( isset( $names[ $file_pattern ] ) ) {
				return sprintf( $names[ $file_pattern ], $matches[2] );
			}
		}

		// Check for MIME types.
		foreach ( get_allowed_mime_types() as $mime ) {
			list( $type, $subtype ) = explode( '/', $mime );

			$patterns = [
				sprintf( '%s.php', $subtype ),
				sprintf( '%s-%s.php', $type, $subtype ),
			];

			if ( in_array( $filename, $patterns, true ) ) {
				return sprintf( $names['{mime}.php'], $mime );
			} elseif ( sprintf( '%s.php', $type ) === $filename ) {
				return sprintf( $names['{mime}.php'], $type );
			}
		}

		// Return a default name.
		return _x( 'n/a', 'default template name', 'unique-templates' );
	}
}
