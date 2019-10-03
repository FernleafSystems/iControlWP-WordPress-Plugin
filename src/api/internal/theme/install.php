<?php

class ICWP_APP_Api_Internal_Theme_Install extends ICWP_APP_Api_Internal_Base {

	/**
	 * @return ApiResponse
	 */
	public function process() {
		$aTheme = $this->getActionParams();

		if ( empty( $aTheme[ 'url' ] ) ) {
			return $this->fail(
				array(),
				'The URL was empty.'
			);
		}

		$sUrl = wp_http_validate_url( $aTheme[ 'url' ] );
		if ( !$sUrl ) {
			return $this->fail(
				array(),
				'The URL did not pass the WordPress HTTP URL Validation.'
			);
		}

		$oWpThemes = $this->loadWpFunctionsThemes();

		$aResult = $oWpThemes->install( $sUrl, $aTheme[ 'overwrite' ] );
		if ( isset( $aResult[ 'successful' ] ) && !$aResult[ 'successful' ] ) {
			return $this->fail( implode( ' | ', $aResult[ 'errors' ] ), $aResult );
		}

		$oInstalledTheme = $aResult[ 'theme_info' ];

		if ( is_string( $oInstalledTheme ) ) {
			$oInstalledTheme = wp_get_theme( $oInstalledTheme );
		}
		if ( !is_object( $oInstalledTheme ) || !$oInstalledTheme->exists() ) {
			return $this->fail( array(), 'After installation, cannot load the theme.' );
		}

		if ( isset( $aTheme[ 'activate' ] ) && $aTheme[ 'activate' ] == '1' ) {
			if ( $oInstalledTheme->get_stylesheet_directory() != get_stylesheet_directory() ) {
				$oWpThemes->activate( $oInstalledTheme->get_stylesheet() );
			}
		}

		$aData = array(
			'result'           => $aResult,
			'wordpress-themes' => $this->getWpCollector()->collectWordpressThemes()
		);

		return $this->success( $aData );
	}
}