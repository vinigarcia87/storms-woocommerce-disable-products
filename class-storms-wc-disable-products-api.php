<?php
/**
 * Storms Framework (http://storms.com.br/)
 *
 * @author    Vinicius Garcia | vinicius.garcia@storms.com.br
 * @copyright (c) Copyright 2012-2020, Storms Websolutions
 * @license   GPLv2 - GNU General Public License v2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package   Storms
 * @version   3.0.0
 *
 * Storms_WC_Disable_Products_API class
 * Shop Control endpoint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Storms_WC_Disable_Products_API extends \WC_REST_Products_Controller
{

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-storms/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'products';

	/**
	 * Register the routes for customers.
	 */
	public function register_routes() {

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<sku>[a-zA-Z-0-9]+)/block', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'block_product_by_sku'),
                'permission_callback' => array($this, 'block_product_permissions_check'),
                'args' => array(
                    'context' => $this->get_context_param(array('default' => 'view')),
                ),
            ),
        ));

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<sku>[a-zA-Z-0-9]+)/unblock', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'unblock_product_by_sku'),
                'permission_callback' => array($this, 'unblock_product_permissions_check'),
                'args' => array(
                    'context' => $this->get_context_param(array('default' => 'view')),
                ),
            ),
        ));

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/block', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'block_product_by_id'),
                'permission_callback' => array($this, 'block_product_permissions_check'),
                'args' => array(
                    'context' => $this->get_context_param(array('default' => 'view')),
                ),
            ),
        ));

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/unblock', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array($this, 'unblock_product_by_id'),
                'permission_callback' => array($this, 'unblock_product_permissions_check'),
                'args' => array(
                    'context' => $this->get_context_param(array('default' => 'view')),
                ),
            ),
        ));

	}

	//<editor-fold desc="Block product">

    /**
     * Check if a given request has access to block an item.
     *
     * @param  \WP_REST_Request $request Full details about the request.
     * @return \WP_Error|boolean
     */
    public function block_product_permissions_check( $request ) {
        if ( ! wc_rest_check_post_permissions( $this->post_type, 'create' ) ) {
            return new \WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to block resources.', 'storms' ), array( 'status' => rest_authorization_required_code() ) );
        }

        return true;
    }

    /**
     * Block a single item.
     *
     * @param \WP_REST_Request $request Full details about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function block_product_by_sku( $request ) {
        $sku   = (string) $request['sku'];
        $id    = wc_get_product_id_by_sku( $sku );
        $request['id'] = $id;
        $object = $this->get_object( (int) $request['id'] );

        if ( ! $object || 0 === $object->get_id() ) {
            return new \WP_Error( "woocommerce_rest_{$this->post_type}_invalid_id", __( 'Invalid ID.', 'woocommerce' ), array( 'status' => 404 ) );
        }

        // Block the selected product
        swdp_change_product_status( $object->get_id(), true );

        return parent::get_item( $request );
    }

    /**
     * Block a single item.
     *
     * @param \WP_REST_Request $request Full details about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function block_product_by_id( $request ) {
        $object = $this->get_object( (int) $request['id'] );

        if ( empty( $id ) || empty( $object->get_id() ) || ! in_array( $object->get_type(), [ 'product', 'variation' ] ) ) {
            return new \WP_Error( "woocommerce_rest_invalid_{$this->post_type}_id", __( 'Invalid id.', 'woocommerce' ), array( 'status' => 404 ) );
        }

        // Block the selected product
        swdp_change_product_status( $object->get_id(), true );

        return $this->get_item( $request );
    }

	//</editor-fold>

    //<editor-fold desc="Unblock product">

    /**
     * Check if a given request has access to unblock an item.
     *
     * @param  \WP_REST_Request $request Full details about the request.
     * @return \WP_Error|boolean
     */
    public function unblock_product_permissions_check( $request ) {
        if ( ! wc_rest_check_post_permissions( $this->post_type, 'create' ) ) {
            return new \WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to unblock resources.', 'storms' ), array( 'status' => rest_authorization_required_code() ) );
        }

        return true;
    }

    /**
     * Unblock a single item.
     *
     * @param \WP_REST_Request $request Full details about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function unblock_product_by_sku( $request ) {
        $sku   = (string) $request['sku'];
        $id    = wc_get_product_id_by_sku( $sku );
        $request['id'] = $id;
        $object = $this->get_object( (int) $request['id'] );

        if ( ! $object || 0 === $object->get_id() ) {
            return new \WP_Error( "woocommerce_rest_{$this->post_type}_invalid_id", __( 'Invalid ID.', 'woocommerce' ), array( 'status' => 404 ) );
        }

        // Block the selected product
        swdp_change_product_status( $object->get_id(), false );

        return parent::get_item( $request );
    }

    /**
     * Unblock a single item.
     *
     * @param \WP_REST_Request $request Full details about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function unblock_product_by_id( $request ) {
        $object = $this->get_object( (int) $request['id'] );

        if ( empty( $id ) || empty( $object->get_id() ) || ! in_array( $object->get_type(), [ 'product', 'variation' ] ) ) {
            return new \WP_Error( "woocommerce_rest_invalid_{$this->post_type}_id", __( 'Invalid id.', 'woocommerce' ), array( 'status' => 404 ) );
        }

        // Block the selected product
        swdp_change_product_status( $object->get_id(), false );

        return $this->get_item( $request );
    }

    //</editor-fold>

}
