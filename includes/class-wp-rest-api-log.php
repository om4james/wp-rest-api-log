<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log' ) ) {

	class WP_REST_API_Log {


		public function plugins_loaded() {

			// record the start time so we can log total millisecons
			global $wp_rest_api_log_start;
			$wp_rest_api_log_start = WP_REST_API_Log_Common::current_milliseconds();

			// filter that is called by the REST API right before it sends a response
			add_filter( 'rest_pre_serve_request', array( $this, 'rest_pre_serve_request' ), 9999, 4 );

			add_filter( 'wp-rest-api-log-bypass-insert', function( $bypass_insert, $result, $request, $rest_server ) {
				// an example of disabling logging for specific requests

				// don't log our own API requests
				if ( stripos( $request->get_route(), '/wp-rest-api-log') !== false ) {
					$bypass_insert = true;
				}

				return $bypass_insert;

			}, 10, 4 );

		}


		public function rest_pre_serve_request( $served, $result, $request, $rest_server ) {

			// allow specific requests to not be logged
			$bypass_insert = apply_filters( WP_REST_API_Log_Common::$plugin_name . '-bypass-insert', false, $result, $request, $rest_server );
			if ( $bypass_insert ) {
				return $served;
			}


			$args = array(
				'ip_address'            => $_SERVER['REMOTE_ADDR'],
				'route'                 => $request->get_route(),
				'method'                => $request->get_method(),
				'request'               => array(
					'body'                 => $request->get_body(),
					'headers'              => $request->get_headers(),
					'query_params'         => $request->get_query_params(),
					'body_params'          => $request->get_body_params(),
					),
				'response'              => array(
					'body'                 => $result,
					'headers'              => function_exists( 'headers_list' ) ? headers_list() : $result->get_headers(),
					),
				);


			do_action( WP_REST_API_Log_Common::$plugin_name . '-insert', $args );

			return $served;

		}


	} // end class

}
