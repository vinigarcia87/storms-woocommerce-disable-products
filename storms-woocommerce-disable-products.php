<?php
/**
 * Plugin Name: Storms WooCommerce Disable Products
 * Plugin URI: https://github.com/vinigarcia87/storms-woocommerce-disable-products
 * Description: This plugin allow you to hide the price and disable the add-to-cart button of an specific product
 * Author: Storms Websolutions - Vinicius Garcia
 * Author URI: http://storms.com.br/
 * Copyright: (c) Copyright 2012-2020, Storms Websolutions
 * License: GPLv2 - GNU General Public License v2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * Version: 1.0
 *
 * WC requires at least: 3.9.2
 * WC tested up to: 3.9.2
 *
 * Text Domain: storms
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	//<editor-fold desc="Funções de verificação de status dos produtos e da loja">

	/**
	 * Verifica se um produto esta habilitado
	 * @param $product_id
	 * @return string
	 */
	function swdp_is_product_enable( $product_id ) {
		$product_is_enable = get_post_meta( $product_id, '_swdp_product_is_enable', true );
		return ( empty( $product_is_enable ) || ( $product_is_enable == 'yes' ) ? 'yes' : 'no' );
	}

	/**
	 * Check if WooCommerce Shop is enabled
	 * @return bool
	 */
	function swdp_is_shop_enabled() {
		return ( get_option( '_swdp_enabled_shop', 'yes' ) == 'yes' );
	}

	//</editor-fold>

	//<editor-fold desc="Campo de habilitar na pagina do produto">

	/**
	 * WooCommerce Product Page - Adiciona o campo na tela
	 * @see http://www.remicorson.com/mastering-woocommerce-products-custom-fields/
	 */
	function swdp_add_is_enable_field_on_product_page() {
		global $post;

		echo '<div class="options_group">';

		woocommerce_wp_select(
			array(
				'id' => '_swdp_product_is_enable',
				'label' => __('Produto habilitado para compra?', 'storms'),
				'options' => array(
					'yes' => __('Habilitar para compra', 'storms'),
					'no' => __('Desabilitar para compra', 'storms'),
				),
				'value' => swdp_is_product_enable( $post->ID ),
				'custom_attributes' => array(
					'autocomplete' => 'off'
				),
				'description' => __('Desabilitar um produto irá esconder o preço e o botão "Adicionar ao Carrinho", não permitindo que os cliente comprem esse produto.', 'storms'),
			)
		);

		echo '</div>';
	}
	add_action( 'woocommerce_product_options_general_product_data', 'swdp_add_is_enable_field_on_product_page' );

	/**
	 * WooCommerce Product Page - Salva o campo da tela
	 * @param $post_id
	 */
	function swdp_add_custom_general_fields_save( $post_id ) {

		$product_is_enable = $_POST['_swdp_product_is_enable'] ?? 'yes';

		// Se mudou para enabled, nao bloqueamos o produto
		$block = $product_is_enable == 'no';
		swdp_change_product_status( $post_id, $block );
	}
	add_action( 'woocommerce_process_product_meta', 'swdp_add_custom_general_fields_save' );

	//</editor-fold>

	//<editor-fold desc="Mostrar status do produto na listagem de produtos">

	/**
	 * Add column in woocommerce admin product list
	 * Source: http://stackoverflow.com/q/23858236/1003020
	 * @param $columns
	 * @return array
	 */
	function swdp_show_product_status( $columns ) {

		$new_columns = array();
		foreach( $columns as $key => $value ) {
			$new_columns[$key] = $value;

			if( $key == 'price' ) {
				// Add column right after the 'price' column
				$new_columns['swdp_product_is_enable'] = __( 'Habilitado?', 'storms' );
			}
		}

		return $new_columns;
	}
	add_filter( 'manage_edit-product_columns', 'swdp_show_product_status', 15 );

	// Add the value to the column in woocommerce admin product list
	function swdp_fill_column_product_status( $column, $postid ) {

		if( $column == 'swdp_product_is_enable' ) {
			// Verificamos se o produto esta habilitado
			$product_is_enable = swdp_is_product_enable( $postid ) == 'yes';
			echo ( $product_is_enable ? '<mark class="instock">' . __( 'Sim', 'storms' ) . '</mark>' : '<mark class="outofstock">' . __( 'Não', 'storms' ) . '</mark>' );
		}
	}
	add_action( 'manage_product_posts_custom_column', 'swdp_fill_column_product_status', 10, 2 );

	/**
	 * Add a style for the new column that shows if each product is enabled or not
	 */
	function swdp_style_for_product_is_enable_column_on_products_admin_page() {
		$current_screen = get_current_screen();
		if( 'edit' == $current_screen->base && 'product' == $current_screen->post_type ) { ?>
			<style>
				.column-swdp_product_is_enable {
					width: 9ch;
					text-align: center;
				}
			</style>
		<?php
		}
	}
	add_action( 'current_screen', 'swdp_style_for_product_is_enable_column_on_products_admin_page' );

	//</editor-fold>

	//<editor-fold desc="Controle de exibiçao de preços  e botões de compra">

	/**
	 * Se o produto nao esta habilitado para compra, entao avisamos o WC que ele nao eh compravel
	 * @param $is_purchasable
	 * @param WC_Product $product
	 * @return bool
	 */
	function swdp_check_if_is_purchasable( $is_purchasable, $product ) {
		// Verificamos se o produto esta habilitado
		$product_is_enable = swdp_is_product_enable( $product->get_id() ) == 'yes';
		return ( $product_is_enable && swdp_is_shop_enabled() && $is_purchasable );
	}
	add_filter( 'woocommerce_is_purchasable', 'swdp_check_if_is_purchasable', 10, 2 );

	/**
	 * Dont show the product price and remove the add-to-cart button for disabled products
	 * @param $price
	 * @param WC_Product $product
	 * @return string|void
	 */
	function swdp_show_price( $price, $product ) {

		// Verificamos se o produto esta habilitado
		$product_is_enable = swdp_is_product_enable( $product->get_id() ) == 'yes';
		if ( ! $product_is_enable || ! swdp_is_shop_enabled() ) {
			return __( 'Não disponível', 'storms' );
		}
		return $price;
	}
	add_filter( 'woocommerce_get_price_html', 'swdp_show_price', 10, 2 );

	/**
	 * Dont show the add to cart button on loop when the product is disabled
	 * @param $add_to_cart_link
	 * @param $product
	 * @return string
	 */
	function swdp_loop_add_to_cart_link( $add_to_cart_link, $product ) {

		// Verificamos se o produto esta habilitado
		$product_is_enable = swdp_is_product_enable( $product->get_id() ) == 'yes';
		if ( ! $product_is_enable || ! swdp_is_shop_enabled() ) {
			return '';
		}
		return $add_to_cart_link;
	}
	add_filter( 'woocommerce_loop_add_to_cart_link', 'swdp_loop_add_to_cart_link', 10, 2 );

	//</editor-fold>

	//<editor-fold desc="Adiciona o campo product_blocked na REST API do produto">

	/**
	 * REST API - Register Produto Bloqueado field.
	 */
	function swdp_register_product_blocked_field() {

		if ( ! function_exists( 'register_rest_field' ) ) {
			return;
		}

		// Campo Produto Bloqueado
		register_rest_field('product',
			'product_blocked',
			array(
				'get_callback' => 'get_product_blocked_callback',
				'update_callback' => 'update_product_blocked_callback',
				'schema' => array(
					'description' => __('Se um produto estiver bloqueado, ele não poderá ser comprado por nenhum cliente.', 'storms'),
					'type' => 'string',
					'context' => array('view', 'edit'),
				),
			)
		);
	}
	add_action( 'rest_api_init', 'swdp_register_product_blocked_field', 100 );

	/**
	 * REST API - Get Produto Bloqueado callback.
	 *
	 * @param array $data Details of current response.
	 * @param string $field Name of field.
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return string
	 */
	function get_product_blocked_callback($data, $field, $request) {

		$product_is_enable = swdp_is_product_enable( $data['id'] );
		return ( ( $product_is_enable == 'yes' ) ? 'no' : 'yes' );
	}

	/**
	 * REST API - Update Produto Bloqueado callback.
	 *
	 * @param string $value The value of the field.
	 * @param WP_Post $object The object from the response.
	 *
	 * @return bool
	 */
	function update_product_blocked_callback( $value, $object ) {

		$product_is_blocked = $value;
		if( empty( $product_is_blocked ) ) {
			$product_is_blocked = 'no';
		}

		// Se mudou para blocked, nao bloqueamos o produto
		$block = $product_is_blocked == 'yes';
		swdp_change_product_status( $object->get_id(), $block );
	}

	//</editor-fold>

	//<editor-fold desc="Funções de ação - Habilitar/desabilitar produtos e loja">

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

	function swdp_enable_shop() {
		// Enable WooCommerce Shop - Disable Catalog Mode
		update_option( '_swdp_enabled_shop', 'yes' );

		// The shop status has changed
		do_action( 'swdp_after_enable_shop' );
	}

	function swdp_disable_shop() {
		// Disable WooCommerce Shop - Enable Catalog Mode
		update_option( '_swdp_enabled_shop', 'no' );

		// The shop status has changed
		do_action( 'swdp_after_disable_shop' );
	}

	//</editor-fold>

	//<editor-fold desc="Registrar a API de Disable Products">

	function swdp_register_api_controllers() {
		include_once __DIR__ . '/class-storms-wc-disable-products-api.php';
		$controller = new Storms_WC_Disable_Products_API();
		$controller->register_routes();

		include_once __DIR__ . '/class-storms-wc-disable-products-shop-api.php';
		$controller = new Storms_WC_Disable_Products_Shop_API();
		$controller->register_routes();
	}
	add_action('rest_api_init', 'swdp_register_api_controllers' );

	//</editor-fold>

}
