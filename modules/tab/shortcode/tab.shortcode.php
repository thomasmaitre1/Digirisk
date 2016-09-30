<?php

namespace digi;
/**
* Ajout le shortcode qui permet d'afficher les onglets
*
* @author Jimmy Latour <jimmy.latour@gmail.com>
* @version 0.1
* @copyright 2015-2016 Eoxia
* @package tab
* @subpackage shortcode
*/

if ( !defined( 'ABSPATH' ) ) exit;

class tab_shortcode {
	/**
	* Le constructeur
	*/
	public function __construct() {
		add_shortcode( 'digi-tab', array( $this, 'callback_digi_tab' ), 1 );
	}

	/**
	* Affiches le template des onglets
	*/
	public function callback_digi_tab( $param ) {
		$type = $param['type'];
		$display = $param['display'];

    $list_tab = apply_filters( 'digi_tab', array() );

		view_util::exec( 'tab', 'list', array( 'type' => $type, 'display' => $display, 'list_tab' => $list_tab ) );
	}
}

new tab_shortcode();
