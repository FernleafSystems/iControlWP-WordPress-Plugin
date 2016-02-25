<?php

if ( !class_exists( 'ICWP_APP_Processor_Plugin', false ) ):

	require_once( dirname(__FILE__).ICWP_DS.'base_plugin.php' );

	class ICWP_APP_Processor_Plugin extends ICWP_APP_Processor_BasePlugin {

		/**
		 */
		public function run() {
			if ( $this->getIsApiCall() ) {
				$this->maybeSetIsAdmin();
				add_action( $this->getApiHook(), array( $this, 'doAPI' ), $this->getApiHookPriority() );
			}

			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$oCon = $this->getController();

			add_filter( $oCon->doPluginPrefix( 'get_service_ips_v4' ), array( $this, 'getServiceIpAddressesV4' ) );
			add_filter( $oCon->doPluginPrefix( 'get_service_ips_v6' ), array( $this, 'getServiceIpAddressesV6' ) );

			add_filter( $oCon->doPluginPrefix( 'verify_site_can_handshake' ), array( $this, 'doVerifyCanHandshake' ) );
			add_filter( $oCon->doPluginPrefix( 'hide_plugin' ), array( $oFO, 'getIfHidePlugin' ) );
			add_filter( $oCon->doPluginPrefix( 'filter_hidePluginMenu' ), array( $oFO, 'getIfHidePlugin' ) );

			$oDp = $this->loadDataProcessor();
			if ( ( $oDp->FetchRequest( 'getworpitpluginurl', false ) == 1 ) || $oDp->FetchRequest( 'geticwppluginurl', false ) == 1 ) {
				$this->returnIcwpPluginUrl();
			}

			add_action( 'wp_footer', array( $this, 'printPluginUri') );
		}

		/**
		 * @return bool
		 */
		protected function maybeSetIsAdmin() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$sSetWpAdmin = $oFO->fetchIcwpRequestParam( 'set_is_admin', 0 );
			if ( $sSetWpAdmin == 1 && !defined( 'WP_ADMIN' ) ) {
				define( 'WP_ADMIN', true );
			}
			return ( defined( 'WP_ADMIN' ) && WP_ADMIN );
		}

		/**
		 * @return string
		 */
		protected function getApiHook() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$sApiHook = $oFO->fetchIcwpRequestParam( 'api_hook', '' );
			if ( empty( $sApiHook ) ) {
				$sApiHook = 'wp_loaded';
				if ( class_exists( 'WooDojo_Maintenance_Mode', false ) || class_exists( 'ITSEC_Core', false ) ) {
					$sApiHook = 'init';
				}
			}
			return $sApiHook;
		}

		/**
		 * @return int
		 */
		protected function getApiHookPriority() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$nHookPriority = $oFO->fetchIcwpRequestParam( 'api_priority', '' );
			if ( empty( $nHookPriority ) || !is_numeric( $nHookPriority )) {
				$nHookPriority = 1;
				if ( class_exists( 'ITSEC_Core', false ) ) {
					$nHookPriority = 100;
				}
			}
			return $nHookPriority;
		}

		/**
		 * @return array
		 */
		public function getServiceIpAddressesV4() {
			return $this->getValidServiceIps( 'ipv4' );
		}

		/**
		 * @return array
		 */
		public function getServiceIpAddressesV6() {
			return $this->getValidServiceIps( 'ipv6' );
		}

		/**
		 * @param string $sIps
		 * @return array
		 */
		protected function getValidServiceIps( $sIps = 'ipv4' ) {
			$aLists = $this->getFeatureOptions()->getDefinition( 'service_ip_addresses' );
			if ( isset( $aLists[$sIps] ) && is_array( $aLists[$sIps] ) && isset( $aLists[$sIps]['valid'] ) && is_array( $aLists[$sIps]['valid'] ) ) {
				return $aLists[$sIps]['valid'];
			}
			return array();
		}

		/**
		 * @param boolean $bCanHandshake
		 * @return boolean
		 */
		public function doVerifyCanHandshake( $bCanHandshake ) {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$oDp = $this->loadDataProcessor();

			$oFO->setOpt( 'time_last_check_can_handshake', $oDp->time() );

			// First simply check SSL usage
			if ( $oDp->getCanOpensslSign() ) {
				return true;
			}

			$nTimeout = 20;
			$sHandshakeVerifyTestUrl = $oFO->getAppUrl( 'handshake_verify_test_url' );
			$aArgs = array(
				'timeout'		=> $nTimeout,
				'redirection'	=> $nTimeout,
				'sslverify'		=> true //this is default, but just to make sure.
			);
			$oFs = $this->loadFileSystemProcessor();
			$sResponse = $oFs->getUrlContent( $sHandshakeVerifyTestUrl, $aArgs );

			if ( !$sResponse ) {
				return false;
			}
			$oJsonResponse = $oDp->doJsonDecode( trim( $sResponse ) );
			return ( is_object( $oJsonResponse ) && isset( $oJsonResponse->success ) && $oJsonResponse->success === true );
		}

		/**
		 * If any of the conditions are met and our plugin executes either the transport or link
		 * handlers, then all execution will end
		 * @uses die
		 * @return void
		 */
		public function doAPI() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();

			if ( $oFO->fetchIcwpRequestParam( 'worpit_link', 0 ) == 1 ) {
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				require_once( dirname(__FILE__).ICWP_DS.'plugin_sitelink.php' );
				$oLinkProcessor = new ICWP_APP_Processor_Plugin_SiteLink( $this->getFeatureOptions() );
				$oLinkResponse = $oLinkProcessor->run();
				$this->sendApiResponse( $oLinkResponse );
				die();
			}
			else if ( $oFO->fetchIcwpRequestParam( 'worpit_api', 0 ) == 1 ) {
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				require_once( dirname(__FILE__).ICWP_DS.'plugin_api.php' );
				$oApiProcessor = new ICWP_APP_Processor_Plugin_Api( $this->getFeatureOptions() );
				$oApiResponse = $oApiProcessor->run();
				$this->sendApiResponse( $oApiResponse );
				die();
			}
		}

		/**
		 * @return bool
		 */
		protected function getIsApiCall() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			return ( ( $oFO->fetchIcwpRequestParam( 'worpit_link', 0 ) == 1 ) || ( $oFO->fetchIcwpRequestParam( 'worpit_api', 0 ) == 1 ) );
		}

		/**
		 * @uses die() / wp_die()
		 *
		 * @param stdClass|string $mResponse
		 * @param boolean $bDoBinaryEncode
		 */
		protected function sendApiResponse( $mResponse, $bDoBinaryEncode = true ) {

			if ( is_object( $mResponse ) && isset( $mResponse->die ) && $mResponse->die ) {
				wp_die( $mResponse->error_message );
				return;
			}

			$oResponse = $bDoBinaryEncode ? base64_encode( serialize( $mResponse ) ) : $mResponse;

			$this->sendHeaders( $bDoBinaryEncode );
			echo "<icwp>".$oResponse."</icwp>";
			echo "<icwpversion>".$this->getFeatureOptions()->getVersion()."</icwpversion>";
			die();
		}

		/**
		 * @param bool $bAsBinary
		 */
		protected function sendHeaders( $bAsBinary = true ) {
			if ( $bAsBinary ) {
				header( "Content-type: application/octet-stream" );
				header( "Content-Transfer-Encoding: binary");
			}
			else {
				header( "Content-type: text/html" );
				header( "Content-Transfer-Encoding: quoted-printable");
			}
		}

		/**
		 * @return void
		 */
		public function printPluginUri() {
			if ( $this->getOption( 'assigned' ) !== 'Y' ) {
				echo '<!-- Worpit Plugin: '.$this->getController()->getPluginUrl().' -->';
			}
		}

		/**
		 * @return void
		 */
		protected function returnIcwpPluginUrl() {
			$this->sendApiResponse( $this->getController()->getPluginUrl(), false );
		}

		/**
		 * @param array $aNoticeAttributes
		 * @return array
		 */
		public function addNotice_add_site( $aNoticeAttributes ) {

			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$oCon = $this->getController();

			if ( $oFO->getIsSiteLinked() || !$oCon->getIsValidAdminArea() ) {
				return;
			}

			$sServiceName = $oCon->getHumanName();
			$sAuthKey = $oFO->getPluginAuthKey();

			$aRenderData = array(
				'notice_attributes' => $aNoticeAttributes,
				'strings' => array(
					'add_site' => sprintf( "Now that you've installed the %s plugin, you need to connect this site to your %s account.", $sServiceName, $sServiceName ),
					'use_key' => sprintf( 'Use the following Authentication Key when prompted %s.', '<span class="the-key">'.$sAuthKey.'</span>' ),
				)
			);
			$this->insertAdminNotice( $aRenderData );
		}
	}

endif;
