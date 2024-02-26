<?php

abstract class SpiritCliBase extends \WP_CLI_Command {

	/**
	 * Holds the command arguments.
	 *
	 * @var array
	 */
	protected $args;

	/**
	 * Holds the command assoc arguments.
	 *
	 * @var array
	 */
	protected $assoc_args;

	/**
	 * Check whether the site is running over the Nginx web server.
	 *
	 * @return bool TRUE if the site is running over Nginx, FALSE otherwise.
	 */
	public static function isNginxServer() {
		return (bool) ( stripos( @$_SERVER['SERVER_SOFTWARE'], 'nginx' ) !== false );
	}

	/**
	 * Check whether the site is running over the Nginx web server.
	 *
	 * @return bool TRUE if the site is running over Nginx, FALSE otherwise.
	 */
	public static function isIISServer() {
		return (bool) ( stripos( @$_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS' ) !== false );
	}

	public static function scanAllDir( $dir ) {
		$result = [];
		foreach ( scandir( $dir ) as $filename ) {
			if ( $filename[0] === '.' ) {
				continue;
			}
			$filePath = $dir . '/' . $filename;
			if ( is_dir( $filePath ) ) {
				foreach ( scanAllDir( $filePath ) as $childFilename ) {
					$result[] = $filename . '/' . $childFilename;
				}
			} else {
				$result[] = $filename;
			}
		}

		return $result;
	}

	public static function find( $pattern, $folder = null ) {
		$path = '';
		if ( ! empty( $folder ) ) {
			$path = rtrim( $folder, '\/' ) . DIRECTORY_SEPARATOR;
		}
		$files       = \WP_CLI\Utils\glob_brace( $path . $pattern );
		$directories = glob( $path . '*', GLOB_ONLYDIR | GLOB_NOSORT | GLOB_MARK );
		foreach ( $directories as $directory ) {
			array_push( $files, ...self::find( $pattern, $directory ) );
		}

		return array_unique( $files );
	}

	/**
	 * Processes the provided arguments.
	 *
	 * @param array $default_args
	 * @param array $args
	 * @param array $default_assoc_args
	 * @param array $assoc_args
	 *
	 * @since 0.1.0
	 *
	 */
	protected function process_args( $default_args = array(), $args = array(), $default_assoc_args = array(), $assoc_args = array() ) {
		$this->args       = $args + $default_args;
		$this->assoc_args = wp_parse_args( $assoc_args, $default_assoc_args );
	}

	protected function wp( $command, $options = [] ) {
		$command = str_replace( 'wp ', '', $command );
		WP_CLI::line( 'Running: wp ' . $command );

		$json = str_contains( $command, '--format=json' );
		try {
			$options['back_compat_conversions'] = false;
			$response = WP_CLI::runcommand( $command, [
				'launch'       => false,
				'exit_error'       => false,
				'parse'        => $json ? 'json' : false,
				'return'       => 'all',
				'command_args' => $options
			] );
			WP_CLI::line( 'Code ' . $response->return_code );
			if ( ! empty( $response->stderr ) ) {
				var_dump($response);
				return false;
			}
			if ( $json ) {
				var_dump($response->stdout);
				return json_decode( $response->stdout, true );
			}

			return $response->stdout;
		} catch ( Exception $exception ) {
			var_dump( $exception->getMessage() );
		}

		return false;
	}

	/**
	 * Outputs a line.
	 *
	 * @param string $msg
	 * @param bool $verbose
	 */
	protected function line( $msg, $verbose ) {
		if ( $verbose ) {
			WP_CLI::line( $msg );
		}
	}

	/**
	 * Runs through all posts and executes the provided callback for each post.
	 *
	 * @param array $query_args
	 * @param callable $callback
	 * @param bool $verbose
	 */
	protected function all_posts( $query_args, $callback, $verbose = true ) {
		if ( ! is_callable( $callback ) ) {
			self::error( __( "The provided callback is invalid", 'sda-wp-cli' ) );
		}

		$default_args = array(
			'post_type'              => 'post',
			'posts_per_page'         => 1000,
			'post_status'            => array( 'publish', 'pending', 'draft', 'future', 'private' ),
			'cache_results '         => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'offset'                 => 0,
		);

		$query_args = wp_parse_args( $query_args, $default_args );
		$query      = new \WP_Query( $query_args );

		$counter     = 0;
		$found_posts = 0;
		while ( $query->have_posts() ) {
			$query->the_post();

			$callback();

			if ( 0 === $counter ) {
				$found_posts = $query->found_posts;
			}

			$counter ++;

			if ( 0 === $counter % $query_args['posts_per_page'] ) {
				$this->stop_the_insanity();

				$this->log( sprintf( __( 'Posts Updated: %d/%d', 'sda-wp-cli' ), $counter, $found_posts ), true );
				$query_args['offset'] += $query_args['posts_per_page'];
				$query                = new \WP_Query( $query_args );
			}
		}

		wp_reset_postdata();

		$this->success( sprintf(
			__( '%d posts were updated', 'sda-wp-cli' ),
			$counter
		), $verbose );
	}

	/**
	 * Frees up memory for long-running processes.
	 */
	protected function stop_the_insanity() {
		global $wpdb, $wp_actions, $wp_filter, $wp_object_cache;

		//reset queries
		$wpdb->queries = array();
		// Prevent wp_actions from growing out of control
		$wp_actions = array();

		if ( is_object( $wp_object_cache ) ) {
			$wp_object_cache->group_ops      = array();
			$wp_object_cache->stats          = array();
			$wp_object_cache->memcache_debug = array();
			$wp_object_cache->cache          = array();

			if ( method_exists( $wp_object_cache, '__remoteset' ) ) {
				$wp_object_cache->__remoteset();
			}
		}

		/*
		 * The WP_Query class hooks a reference to one of its own methods
		 * onto filters if update_post_term_cache or
		 * update_post_meta_cache are true, which prevents PHP's garbage
		 * collector from cleaning up the WP_Query instance on long-
		 * running processes.
		 *
		 * By manually removing these callbacks (often created by things
		 * like get_posts()), we're able to properly unallocate memory
		 * once occupied by a WP_Query object.
		 *
		 */
		if ( isset( $wp_filter['get_term_metadata'] ) ) {
			/*
			 * WordPress 4.7 has a new Hook infrastructure, so we need to make sure
			 * we're accessing the global array properly.
			 */
			if ( class_exists( 'WP_Hook' ) && $wp_filter['get_term_metadata'] instanceof \WP_Hook ) {
				$filter_callbacks = &$wp_filter['get_term_metadata']->callbacks;
			} else {
				$filter_callbacks = &$wp_filter['get_term_metadata'];
			}

			if ( isset( $filter_callbacks[10] ) ) {
				foreach ( $filter_callbacks[10] as $hook => $content ) {
					if ( preg_match( '#^[0-9a-f]{32}lazyload_term_meta$#', $hook ) ) {
						unset( $filter_callbacks[10][ $hook ] );
					}
				}
			}
		}

	}

	/**
	 * Outputs a log message.
	 *
	 * @param string $msg
	 * @param bool $verbose
	 */
	protected function log( $msg, $verbose ) {
		if ( $verbose ) {
			WP_CLI::log( $msg );
		}
	}

	/**
	 * Outputs a success message.
	 *
	 * @param string $msg
	 * @param bool $verbose
	 */
	protected function success( $msg, $verbose ) {
		if ( $verbose ) {
			WP_CLI::success( $msg );
		}
	}

	/**
	 * Runs through all records on a specific table.
	 *
	 * @param string $message
	 * @param string $table
	 * @param callable $callback
	 *
	 * @return bool
	 */
	protected function all_records( $message, $table, $callback ) {
		global $wpdb;

		$offset = 0;
		$step   = 1000;

		$found_posts = $wpdb->get_col( "SELECT COUNT(ID) FROM {$table}" );

		if ( ! $found_posts ) {
			return false;
		}

		$found_posts = $found_posts[0];

		$progress_bar = \WP_CLI\Utils\make_progress_bar( sprintf( '[%d] %s', $found_posts, $message ), (int) $found_posts, 1 );
		$progress_bar->display();

		do {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} LIMIT %d OFFSET %d", array(
					$step,
					$offset,
				) )
			);

			if ( $results ) {
				foreach ( $results as $result ) {
					$callback( $result );
					$progress_bar->tick();
				}
			}

			$offset += $step;

		} while ( $results );
	}

	/**
	 * Outputs a warning.
	 *
	 * @param string $msg
	 * @param bool $verbose
	 */
	protected function warning( $msg, $verbose ) {
		if ( $verbose ) {
			WP_CLI::warning( $msg );
		}
	}
}
