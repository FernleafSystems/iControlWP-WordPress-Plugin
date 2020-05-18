<?php

class ICWP_APP_Api_Internal_Collect_Sync extends ICWP_APP_Api_Internal_Collect_Base {

	/**
	 * @inheritDoc
	 */
	public function process() {
		$aData = [
			'capabilities'    => $this->getCollector_Capabilities()->collect(),
			'wordpress-info'  => $this->getCollector_WordPressInfo()->collect(),
			'wordpress-paths' => $this->getCollector_Paths()->collect(),
		];

		if ( class_exists( 'DirectoryIterator', false ) ) {
			$this->cleanRollbackData();
			$this->cleanRollbackDir();
		}

		return $this->success( $aData );
	}

	/**
	 * @return bool
	 */
	protected function cleanRollbackData() {

		$nBoundary = time() - WEEK_IN_SECONDS;
		$oFs = $this->loadFS();

		$aContexts = [ 'plugins', 'themes' ];
		foreach ( $aContexts as $sContext ) {
			$sWorkingDir = path_join( $this->getRollbackBaseDir(), $sContext );
			if ( !is_dir( $sWorkingDir ) ) {
				continue;
			}
			try {
				$oDirIt = new DirectoryIterator( $sWorkingDir );
				foreach ( $oDirIt as $oFileItem ) {
					if ( $oFileItem->isDir() && !$oFileItem->isDot() ) {
						if ( $oFileItem->getMTime() < $nBoundary ) {
							$oFs->deleteDir( $oFileItem->getPathname() );
						}
					}
				}
			}
			catch ( Exception $oE ) { //  UnexpectedValueException, RuntimeException, Exception
				continue;
			}
		}

		return true;
	}

	/**
	 */
	protected function cleanRollbackDir() {
		$oFs = $this->loadFS();

		try {
			$oDirIt = new DirectoryIterator( $this->getRollbackBaseDir() );
			foreach ( $oDirIt as $oFileItem ) {
				if ( !$oFileItem->isDot() ) {

					if ( !$oFileItem->isDir() ) {
						$oFs->deleteFile( $oFileItem->getPathname() );
					}
					elseif ( !in_array( $oFileItem->getFilename(), [ 'plugins', 'themes' ] ) ) {
						$oFs->deleteDir( $oFileItem->getPathname() );
					}
				}
			}
		}
		catch ( Exception $oE ) {
			//  UnexpectedValueException, RuntimeException, Exception
		}
	}

	/**
	 * @return string
	 */
	protected function getRollbackBaseDir() {
		return path_join( WP_CONTENT_DIR, 'icwp/rollback/' );
	}
}