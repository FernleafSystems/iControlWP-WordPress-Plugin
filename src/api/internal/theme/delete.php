<?php

class ICWP_APP_Api_Internal_Theme_Delete extends ICWP_APP_Api_Internal_Base {

	/**
	 * @return ApiResponse
	 */
	public function process() {
		$aActionParams = $this->getActionParams();
		$sStylesheet = $aActionParams[ 'theme_file' ];

		if ( empty( $sStylesheet ) ) {
			return $this->fail(
				[],
				'Stylesheet provided was empty.'
			);
		}

		$oWpThemes = $this->loadWpFunctionsThemes();
		if ( !$oWpThemes->getExists( $sStylesheet ) ) {
			return $this->fail(
				[ 'stylesheet' => $sStylesheet ],
				sprintf( 'Theme does not exist with Stylesheet: %s', $sStylesheet )
			);
		}

		$oThemeToDelete = $oWpThemes->getTheme( $sStylesheet );
		if ( $oThemeToDelete->get_stylesheet_directory() == get_stylesheet_directory() ) {
			return $this->fail(
				[ 'stylesheet' => $sStylesheet ],
				sprintf( 'Cannot uninstall the currently active WordPress theme: %s', $sStylesheet )
			);
		}

		$mResult = $oWpThemes->delete( $sStylesheet );

		$aData = [
			'result'           => $mResult,
			'wordpress-themes' => $this->getWpCollector()->collectWordpressThemes(),
			//Need to send back all themes so we can update the one that got deleted
		];
		return $this->success( $aData );
	}
}