<?php

if ( class_exists( 'ICWP_APP_Api_Internal_Common_Plugins', false ) ) {
	return;
}

require_once( dirname( dirname( __FILE__ ) ).'/base.php' );

class ICWP_APP_Api_Internal_Common_Plugins extends ICWP_APP_Api_Internal_Base {

	/**
	 * @param string $sFile
	 * @param string $sContext
	 * @return boolean
	 */
	public function prepRollbackData( $sFile, $sContext = 'plugins' ) {
		$sPluginDirName = dirname( $sFile );
		$sPluginDirPath = path_join( WP_PLUGIN_DIR, $sPluginDirName );

		$sDestinationPath = path_join( $this->getRollbackBaseDir(), $sContext.DIRECTORY_SEPARATOR.$sPluginDirName );
		if ( is_dir( $sDestinationPath ) ) {
			/** @var WP_Filesystem_Base $wp_filesystem */
			global $wp_filesystem;
			$wp_filesystem->rmdir( $sDestinationPath, true );
		}
		wp_mkdir_p( $sDestinationPath );
		return copy_dir( $sPluginDirPath, $sDestinationPath );
	}

	/**
	 * @return string
	 */
	protected function getRollbackBaseDir() {
		return path_join( WP_CONTENT_DIR, 'icwp/rollback/' );
	}
}