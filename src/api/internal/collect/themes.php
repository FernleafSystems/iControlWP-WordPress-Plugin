<?php

if ( !class_exists( 'ICWP_APP_Api_Internal_Collect_Themes', false ) ):

	require_once( dirname( __FILE__ ).ICWP_DS.'base.php' );

	class ICWP_APP_Api_Internal_Collect_Themes extends ICWP_APP_Api_Internal_Collect_Base {
		/**
		 * @return ApiResponse
		 */
		public function process() {
			$aData = array(
				'wordpress-themes' => $this->collect(),
			);
			return $this->success( $aData );
		}

		/**
		 * @return array								associative: ThemeStylesheet => ThemeData
		 */
		public function collect() {

//			$this->prepThirdPartyThemes(); TODO
			$aThemes = $this->getInstalledThemes();
			$oUpdates = $this->loadWpFunctions()->updatesGather( 'themes', $this->isForceUpdateCheck() ); // option to do another update check? force it?
			$aAutoUpdates = $this->getAutoUpdates( 'themes' );

			$sActiveThemeStylesheet = $this->loadWpFunctionsThemes()->getCurrent()->get_stylesheet();

			foreach ( $aThemes as $sStylesheet => &$aData ) {
				$aData[ 'active' ]				= ( $sStylesheet == $sActiveThemeStylesheet );
				$aData[ 'auto_update' ]			= in_array( $sStylesheet, $aAutoUpdates );
				$aData[ 'update_available' ]	= isset( $oUpdates->response[ $aData[ 'Stylesheet' ] ] ) ? 1 : 0;
				$aData[ 'update_info' ]			= '';

				if ( $aData['update_available'] ) {
					$oUpdateInfo = $oUpdates->response[ $aData[ 'Stylesheet' ] ];

					if ( isset( $oUpdateInfo['sections'] ) ) {
						unset( $oUpdateInfo['sections'] ); // TODO: Filter unwanted data using set array of keys
					}
					$aData['update_info'] = json_encode( $oUpdateInfo );
				}
			}
			return $aThemes;
		}

		/**
		 * The method for getting installed themes changed in version 3.4+ so this function normalizes everything.
		 *
		 * @return array
		 */
		public function getInstalledThemes() {

			$aThemes = array();

			if ( $this->loadWpFunctions()->getWordpressIsAtLeastVersion( '3.4' ) ) {

				/** @var WP_Theme[] $aThemeObjects */
				$aThemeObjects = $this->loadWpFunctionsThemes()->getThemes();

				$bHasChildThemes = false;

				foreach ( $aThemeObjects as $oTheme ) {

					$bIsChildTheme = ( $oTheme->offsetGet( 'Template' ) != $oTheme->offsetGet( 'Stylesheet' ) );
					$bHasChildThemes = $bHasChildThemes || $bIsChildTheme;

					$sStylesheet = $oTheme->offsetGet( 'Stylesheet' );
					$aThemes[ $sStylesheet ] = array(
						'Name'				=> $oTheme->display( 'Name' ),
						'Title'				=> $oTheme->offsetGet( 'Title' ),
						'Description'		=> $oTheme->offsetGet( 'Description' ),
						'Author'			=> $oTheme->offsetGet( 'Author' ),
						'Author Name'		=> $oTheme->offsetGet( 'Author Name' ),
						'Author URI'		=> $oTheme->offsetGet( 'Author URI' ),
						'Version'			=> $oTheme->offsetGet( 'Version' ),
						'Template'			=> $oTheme->offsetGet( 'Template' ),
						'Stylesheet'		=> $sStylesheet,
						//'Template Dir'		=> $oTheme->offsetGet( 'Template Dir' ),
						//'Stylesheet Dir'	=> $oTheme->offsetGet( 'Stylesheet Dir' ),
						'Theme Root'		=> $oTheme->offsetGet( 'Theme Root' ),
						'Theme Root URI'	=> $oTheme->offsetGet( 'Theme Root URI' ),

						'Status'			=> $oTheme->offsetGet( 'Status' ),

						'IsChild'			=> $bIsChildTheme ? 1 : 0,
						'IsParent'			=> 0,

						// We add our own
						'network_active'	=> $oTheme->is_allowed( 'network' )
					);
					$aThemes[ $sStylesheet ] = array_intersect_key(
						$aThemes[ $sStylesheet ],
						array_flip( $this->getDesiredThemeAttributes() )
					);
				}

				if ( $bHasChildThemes ) {
					foreach ( $aThemes as $aMaybeChildTheme ) {
						if ( $aMaybeChildTheme[ 'IsChild' ] ) {
							foreach ( $aThemes as &$aMaybeParentTheme ) {
								if ( $aMaybeParentTheme[ 'Stylesheet' ] == $aMaybeChildTheme[ 'Template' ] ) {
									$aMaybeParentTheme[ 'IsParent' ] = 1;
								}
							}
						}
					}
				}
			}
			else {
				$aThemes = $this->loadWpFunctionsThemes()->getThemes();
				$fIsMultisite = is_multisite();
				$aNetworkAllowedThemes = function_exists( 'get_site_allowed_themes' )? get_site_allowed_themes() : array();

				// We add our own here because it's easier due to WordPress differences
				foreach( $aThemes as $sName => $aData ) {
					$sStylesheet = $aData[ 'Stylesheet' ];
					$aData[ 'network_active' ] = $fIsMultisite && isset( $aNetworkAllowedThemes[ $sStylesheet ] );
					unset( $aThemes[ $sName ] );
					$aThemes[ $sStylesheet ] = $aData;
				}
			}

			return $aThemes;
		}

		/**
		 * @return array
		 */
		protected function getDesiredThemeAttributes() {
			return array(
				'Name',
				'Version',
				'Template',
				'Stylesheet',
				'IsChild',
				'IsParent',
				'Network',
				'active',
				'network_active'
			);
		}
	}

endif;