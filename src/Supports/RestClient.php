<?php

namespace Stackonet\WP\Framework\Supports;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Class RestClient
 *
 * @package Stackonet\WP\Framework\Supports
 */
class RestClient {
	/**
	 * API base URL
	 *
	 * @var string
	 */
	protected $api_base_url = '';

	/**
	 * User Agent
	 *
	 * @var string
	 */
	protected $user_agent = null;

	/**
	 * Request headers
	 *
	 * @var array
	 */
	protected $headers = [];

	/**
	 * Additional request arguments
	 *
	 * @var array
	 */
	protected $request_args = [];

	/**
	 * Global parameters that should send on every request
	 *
	 * @var array
	 */
	protected $global_parameters = [];

	/**
	 * Request info for debugging
	 *
	 * @var array
	 */
	protected $debug_info = [];

	/**
	 * Class constructor.
	 *
	 * @param string|null $api_base_url API base URL.
	 */
	public function __construct( ?string $api_base_url = null ) {
		if ( filter_var( $api_base_url, FILTER_VALIDATE_URL ) ) {
			$this->api_base_url = $api_base_url;
		}
		$this->user_agent = get_option( 'blogname' );

		// setup defaults.
		$this->set_request_arg( 'timeout', 30 );
		$this->set_request_arg( 'sslverify', false );
		$this->add_headers( 'User-Agent', $this->user_agent );

		return $this;
	}

	/**
	 * Get content type
	 *
	 * @return string
	 */
	public function get_content_type(): string {
		return $this->headers['Content-Type'] ?? '';
	}

	/**
	 * Add header
	 *
	 * @param string|array $key Header key. Or array of headers with key => value format.
	 * @param mixed        $value The value.
	 *
	 * @return self
	 */
	public function add_headers( $key, $value = null ) {
		if ( is_string( $key ) ) {
			$this->headers[ $key ] = $value;

			return $this;
		}
		if ( is_array( $key ) ) {
			foreach ( $key as $header => $header_value ) {
				$this->headers[ $header ] = $header_value;
			}
		}

		return $this;
	}

	/**
	 * Add authorization header
	 *
	 * @param string $credentials Authorization credentials.
	 * @param string $type Authorization type.
	 *
	 * @return static
	 */
	public function add_auth_header( string $credentials, string $type = 'Basic' ) {
		return $this->add_headers( 'Authorization', sprintf( '%s %s', $type, $credentials ) );
	}

	/**
	 * Set request argument.
	 *
	 * @param string $name Argument name.
	 * @param null   $value Argument value.
	 *
	 * @return static
	 */
	public function set_request_arg( string $name = '', $value = null ) {
		$this->request_args[ $name ] = $value;

		return $this;
	}

	/**
	 * Get api endpoint
	 *
	 * @param string $endpoint Rest URL Endpoint.
	 *
	 * @return string
	 */
	public function get_api_endpoint( string $endpoint = '' ): string {
		// Return endpoint if it is a full URI.
		if ( false !== strpos( $endpoint, 'http' ) ) {
			return $endpoint;
		}
		return rtrim( $this->api_base_url, '/' ) . '/' . ltrim( $endpoint, '/' );
	}

	/**
	 * Get global parameters
	 *
	 * @return array
	 */
	public function get_global_parameters(): array {
		return $this->global_parameters;
	}

	/**
	 * Set global parameter
	 *
	 * @param string $key data key.
	 * @param mixed  $value The value to be set.
	 *
	 * @return static
	 */
	public function set_global_parameter( string $key, $value ) {
		$this->global_parameters[ $key ] = $value;

		return $this;
	}

	/**
	 * Get debug info
	 *
	 * @return array
	 */
	public function get_debug_info(): array {
		return $this->debug_info;
	}

	/**
	 * Performs an HTTP GET request and returns its response.
	 *
	 * @param string $endpoint The rest endpoint.
	 * @param array  $parameters Additional parameters.
	 *
	 * @return array|WP_Error The response array or a WP_Error on failure.
	 */
	public function get( string $endpoint = '', array $parameters = [] ) {
		return $this->request( 'GET', $endpoint, $parameters );
	}

	/**
	 * Performs an HTTP POST request and returns its response.
	 *
	 * @param string $endpoint The rest endpoint.
	 * @param mixed  $data The rest body content.
	 *
	 * @return array|WP_Error The response array or a WP_Error on failure.
	 */
	public function post( string $endpoint = '', $data = null ) {
		return $this->request( 'POST', $endpoint, $data );
	}

	/**
	 * Performs an HTTP PUT request and returns its response.
	 *
	 * @param string $endpoint The rest endpoint.
	 * @param mixed  $data The rest body content.
	 *
	 * @return array|WP_Error The response array or a WP_Error on failure.
	 */
	public function put( string $endpoint = '', $data = null ) {
		return $this->request( 'PUT', $endpoint, $data );
	}

	/**
	 * Performs an HTTP DELETE request and returns its response.
	 *
	 * @param string $endpoint The rest endpoint.
	 * @param mixed  $parameters Additional parameters.
	 *
	 * @return array|WP_Error The response array or a WP_Error on failure.
	 */
	public function delete( string $endpoint = '', $parameters = null ) {
		return $this->request( 'DELETE', $endpoint, $parameters );
	}


	/**
	 * Performs an HTTP request and returns its response.
	 *
	 * @param string            $method Request method. Support GET, POST, PUT, DELETE.
	 * @param string            $endpoint The rest endpoint.
	 * @param null|string|array $request_body Request body or additional parameters for GET method.
	 *
	 * @return array|WP_Error The response array or a WP_Error on failure.
	 */
	public function request( string $method = 'GET', string $endpoint = '', $request_body = null ) {
		$url             = $this->get_endpoint_url( $method, $endpoint, $request_body );
		$args            = $this->get_arguments( $method, $request_body );
		$remote_response = wp_remote_request( $url, $args );

		$this->debug_info = [
			'request_url'     => $url,
			'request_args'    => $args,
			'remote_response' => $remote_response,
		];

		return $this->filter_remote_response( $url, $args, $remote_response );
	}

	/**
	 * Get HTTP request url and arguments
	 *
	 * @param string            $method Request method. Support GET, POST, PUT, DELETE.
	 * @param string            $endpoint The rest endpoint.
	 * @param null|string|array $request_body Request body or additional parameters for GET method.
	 *
	 * @return array
	 */
	public function get_url_and_arguments( string $method, string $endpoint, ?array $request_body ): array {
		return [
			$this->get_endpoint_url( $method, $endpoint, $request_body ),
			$this->get_arguments( $method, $request_body ),
		];
	}

	/**
	 * Get HTTP request url and arguments
	 *
	 * @param string            $method Request method. Support GET, POST, PUT, DELETE.
	 * @param null|string|array $request_body Request body or additional parameters for GET method.
	 *
	 * @return array
	 */
	public function get_arguments( string $method = 'GET', $request_body = null ): array {
		$base_args = [
			'method'  => $method,
			'headers' => $this->headers,
		];
		$args      = array_merge( $base_args, $this->request_args );
		if ( $request_body && ! in_array( $method, [ 'HEAD', 'GET', 'DELETE' ], true ) ) {
			$args['body'] = $request_body;
		}

		return $args;
	}

	/**
	 * Get endpoint full url
	 *
	 * @param string $method Request method. Support GET, POST, PUT, DELETE.
	 * @param string $endpoint Endpoint.
	 * @param mixed  $args additional arguments.
	 *
	 * @return string
	 */
	public function get_endpoint_url( string $method, string $endpoint, $args = null ): string {
		$url = $this->get_api_endpoint( $endpoint );
		if ( is_array( $args ) && in_array( $method, [ 'HEAD', 'GET', 'DELETE' ], true ) ) {
			$url = add_query_arg( $args, $url );
		}

		// Add global parameters if any.
		if ( count( $this->get_global_parameters() ) ) {
			$url = add_query_arg( $this->get_global_parameters(), $url );
		}

		return $url;
	}

	/**
	 * Filter remote response
	 *
	 * @param string         $url The request URL.
	 * @param array          $args The request arguments.
	 * @param array|WP_Error $response The remote response or WP_Error object.
	 *
	 * @return array|WP_Error
	 */
	public function filter_remote_response( string $url, array $args, $response ) {
		if ( is_wp_error( $response ) ) {
			$response->add_data( $url, 'debug_request_url' );
			$response->add_data( $args, 'debug_request_args' );

			return $response;
		}

		$response_code = (int) wp_remote_retrieve_response_code( $response );
		$content_type  = wp_remote_retrieve_header( $response, 'Content-Type' );
		if ( false !== strpos( $content_type, 'application/json' ) ) {
			$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
		} elseif ( false !== strpos( $content_type, 'text/html' ) ) {
			$response_body = (array) wp_remote_retrieve_body( $response );
		} else {
			$response_body = 'Unsupported content type: ' . $content_type;
		}

		if ( ! ( $response_code >= 200 && $response_code < 300 ) ) {
			$response_message = wp_remote_retrieve_response_message( $response );
			$wp_error         = new WP_Error( 'rest_error', $response_message, $response_body );
			$wp_error->add_data( $this->get_debug_info(), 'debug_info' );

			return $wp_error;
		}

		if ( ! is_array( $response_body ) ) {
			$wp_error = new WP_Error( 'unexpected_response_type', 'Rest Client Error: unexpected response type' );
			$wp_error->add_data( $this->get_debug_info(), 'debug_info' );

			return $wp_error;
		}

		return $response_body;
	}
}
