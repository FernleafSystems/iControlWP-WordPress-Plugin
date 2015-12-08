<?php

if ( !class_exists( 'ICWP_APP_Processor_Plugin', false ) ):

	require_once( dirname(__FILE__).ICWP_DS.'base.php' );

	class ICWP_APP_Processor_Plugin extends ICWP_APP_Processor_Base {

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

			add_filter( $oFO->doPluginPrefix( 'admin_notices' ), array( $this, 'adminNoticeFeedback' ) );
			add_filter( $oFO->doPluginPrefix( 'admin_notices' ), array( $this, 'adminNoticeAddSite' ) );

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
			$aLists = $this->getOption( 'service_ip_addresses_'.$sIps, array() );
			if ( isset( $aLists['valid'] ) && is_array( $aLists['valid'] ) ) {
				return $aLists['valid'];
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

			// First simply check SSL support
			if ( $this->loadEncryptProcessor()->getSupportsOpenSslSign() ) {
				return true;
			}

			$nTimeout = 20;
			$sHandshakeVerifyTestUrl = $oFO->getOpt( 'handshake_verify_test_url' );
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
				$oLinkProcessor = new ICWP_APP_Processor_Plugin_SiteLink( $oFO );
				$oLinkResponse = $oLinkProcessor->run();
				$this->sendApiResponse( $oLinkResponse );
				die();
			}
			else if ( $oFO->fetchIcwpRequestParam( 'worpit_api', 0 ) == 1 ) {
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

				$sApiChannel = $this->getApiChannel(); // also verifies it's a valid channel
				require_once( dirname(__FILE__).ICWP_DS.'plugin_api_'.$sApiChannel.'.php' );

				switch( $sApiChannel ) {

					case 'retrieve':
						$oApiProcessor = new ICWP_APP_Processor_Plugin_Api_Retrieve( $oFO );
						break;

					case 'execute':
						$oApiProcessor = new ICWP_APP_Processor_Plugin_Api_Execute( $oFO );
						break;

					case 'internal':
						$oApiProcessor = new ICWP_APP_Processor_Plugin_Api_Internal( $oFO );
						break;

					case 'login':
						$oApiProcessor = new ICWP_APP_Processor_Plugin_Api_Login( $oFO );
						break;

					default: // case 'index':
						$oApiProcessor = new ICWP_APP_Processor_Plugin_Api_Index( $oFO );
						break;
				}

				$oApiResponse = $oApiProcessor->run();
				$this->sendApiResponse( $oApiResponse, true, $oFO->fetchIcwpRequestParam( 'icwpenc', 0 ) == 1 );
				die();
			}
		}

		/**
		 * @return string
		 */
		protected function getApiChannel() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();

			$sApiChannel = $oFO->fetchIcwpRequestParam( 'm', 'index' );
			if ( !in_array( $sApiChannel, $oFO->getPermittedApiChannels() ) ) {
				$sApiChannel = 'index';
			}
			return $sApiChannel;
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
		 * @param stdClass|string $oResponse
		 * @param boolean $bDoBinaryEncode
		 * @param bool $bEncrypt
		 */
		protected function sendApiResponse( $oResponse, $bDoBinaryEncode = true, $bEncrypt = false ) {

			if ( is_object( $oResponse ) && isset( $oResponse->die ) && $oResponse->die ) {
				wp_die( $oResponse->error_message );
				return;
			}

			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();

			if ( $bEncrypt && !empty( $oResponse->data ) ) {
				$oEncryptedResult = $this->loadEncryptProcessor()->encryptDataPublicKey( $oResponse->data, $oFO->getIcwpPublicKey() );

				if ( $oEncryptedResult->success ) {
					$oResponse->data = array(
						'is_encrypted' => 1,
						'password' => $oEncryptedResult->encrypted_password,
						'sealed_data' => $oEncryptedResult->encrypted_data
					);
				}
			}

			if ( $bDoBinaryEncode ) {
				$oResponse = base64_encode( serialize( $oResponse ) );
			}

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
		 * @param array $aAdminNotices
		 * @return array
		 */
		public function adminNoticeFeedback( $aAdminNotices ) {
			$aAdminFeedbackNotice = $this->getOption( 'feedback_admin_notice' );

			if ( $this->getController()->getIsValidAdminArea() && !empty( $aAdminFeedbackNotice ) && is_array( $aAdminFeedbackNotice ) ) {

				foreach ( $aAdminFeedbackNotice as $sNotice ) {
					if ( empty( $sNotice ) || !is_string( $sNotice ) ) {
						continue;
					}
					$aAdminNotices[] = $this->getAdminNoticeHtml( '<p>'.$sNotice.'</p>', 'updated', false );
				}
				/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
				$oFO = $this->getFeatureOptions();
				$oFO->doClearAdminFeedback( 'feedback_admin_notice', array() );
			}
			return $aAdminNotices;
		}

		/**
		 * @param array $aAdminNotices
		 * @return array
		 */
		public function adminNoticeAddSite( $aAdminNotices ) {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$oCon = $this->getController();

			if ( $oCon->getIsValidAdminArea() && !$oFO->getIsSiteLinked() ) {

				$sAckPluginNotice = $this->loadWpUsersProcessor()->getUserMeta( $oCon->doPluginOptionPrefix( 'ack_plugin_notice' ) );
				$nCurrentUserId = 0;
				$sNonce = wp_nonce_field( $oCon->getPluginPrefix() );
				$sServiceName = $oCon->getHumanName();
				$sFormAction = $oCon->getPluginUrl_AdminMainPage();
				$sAuthKey = $oFO->getPluginAuthKey();
				
				ob_start();
				include( $oFO->getViewSnippet( 'admin_notice_add_site' ) );
				$sNoticeMessage = ob_get_contents();
				ob_end_clean();

				$aAdminNotices[] = $this->getAdminNoticeHtml( $sNoticeMessage, 'error', false );
			}
			return $aAdminNotices;
		}

		/**
		 * @return int
		 */
		protected function getInstallationDays() {
			$nTimeInstalled = $this->getFeatureOptions()->getOpt( 'installation_time' );
			if ( empty( $nTimeInstalled ) ) {
				return 0;
			}
			return round( ( $this->loadDataProcessor()->time() - $nTimeInstalled ) / DAY_IN_SECONDS );
		}

	}

endif;
