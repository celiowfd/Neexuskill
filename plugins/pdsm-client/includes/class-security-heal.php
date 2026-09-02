<?php
/**
 * PDSM Client - Security Auto-Heal Module
 * 
 * Normas Aplicáveis: ISO/IEC 25010 (Segurança), ISO/IEC 29119 (Testes de Rollback)
 */

namespace PDSM\Client;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Security_Heal {

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
		register_rest_route( 'pdsm/v1', '/agent/security-scan', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'do_security_scan' ],
			'permission_callback' => [ $this, 'verify_hmac' ]
		] );

		register_rest_route( 'pdsm/v1', '/agent/apply-update', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'apply_update' ],
			'permission_callback' => [ $this, 'verify_hmac' ]
		] );
	}

	public function verify_hmac( \WP_REST_Request $request ) {
		// Simulação da validação HMAC-SHA256 (presumida como importada do Client_API)
		$signature = $request->get_header( 'X-PDSM-Signature' );
		if ( empty( $signature ) ) {
			return new \WP_Error( 'forbidden', 'Assinatura HMAC ausente', [ 'status' => 403 ] );
		}
		// ...validação completa omitida por brevidade...
		return true;
	}

	public function do_security_scan( \WP_REST_Request $request ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();
		$themes = wp_get_themes();
		
		$plugin_data = [];
		foreach ( $plugins as $file => $data ) {
			$plugin_data[] = [
				'slug'    => dirname( $file ),
				'file'    => $file,
				'version' => $data['Version'],
				'name'    => $data['Name']
			];
		}

		$theme_data = [];
		foreach ( $themes as $slug => $theme ) {
			$theme_data[] = [
				'slug'    => $slug,
				'version' => $theme->get('Version')
			];
		}

		return rest_ensure_response( [
			'core' => [
				'version' => get_bloginfo( 'version' )
			],
			'php' => [
				'version' => PHP_VERSION,
				'supported' => version_compare( PHP_VERSION, '8.1', '>=' )
			],
			'plugins' => $plugin_data,
			'themes'  => $theme_data
		] );
	}

	public function apply_update( \WP_REST_Request $request ) {
		$type = $request->get_param( 'type' );
		$slug = $request->get_param( 'slug' );
		$version = $request->get_param( 'version' );

		if ( ! in_array( $type, [ 'plugin', 'theme' ], true ) ) {
			return new \WP_Error( 'invalid_type', 'Tipo de update inválido', [ 'status' => 400 ] );
		}

		// 1. Simulação do processo de Backup local
		$target_dir = ( $type === 'plugin' ) ? WP_PLUGIN_DIR . '/' . $slug : get_theme_root() . '/' . $slug;
		$backup_dir = WP_CONTENT_DIR . '/backups/pdsm/' . $slug . '_' . time();

		if ( file_exists( $target_dir ) ) {
			wp_mkdir_p( dirname( $backup_dir ) );
			rename( $target_dir, $backup_dir ); // Move atual para backup
		}

		// 2. Download do pacote (WordPress.org repo simulado)
		$download_url = "https://downloads.wordpress.org/{$type}/{$slug}.{$version}.zip";
		
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$tmp_file = download_url( $download_url );

		if ( is_wp_error( $tmp_file ) ) {
			// Rollback
			if ( file_exists( $backup_dir ) ) {
				rename( $backup_dir, $target_dir );
			}
			return new \WP_Error( 'download_failed', 'Falha ao baixar pacote', [ 'status' => 500 ] );
		}

		// 3. Extração (Atomic Swap)
		WP_Filesystem();
		global $wp_filesystem;
		
		$unzip_result = unzip_file( $tmp_file, dirname( $target_dir ) );
		unlink( $tmp_file ); // Apaga tmp

		if ( is_wp_error( $unzip_result ) ) {
			// Rollback
			if ( file_exists( $backup_dir ) ) {
				rename( $backup_dir, $target_dir );
			}
			return new \WP_Error( 'unzip_failed', 'Falha ao extrair pacote', [ 'status' => 500 ] );
		}

		// 4. Teste de Sanidade
		$health_check = wp_remote_get( home_url() );
		$body = wp_remote_retrieve_body( $health_check );
		$status_code = wp_remote_retrieve_response_code( $health_check );

		if ( $status_code !== 200 || strpos( $body, 'Fatal error' ) !== false ) {
			// ROLLBACK AUTOMÁTICO EM CASO DE CRASH
			$wp_filesystem->delete( $target_dir, true );
			rename( $backup_dir, $target_dir );
			return rest_ensure_response( [
				'status' => 'rolled_back',
				'message' => 'O site quebrou após o update. Rollback efetuado com sucesso.'
			] );
		}

		// Cleanup backup if success
		$wp_filesystem->delete( $backup_dir, true );

		return rest_ensure_response( [
			'status' => 'success',
			'message' => 'Update aplicado com sucesso e testado via Health Check.'
		] );
	}
}
