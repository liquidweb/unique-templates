<?php

namespace LiquidWeb\UniqueTemplates;

use WP_CLI\Utils as Utils;
use WP_CLI_Command;

class Theme extends WP_CLI_Command {

	/**
	 * List unique templates within a theme.
	 *
	 * ## OPTIONS
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields. Separate multiple values with a comma.
	 * ---
	 * default: all
	 * options:
	 *   - all
	 *   - name
	 *   - filename
	 *   - url
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
			[ 'name', 'filename', 'url' ],
			explode( ',', $assoc_args['fields'] )
		);

		// Collect the list of templates.
		$template_files = $this->get_all_templates();
		$template_base  = array_map( 'trailingslashit', [
			get_stylesheet_directory(),
			get_template_directory(),
		] );

		// Collect additional details about each template, as necessary.
		foreach ( $template_files as $filename ) {
			$templates[] = [
				'name'     => basename( $filename, '.php' ),
				'filename' => str_replace( $template_base, '', $filename ),
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

		// Append named templates, using wp_get_theme()->get_post_templates().
		$named_templates = array_reduce( wp_get_theme()->get_post_templates(), function ( $templates, $group ) {
			return array_merge( $templates, array_keys( $group ) );
		}, [] );

		// Create a single list of template files and filter it to unique files.
		return array_filter( array_unique( array_merge( $template_files, $named_templates ) ) );
	}
}
