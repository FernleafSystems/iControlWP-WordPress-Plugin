<?php

class ICWP_APP_Api_Internal_Theme_Install extends ICWP_APP_Api_Internal_Base {

	/**
	 * @inheritDoc
	 */
	public function process() {
		$aTheme = $this->getActionParams();

		if ( empty( $aTheme[ 'url' ] ) ) {
			return $this->fail( [], -1, 'The URL was empty.' );
		}

		$sUrl = wp_http_validate_url( $aTheme[ 'url' ] );
		if ( !$sUrl ) {
			return $this->fail( [], -1, 'The URL did not pass the WordPress HTTP URL Validation.' );
		}

		$oWpThemes = $this->loadWpFunctionsThemes();

		$aResult = $oWpThemes->install( $sUrl, $aTheme[ 'overwrite' ] );
		if ( empty( $aResult[ 'successful' ] ) ) {
			return $this->fail( implode( ' | ', $aResult[ 'errors' ] ), -1, $aResult );
		}

		$oInstalledTheme = $aResult[ 'theme_info' ];

		if ( is_string( $oInstalledTheme ) ) {
			$oInstalledTheme = wp_get_theme( $oInstalledTheme );
		}
		if ( !is_object( $oInstalledTheme ) || !$oInstalledTheme->exists() ) {
			return $this->fail( [], 'After installation, cannot load the theme.' );
		}

		if ( isset( $aTheme[ 'activate' ] ) && $aTheme[ 'activate' ] == '1' ) {
			if ( $oInstalledTheme->get_stylesheet_directory() != get_stylesheet_directory() ) {
				$oWpThemes->activate( $oInstalledTheme->get_stylesheet() );
			}
		}

		$aData = [
			'result'           => $aResult,
			'wordpress-themes' => $this->getWpCollector()->collectWordpressThemes()
		];

		return $this->success( $aData );
	}
}