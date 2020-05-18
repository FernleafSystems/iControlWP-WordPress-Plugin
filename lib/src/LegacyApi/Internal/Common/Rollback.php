<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Common;

trait Rollback {

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
			/** @var \WP_Filesystem_Base $wp_filesystem */
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