<?php
/**
 * Plugin Name: Storms WooCommerce Disable Products
 * Description: This plugin allow you to hide the price and disable the add-to-cart button of an specific product
 * Author: Storms Websolutions - Vinicius Garcia
 * Author URI: http://storms.com.br/
 * Copyright: (c) Copyright 2012-2016, Storms Websolutions
 * License: GPLv2 - GNU General Public License v2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * Version: 0.1
 *
 * Text Domain: storms
 * Domain Path: /i18n/languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// WooCommerce Product Page - Adiciona o campo na tela
// @see http://www.remicorson.com/mastering-woocommerce-products-custom-fields/
function swdp_add_is_enable_field_on_product_page() {
	global $woocommerce, $post;

	echo '<div class="options_group">';

    $product_is_enable = get_post_meta( $post->ID, '_swdp_product_is_enable', true );
	woocommerce_wp_select(
		array(
			'id'      => '_swdp_product_is_enable',
			'label'   => __( 'Produto habilitado para compra?', 'storms' ),
			'options' => array(
				'yes' => __( 'Habilitar para compra', 'storms' ),
				'no'  => __( 'Desabilitar para compra', 'storms' ),
			),
            'value'   => ( !empty( $product_is_enable ) && $product_is_enable == 'yes' ? 'yes' : 'no' ),
			'custom_attributes' => array(
				'autocomplete' => 'off'
			),
			'description' => __( 'Desabilitar um produto irá esconder o preço e o botão "Adicionar ao Carrinho", não permitindo que os cliente comprem esse produto.', 'storms' ),
		)
	);

	echo '</div>';
}
add_action( 'woocommerce_product_options_general_product_data', 'swdp_add_is_enable_field_on_product_page' );

// WooCommerce Product Page - Salva o campo da tela
function swdp_add_custom_general_fields_save( $post_id ) {

    $product_is_enable = $_POST['_swdp_product_is_enable'] ?? 'yes';

    if( $product_is_enable == 'yes' ) {
        wp_update_post( [ 'ID' => $post_id, 'post_status' => 'publish' ] );
        update_post_meta( $post_id, '_swdp_product_is_enable', esc_attr( $product_is_enable ) );
    } else {
        wp_update_post( [ 'ID' => $post_id, 'post_status' => 'draft' ] );
        update_post_meta( $post_id, '_swdp_product_is_enable', esc_attr( $product_is_enable ) );
    }
}
add_action( 'woocommerce_process_product_meta', 'swdp_add_custom_general_fields_save' );

/**
 * Se o produto nao esta habilitado para compra, entao avisamos o WC que ele nao eh compravel
 * @param $is_purchasable
 * @param $product
 * @return bool
 */
function swdp_check_if_is_purchasable( $is_purchasable, $product ) {
    // Verificamos se o produto esta habilitado
    $product_is_enable = ( $product->get_meta('_swdp_product_is_enable') == 'yes' );
    return ( $product_is_enable && ! swdp_is_shop_disabled() && $is_purchasable );
}
add_filter( 'woocommerce_is_purchasable', 'swdp_check_if_is_purchasable', 10, 2 );

// Add column in woocommerce admin product list
// Source: http://stackoverflow.com/q/23858236/1003020
function swdp_show_product_status( $columns ) {

	$new_columns = array();
	foreach( $columns as $key => $value ) {
		$new_columns[$key] = $value;

		if( $key == 'price' ) {
			// Add column right after the 'price' column
			$new_columns['_swdp_product_is_enable'] = __( 'Habilitado?', 'storms' );
		}
	}

   return $new_columns;
}
add_filter( 'manage_edit-product_columns', 'swdp_show_product_status', 15 );

// Add the value to the column in woocommerce admin product list
function swdp_fill_column_product_status( $column, $postid ) {
    if ( $column == '_swdp_product_is_enable' ) {

        // Verificamos se o produto esta habilitado
        $product_is_enable = ( get_post_meta( $postid, '_swdp_product_is_enable', true ) == 'yes' );

        echo ( $product_is_enable ? '<mark class="instock">' . __( 'Sim', 'storms' ) . '</mark>' : '<mark class="outofstock">' . __( 'Não', 'storms' ) . '</mark>' );
    }
}
add_action( 'manage_product_posts_custom_column', 'swdp_fill_column_product_status', 10, 2 );

// Dont show the product price and remove the add-to-cart button for disabled products
function swdp_show_price( $price, $product ) {
    // Verificamos se o produto esta habilitado
    $product_is_enable = ( $product->get_meta('_swdp_product_is_enable') == 'yes' );

	if( ! $product_is_enable || swdp_is_shop_disabled() ) {
		return __( 'Não disponível', 'storms' );
	}

	return $price;
}
add_filter( 'woocommerce_get_price_html', 'swdp_show_price', 10, 2 );

// Dont show the add to cart button on loop when the product is disabled
function swdp_loop_add_to_cart_link( $add_to_cart_link, $product ) {
    // Verificamos se o produto esta habilitado
    $product_is_enable = ( $product->get_meta('_swdp_product_is_enable') == 'yes' );

    if( ! $product_is_enable || swdp_is_shop_disabled() ) {
		return '';
	}

	return $add_to_cart_link;
}
add_filter( 'woocommerce_loop_add_to_cart_link', 'swdp_loop_add_to_cart_link', 10, 2 );

/**
 * REST API - Register Produto Bloqueado field.
 */
function swdp_register_product_blocked_field() {

    if ( ! function_exists( 'register_rest_field' ) ) {
        return;
    }

    // Campo Produto Bloqueado
    register_rest_field( 'product',
        'product_blocked',
        array(
            'get_callback'    => 'get_product_blocked_callback',
            'update_callback' => 'update_product_blocked_callback',
            'schema'          => array(
                'description' => __( 'Se um produto estiver bloqueado, ele não poderá ser comprado por nenhum cliente.', 'storms' ),
                'type'        => 'string',
                'context'     => array( 'view', 'edit' ),
            ),
        )
    );
}
add_action( 'rest_api_init', 'swdp_register_product_blocked_field', 100 );

/**
 * REST API - Get Produto Bloqueado callback.
 *
 * @param array           $data    Details of current response.
 * @param string          $field   Name of field.
 * @param WP_REST_Request $request Current request.
 *
 * @return string
 */
function get_product_blocked_callback( $data, $field, $request ) {
    $product_is_enable = get_post_meta( $data['id'], '_swdp_product_is_enable', true );
    return ( ( $product_is_enable == 'yes' ) ? 'no' : 'yes' );
}

/**
 * REST API - Update Produto Bloqueado callback.
 *
 * @param string  $value  The value of the field.
 * @param WP_Post $object The object from the response.
 *
 * @return bool
 */
function update_product_blocked_callback( $value, $object ) {

    $product_is_blocked = $value;
    if( empty( $product_is_blocked ) ) {
        $product_is_blocked = 'no';
    }

    $product_is_enable = ( $product_is_blocked == 'no' );
    if( $product_is_enable ) {
        wp_update_post( [ 'ID' => $object->get_id(), 'post_status' => 'publish' ] );
        update_post_meta( $object->get_id(), '_swdp_product_is_enable', 'yes' );
    } else {
        wp_update_post( [ 'ID' => $object->get_id(), 'post_status' => 'draft' ] );
        update_post_meta( $object->get_id(), '_swdp_product_is_enable', 'no' );
    }
}

/**
 * Change product status - block or unblock
 *
 * @param int $product_id
 * @param bool $block
 */
function swdp_change_product_status( $product_id, $block ) {
    if( $block ) {
        wp_update_post( [ 'ID' => $product_id, 'post_status' => 'draft' ] );
        update_post_meta( $product_id, '_swdp_product_is_enable', 'no' );
    } else {
        wp_update_post( [ 'ID' => $product_id, 'post_status' => 'publish' ] );
        update_post_meta( $product_id, '_swdp_product_is_enable', 'yes' );
    }
}

/**
 * Enable/Disable WooCommerce Shop
 * @param bool $desabilitar
 */
function swdp_disable_shop( $desabilitar ) {
	// Disable WooCommerce Shop - Enable Catalog Mode
	update_option( '_swdp_disable_shop', ( $desabilitar ? 'yes' : 'no' ) );

	// The shop status has changed
	do_action( 'swdp_disable_shop', $desabilitar );
}

/**
 * Check if WooCommerce Shop is disabled
 * @return bool
 */
function swdp_is_shop_disabled() {
	return ( get_option( '_swdp_disable_shop', 'no' ) == 'yes' );
}

add_action( 'rest_api_init', function() {
    include_once('storms_rest_api_shop.php');
    $controller = new API\SWDP_API_Shop();
    $controller->register_routes();

    include_once('storms_rest_api_products.php');
    $controller = new API\SWDP_API_Products();
    $controller->register_routes();
});