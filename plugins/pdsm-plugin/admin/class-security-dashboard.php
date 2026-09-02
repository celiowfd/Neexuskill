<?php
/**
 * PDSM Hub - Security Dashboard
 */

namespace PDSM\Hub\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Security_Dashboard {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
	}

	public function add_menu_page() {
		add_submenu_page(
			'pdsm-hub',
			'Security Auto-Heal',
			'Security Auto-Heal',
			'manage_options',
			'pdsm-security',
			[ $this, 'render_page' ]
		);
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Acesso negado.' );
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html( 'Security Auto-Heal Dashboard' ) . '</h1>';
		
		echo '<div class="notice notice-info"><p>Gestão automatizada de vulnerabilidades e updates seguros via Atomic Swap.</p></div>';

		// Tabela Mock
		echo '<table class="wp-list-table widefat fixed striped">';
		echo '<thead><tr><th>Site</th><th>WP Core</th><th>PHP</th><th>Plugins Vulneráveis</th><th>Ação</th></tr></thead>';
		echo '<tbody>';
		echo '<tr>';
		echo '<td>' . esc_html( 'truffasdadinha.com.br' ) . '</td>';
		echo '<td>' . esc_html( '7.0.2 (Risco)' ) . '</td>';
		echo '<td>' . esc_html( '8.1.34 (Obsoleto)' ) . '</td>';
		echo '<td><span style="color:red">Backup Migration (CVE-2023-XXXX)</span>, Jetpack</td>';
		echo '<td><button class="button button-primary">Executar Auto-Heal</button></td>';
		echo '</tr>';
		echo '</tbody>';
		echo '</table>';

		echo '</div>';
	}
}
