<?php

if ( !class_exists( 'ICWP_APP_Processor_Whitelabel', false ) ):

	require_once( dirname(__FILE__).ICWP_DS.'base_app.php' );

	class ICWP_APP_Processor_Whitelabel extends ICWP_APP_Processor_BaseApp {

		/**
		 */
		public function run() {
			add_filter( $this->getController()->doPluginPrefix( 'plugin_labels' ), array( $this, 'doRelabelPlugin' ) );
			add_filter( 'plugin_row_meta', array( $this, 'fRemoveDetailsMetaLink' ), 200, 2 );
			add_filter( $this->getController()->doPluginPrefix( 'main_extracontent' ), array( $this, 'addExtraContent' ) );
		}

		/**
		 * @param string $sExtraContent
		 * @return string
		 */
		public function addExtraContent( $sExtraContent = '' ) {
			$sExtraContent = apply_filters( 'icwp-whitelabel-extracontent', '' );
			return $sExtraContent;
		}

		/**
		 * @param array $aPluginLabels
		 * @return array
		 */
		public function doRelabelPlugin( $aPluginLabels ) {
			// these are the old white labelling keys which will be replaced upon final release of white labelling.
			$sServiceName = $this->getOption( 'service_name' );
			if ( !empty( $sServiceName ) ) {
				$aPluginLabels['Name'] = $sServiceName;
				$aPluginLabels['Title'] = $sServiceName;
				$aPluginLabels['Author'] = $sServiceName;
				$aPluginLabels['AuthorName'] = $sServiceName;
			}
			$sTagLine = $this->getOption( 'tag_line' );
			if ( !empty( $sTagLine ) ) {
				$aPluginLabels['Description'] = $sTagLine;
			}
			$sUrl = $this->getOption( 'plugin_home_url' );
			if ( !empty( $sUrl ) ) {
				$aPluginLabels['PluginURI'] = $sUrl;
				$aPluginLabels['AuthorURI'] = $sUrl;
			}

			$sIcon16 = $this->getOption( 'icon_url_16x16' );
			if ( !empty( $sIcon16 ) ) {
				$aPluginLabels['icon_url_16x16'] =  $sIcon16;
			}

			$sIcon32 = $this->getOption( 'icon_url_32x32' );
			if ( !empty( $sIcon32 ) ) {
				$aPluginLabels['icon_url_32x32'] =  $sIcon32;
			}

			return $aPluginLabels;
		}

		/**
		 * @filter
		 * @param array $aPluginMeta
		 * @param string $sPluginBaseFileName
		 * @return array
		 */
		public function fRemoveDetailsMetaLink( $aPluginMeta, $sPluginBaseFileName ) {
			if ( $sPluginBaseFileName == $this->getController()->getPluginBaseFile() ) {
				if ( isset( $aPluginMeta[2] ) && strpos( $aPluginMeta[2], 'plugin=worpit-admin-dashboard-plugin' ) > 0 ) {
					unset( $aPluginMeta[ 2 ] );
				}
			}
			return $aPluginMeta;
		}
	}

endif;