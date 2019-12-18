<?php

class ICWP_APP_FeatureHandler_Autoupdates extends ICWP_APP_FeatureHandler_BaseApp {

	/**
	 * @param string $sContext
	 * @return array
	 */
	public function getAutoUpdates( $sContext = 'plugins' ) {
		$aUpdates = $this->getOpt( 'auto_update_'.$sContext, [] );
		return is_array( $aUpdates ) ? $aUpdates : [];
	}

	/**
	 * @param array  $aUpdateItems
	 * @param string $sContext
	 * @return array
	 */
	public function setAutoUpdates( $aUpdateItems, $sContext = 'plugins' ) {
		if ( is_array( $aUpdateItems ) ) {
			$this->setOpt( 'auto_update_'.$sContext, $aUpdateItems );
		}
	}

	/**
	 * @param string $sSlug
	 * @param bool   $bSetOn
	 * @param string $sContext
	 */
	public function setAutoUpdate( $sSlug, $bSetOn = false, $sContext = 'plugins' ) {
		$aAutoUpdateItems = $this->getAutoUpdates( $sContext );

		$nInArray = array_search( $sSlug, $aAutoUpdateItems );
		if ( $bSetOn && $nInArray === false ) {
			$aAutoUpdateItems[] = $sSlug;
		}
		elseif ( !$bSetOn && $nInArray !== false ) {
			unset( $aAutoUpdateItems[ $nInArray ] );
		}
		$this->setAutoUpdates( $aAutoUpdateItems, $sContext );
	}
}