<?php
/**
 * Storms Framework (http://storms.com.br/)
 *
 * @author    Vinicius Garcia | vinicius.garcia@storms.com.br
 * @copyright (c) Copyright 2012-2016, Storms Websolutions
 * @license   GPLv2 - GNU General Public License v2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package   Storms
 * @version   3.0.0
 *
 * API\WC_API_Loja class
 * Shop Control endpoint
 */

namespace API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SWDP_API_Shop extends \WC_REST_Controller
{

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-swdp/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'shop';

	/**
	 * Register the routes for customers.
	 */
	public function register_routes() {

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/enable', array(
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'set_enable' ),
				'permission_callback' => array( $this, 'set_enable_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/disable', array(
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'set_disable' ),
				'permission_callback' => array( $this, 'set_disable_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/status', array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'check_shop_status' ),
				'permission_callback' => array( $this, 'check_shop_status_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	//<editor-fold desc="Enable Shop">

	/**
	 * Enable the shop
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function set_enable( $request ) {

        swdp_disable_shop( false );

		if( swdp_is_shop_disabled() ) {
			return new \WP_Error( 'storms_rest_cannot_enable', __( 'Sorry, could not enable the shop.', 'storms' ), array( 'status' => 400 ) );
		}

        $date = new \DateTime();
        $date->setTimezone(new \DateTimeZone('America/Sao_Paulo'));

		$status = array(
			'shop_status' => true,
			'description' => __( 'The shop is enabled', 'storms' ),
			'status_date' => $date->format('Y-m-d H:i:s'),
		);

		$status = $this->prepare_item_for_response( $status, $request );
		$response = rest_ensure_response( $status );

		return $response;
	}

	/**
	 * Check whether a given request has permission to enable the shop
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function set_enable_permissions_check( $request ) {
		if ( ! wc_rest_check_user_permissions( 'create' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	//</editor-fold>

	//<editor-fold desc="Disable Shop">

	/**
	 * Disable the shop
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function set_disable( $request ) {

        swdp_disable_shop( true );

		if( ! swdp_is_shop_disabled() ) {
			return new \WP_Error( 'storms_rest_cannot_disable', __( 'Sorry, could not disable the shop.', 'storms' ), array( 'status' => 400 ) );
		}

		$date = new \DateTime();
		$date->setTimezone(new \DateTimeZone('America/Sao_Paulo'));

		$status = array(
			'shop_status' => false,
			'description' => __( 'The shop is disabled', 'storms' ),
			'status_date' => $date->format('Y-m-d H:i:s'),
		);

		$status = $this->prepare_item_for_response( $status, $request );
		$response = rest_ensure_response( $status );

		return $response;
	}

	/**
	 * Check whether a given request has permission to disable the shop
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function set_disable_permissions_check( $request ) {
		if ( ! wc_rest_check_user_permissions( 'create' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	//</editor-fold>

	//<editor-fold desc="Check if shop is enable">

	/**
	 * Verifica se a loja esta habilitada ou desabilitada para vendas
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function check_shop_status( $request ) {

		$shop_status = ! swdp_is_shop_disabled();

		$descp = $shop_status ? __( 'The shop is enabled', 'storms' ) : __( 'The shop is disabled', 'storms' );

        $date = new \DateTime();
        $date->setTimezone(new \DateTimeZone('America/Sao_Paulo'));

		$status = array(
			'shop_status' => $shop_status,
			'description' => $descp,
			'status_date' => $date->format('Y-m-d H:i:s'),
		);

		$status = $this->prepare_item_for_response( $status, $request );
		$response = rest_ensure_response( $status );

        return $response;
	}

	/**
	 * Check whether a given request has permission to check the shop status
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function check_shop_status_permissions_check( $request ) {
		if ( ! wc_rest_check_user_permissions( 'read' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	//</editor-fold>

	/**
	 * Prepare a shop status request output for response.
	 *
	 * @param string $ping Ping object.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $ping, $request ) {

		$data = array(
			'shop_status' => $ping['shop_status'],
			'description'   => $ping['description'],
			'status_date'   => wc_rest_prepare_date_response( $ping['status_date'] ),
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		/**
		 * Filter customer data returned from the REST API.
		 *
		 * @param \WP_REST_Response $response  The response object.
		 * @param string           $ping      Ping object.
		 * @param \WP_REST_Request  $request   Request object.
		 */
		return apply_filters( 'woocommerce_rest_prepare_swdp_shop', $response, $ping, $request );
	}

	/**
	 * Get the Concessionaria's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = [
			'server_status' => array(
				'description' => __( 'Shop status.', 'storms' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'description' => array(
				'description' => __( 'Shop status description.', 'storms' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'status_date' => array(
				'description' => __( 'Date when the shop status was checked.', 'storms' ),
				'type'        => 'date-time',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		];

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'context' => array(
				'default' => 'view'
			)
		);
	}
}
