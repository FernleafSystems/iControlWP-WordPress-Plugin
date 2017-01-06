<?php

if ( !class_exists( 'ICWP_APP_Processor_Plugin', false ) ):

	require_once( dirname(__FILE__).ICWP_DS.'base_app.php' );

	class ICWP_APP_Processor_Plugin extends ICWP_APP_Processor_BaseApp {

		/**
		 */
		public function run() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$oReqParams = $this->getRequestParams();

			add_filter( $oFO->doPluginPrefix( 'get_service_ips_v4' ), array( $this, 'getServiceIpAddressesV4' ) );
			add_filter( $oFO->doPluginPrefix( 'get_service_ips_v6' ), array( $this, 'getServiceIpAddressesV6' ) );

			add_filter( $oFO->doPluginPrefix( 'verify_site_can_handshake' ), array( $this, 'doVerifyCanHandshake' ) );
			add_filter( $oFO->doPluginPrefix( 'hide_plugin' ), array( $oFO, 'getIfHidePlugin' ) );
			add_filter( $oFO->doPluginPrefix( 'filter_hidePluginMenu' ), array( $oFO, 'getIfHidePlugin' ) );

			$oDp = $this->loadDataProcessor();
			if ( ( $oDp->FetchRequest( 'getworpitpluginurl', false ) == 1 ) || $oDp->FetchRequest( 'geticwppluginurl', false ) == 1 ) {
				$this->returnIcwpPluginUrl();
			}

//			add_action( 'wp_footer', array( $this, 'printPluginUri') );

			if ( $oReqParams->getIsApiCall() ) {
				$sApiHook = $oReqParams->getApiHook();
				if ( $oReqParams->getIsApiCall_Action() ) {
					if ( $sApiHook == 'immediate' ) {
						$this->doApiAction();
					}
					else {
						add_action( $sApiHook, array( $this, 'doApiAction' ), $oReqParams->getApiHookPriority() );
					}
				}
				else if ( $oReqParams->getIsApiCall_LinkSite() ) {
					if ( $sApiHook == 'immediate' ) {
						$this->doApiLinkSite();
					}
					else {
						add_action( $sApiHook, array( $this, 'doApiLinkSite' ), $oReqParams->getApiHookPriority() );
					}
				}
			}
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

			// First simply check SSL support
			if ( $this->loadEncryptProcessor()->getSupportsOpenSslSign() ) {
				return true;
			}

			$nTimeout = 20;
			$sHandshakeVerifyTestUrl = $oFO->getAppUrl( 'handshake_verify_test_url' );
			$aArgs = array(
				'timeout'		=> $nTimeout,
				'redirection'	=> $nTimeout,
				'sslverify'		=> true //this is default, but just to make sure.
			);
			$sResponse = $this->loadFileSystemProcessor()->getUrlContent( $sHandshakeVerifyTestUrl, $aArgs );

			if ( !$sResponse ) {
				return false;
			}
			$oJsonResponse = $oDp->doJsonDecode( trim( $sResponse ) );
			return ( is_object( $oJsonResponse ) && isset( $oJsonResponse->success ) && $oJsonResponse->success === true );
		}

		/**
		 * @uses die()
		 */
		public function doApiLinkSite() {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			require_once( dirname( __FILE__ ) . ICWP_DS . 'plugin_api_link.php' );
			$oLinkProcessor = new ICWP_APP_Processor_Plugin_SiteLink( $this->getFeatureOptions() );
			$oLinkResponse = $oLinkProcessor->run();
			$this->sendApiResponse( $oLinkResponse );
			die();
		}

		/**
		 * If any of the conditions are met and our plugin executes either the transport or link
		 * handlers, then all execution will end
		 * @uses die
		 * @return void
		 */
		public function doApiAction() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			$sApiChannel = $this->getApiChannel(); // also verifies it's a valid channel
			require_once( dirname(__FILE__).ICWP_DS. sprintf( 'plugin_api_%s.php', $sApiChannel ) );

			switch( $sApiChannel ) {

				case 'auth':
					$oApiProcessor = new ICWP_APP_Processor_Plugin_Api_Auth( $oFO );
					break;

				case 'retrieve':
					$oApiProcessor = new ICWP_APP_Processor_Plugin_Api_Retrieve( $oFO );
					break;

				case 'execute':
					$oApiProcessor = new ICWP_APP_Processor_Plugin_Api_Execute( $oFO );
					break;

				case 'internal':
					$oApiProcessor = new ICWP_APP_Processor_Plugin_Api_Internal( $oFO );
					break;

				case 'status':
					$oApiProcessor = new ICWP_APP_Processor_Plugin_Api_Status( $oFO );
					break;

				case 'login':
					$oApiProcessor = new ICWP_APP_Processor_Plugin_Api_Login( $oFO );
					break;

				default: // case 'index':
					echo $sApiChannel;
					require_once( dirname(__FILE__).ICWP_DS. sprintf( 'plugin_api_index.php', $sApiChannel ) );
					$oApiProcessor = new ICWP_APP_Processor_Plugin_Api_Index( $oFO );
					break;
			}

			$oApiResponse = $oApiProcessor->run();
			$this->sendApiResponse( $oApiResponse, true, $this->getRequestParams()->getParam( 'icwpenc', 0 ) );
			die();
		}

		/**
		 * @return string
		 */
		protected function getApiChannel() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();

			$sApiChannel = $this->getRequestParams()->getApiChannel();
			if ( !in_array( $sApiChannel, $oFO->getPermittedApiChannels() ) ) {
				$sApiChannel = 'index';
			}
			return $sApiChannel;
		}

		/**
		 * @return void
		 */
		protected function returnIcwpPluginUrl() {
			$this->flushResponse( $this->getController()->getPluginUrl(), false, false );
		}

		/**
		 * @uses die() / wp_die()
		 *
		 * @param ApiResponse|string $oResponse
		 * @param bool $bDoBinaryEncode
		 * @param bool $bEncrypt
		 */
		protected function sendApiResponse( $oResponse, $bDoBinaryEncode = true, $bEncrypt = false ) {

			if ( $oResponse->isDie() ) {
				wp_die( $oResponse->getErrorMessage() );
				return;
			}

			$oResponse->setAuthenticated( $this->loadWpUsersProcessor()->isUserLoggedIn() );

			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();

			$aDataBody = $oResponse->getData();
			if ( $bEncrypt && !empty( $aDataBody ) ) {
				$oEncryptedResult = $this->loadEncryptProcessor()->encryptDataPublicKey(
					$aDataBody,
					$oFO->getIcwpPublicKey()
				);

				if ( $oEncryptedResult->success ) {
					$oResponse->setData(
						array(
							'is_encrypted' => 1,
							'password' => $oEncryptedResult->encrypted_password,
							'sealed_data' => $oEncryptedResult->encrypted_data
						)
					);
				}
			}

			$sResponseBody = $oResponse->getResponsePackage();
			if ( $bDoBinaryEncode ) {
				$sResponseBody = base64_encode( $this->loadDataProcessor()->jsonEncode( $oResponse->getResponsePackage() ) );
			}
			$this->flushResponse( $sResponseBody, $bDoBinaryEncode ? 'json' : 'none', $bDoBinaryEncode );
		}

		/**
		 * @param string $sContent
		 * @param string $sEncoding
		 * @param bool $bBinary
		 */
		private function flushResponse( $sContent, $sEncoding = 'json', $bBinary = true ) {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();

			$this->sendHeaders( $bBinary );
			echo sprintf( "<icwp>%s</icwp>", $sContent );
			echo sprintf( "<icwpencoding>%s</icwpencoding>", $sEncoding );
			echo sprintf( "<icwpversion>%s</icwpversion>", $oFO->getVersion() );
			if ( !$oFO->getIsSiteLinked() && $this->loadEncryptProcessor()->getSupportsOpenSslSign() ) {
				/**
				 * displaying the key here is irrelevant because its use is essentially completely
				 * redundant for sites that support OpenSSL signatures.
				 */
				echo sprintf( "<icwpauth>%s</icwpauth>",  $oFO->getPluginAuthKey() );
			}
			die();
		}

		/**
		 * @param bool $bAsBinary
		 */
		private function sendHeaders( $bAsBinary = true ) {
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
	}

endif;
