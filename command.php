<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

require_once __DIR__ . '/src/class-theme.php';

WP_CLI::add_command( 'theme unique-templates', [ 'LiquidWeb\UniqueTemplates\Theme', 'unique_templates' ] );
