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

			$bForceUpdateCheck = (bool)$this->getRequestParams()->getParam( 'force_update_check', 0 );

//			$this->prepThirdPartyThemes(); TODO
			$aThemes = $this->getInstalledThemes();
			$oUpdates = $this->loadWpFunctionsProcessor()->updatesGather( 'themes', $bForceUpdateCheck ); // option to do another update check? force it?
			$aAutoUpdates = $this->getAutoUpdates( 'themes' );

			$sActiveThemeName = $this->loadWpFunctionsThemes()->getCurrentThemeName();

			foreach ( $aThemes as $sName => &$aData ) {
				$aData[ 'active' ]				= ( $sName == $sActiveThemeName );
				$aData[ 'auto_update' ]			= in_array( $sName, $aAutoUpdates );
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

			if ( $this->loadWpFunctionsProcessor()->getWordpressIsAtLeastVersion( '3.4' ) ) {

				/** @var WP_Theme[] $aThemeObjects */
				$aThemeObjects = $this->loadWpFunctionsThemes()->getThemes();

				foreach ( $aThemeObjects as $oTheme ) {
					$sName = $oTheme->get( 'Name' );
					$aThemes[$sName] = array(
						'Name'				=> $oTheme->display( 'Name' ),
						'Title'				=> $oTheme->offsetGet( 'Title' ),
						'Description'		=> $oTheme->offsetGet( 'Description' ),
						'Author'			=> $oTheme->offsetGet( 'Author' ),
						'Author Name'		=> $oTheme->offsetGet( 'Author Name' ),
						'Author URI'		=> $oTheme->offsetGet( 'Author URI' ),
						'Version'			=> $oTheme->offsetGet( 'Version' ),

						'Template'			=> $oTheme->offsetGet( 'Template' ),
						'Stylesheet'		=> $oTheme->offsetGet( 'Stylesheet' ),
						//'Template Dir'		=> $oTheme->offsetGet( 'Template Dir' ),
						//'Stylesheet Dir'	=> $oTheme->offsetGet( 'Stylesheet Dir' ),
						'Theme Root'		=> $oTheme->offsetGet( 'Theme Root' ),
						'Theme Root URI'	=> $oTheme->offsetGet( 'Theme Root URI' ),

						'Status'			=> $oTheme->offsetGet( 'Status' ),

						// We add our own
						'network_active'	=> $oTheme->is_allowed( 'network' )
					);
					$aThemes[$sName] = array_intersect_key( $aThemes[$sName], array_flip( $this->getDesiredThemeAttributes() ) );
				}
			}
			else {
				$aThemes = $this->loadWpFunctionsThemes()->getThemes();
				$fIsMultisite = is_multisite();
				$aNetworkAllowedThemes = function_exists( 'get_site_allowed_themes' )? get_site_allowed_themes() : array();

				// We add our own here because it's easier due to WordPress differences
				foreach( $aThemes as $sName => $aData ) {
					$aThemes[$sName]['network_active'] = $fIsMultisite && isset( $aNetworkAllowedThemes[ $aData['Stylesheet'] ] );
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
				'Network',
				'active',
				'network_active'
			);
		}
	}

endif;