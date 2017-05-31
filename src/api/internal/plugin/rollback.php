<?php

if ( !class_exists( 'ICWP_APP_Api_Internal_Plugin_Rollback', false ) ):

	require_once( dirname( dirname( __FILE__ ) ).ICWP_DS.'base.php' );

	class ICWP_APP_Api_Internal_Plugin_Rollback extends ICWP_APP_Api_Internal_Base {

		/**
		 * @return ApiResponse
		 */
		public function process() {
			$sContext = 'plugins';
			$aActionParams = $this->getActionParams();
			$sPluginFile = $aActionParams[ 'plugin_file' ];

			$oFS = $this->loadFileSystemProcessor();

			$sPluginDirName = dirname( $sPluginFile );
			$sPluginDirPath = path_join( WP_PLUGIN_DIR, $sPluginDirName );

			$sRollbackSourcePath = path_join( $this->getRollbackBaseDir(), $sContext.DIRECTORY_SEPARATOR.$sPluginDirName );
			if ( !$oFS->isDir( $sRollbackSourcePath ) || $oFS->isDirEmpty( $sRollbackSourcePath ) ) {
				return $this->fail( 'The Rollback directory is either empty or does not exist.' );
			}

			// empty the target directory (delete it and recreate)
			$oFS->deleteDir( $sPluginDirPath );
			$oFS->mkdir( $sPluginDirPath );
			copy_dir( $sRollbackSourcePath, $sPluginDirPath );
			$oFS->deleteDir( $sRollbackSourcePath );

			$aData = array(
				'wordpress-plugins'	=> $this->getWpCollector()->collectWordpressPlugins( null, true )
			);
			return $this->success( $aData );
		}

		/**
		 * @return string
		 */
		protected function getRollbackBaseDir() {
			return path_join( WP_CONTENT_DIR, 'icwp'.DIRECTORY_SEPARATOR.'rollback'.DIRECTORY_SEPARATOR );
		}
	}

endif;