<?php

if ( !class_exists( 'ICWP_APP_Processor_BasePlugin', false ) ):

	require_once( dirname(__FILE__).ICWP_DS.'base_app.php' );

	class ICWP_APP_Processor_BasePlugin extends ICWP_APP_Processor_BaseApp {

		/**
		 */
		public function init() {
			$oFO = $this->getFeatureOptions();
			add_filter( $oFO->doPluginPrefix( 'show_marketing' ), array( $this, 'getIsShowMarketing' ) );
			add_filter( $oFO->doPluginPrefix( 'delete_on_deactivate' ), array( $this, 'getIsDeleteOnDeactivate' ) );
		}

		/**
		 */
		public function run() {}

		/**
		 * @param array $aNoticeAttributes
		 * @return bool
		 */
		protected function getIfDisplayAdminNotice( $aNoticeAttributes ) {

			if ( ! parent::getIfDisplayAdminNotice( $aNoticeAttributes ) ) {
				return false;
			}

			if ( isset( $aNoticeAttributes['delay_days'] ) && is_int( $aNoticeAttributes['delay_days'] ) && ( $this->getInstallationDays() <= $aNoticeAttributes['delay_days'] ) ) {
				return false;
			}

			return true;
		}

		public function addNotice_rate_plugin( $aNoticeAttributes ) {

			$aRenderData = array(
				'notice_attributes' => $aNoticeAttributes,
				'strings' => array(
					'dismiss' => __( "I'd rather not show this support" ).' / '.__( "I've done this already" ).' :D',
					'forums' => __( 'Support Forums' )
				),
				'hrefs' => array(
					'forums' => 'https://wordpress.org/support/plugin/wp-simple-firewall',
				)
			);
			$this->insertAdminNotice( $aRenderData );
		}

		/**
		 * @see autoAddToAdminNotices()
		 * @param array $aNoticeAttributes
		 */
		protected function addNotice_php53_version_warning( $aNoticeAttributes ) {
			$oDp = $this->loadDataProcessor();
			if ( $oDp->getPhpVersionIsAtLeast( '5.3.2' ) ) {
				return;
			}

			$oCon = $this->getController();
			$aRenderData = array(
				'notice_attributes' => $aNoticeAttributes,
				'strings' => array(
					'your_php_version' => sprintf( __( 'Your PHP version is very (10+ years) old: %s' ), $oDp->getPhpVersion() ),
					'future_versions_not_supported' => sprintf( __( 'Future versions of the %s plugin will not support your PHP version.' ), $oCon->getHumanName() ),
					'ask_host_to_upgrade' => sprintf( __( 'You should ask your host to upgrade or provide a much newer PHP version.' ), $oCon->getHumanName() ),
					'any_questions' => sprintf( __( 'If you have any questions, please leave us a message in the forums.' ), $oCon->getHumanName() ),
					'dismiss' => __( 'Dismiss this notice' ),
					'forums' => __( 'Support Forums' )
				),
				'hrefs' => array(
					'forums' => 'https://wordpress.org/support/plugin/wp-simple-firewall',
				)
			);
			$this->insertAdminNotice( $aRenderData );
		}

		/**
		 * @see autoAddToAdminNotices()
		 * @param array $aNoticeAttributes
		 */
		protected function addNotice_plugin_update_available( $aNoticeAttributes ) {
			$oFO = $this->getFeatureOptions();
			$oWpUsers = $this->loadWpUsersProcessor();

			$sAdminNoticeMetaKey = $oFO->doPluginPrefix( 'plugin-update-available' );
			if ( $this->loadAdminNoticesProcessor()->getAdminNoticeIsDismissed( 'plugin-update-available' ) ) {
				$oWpUsers->updateUserMeta( $sAdminNoticeMetaKey, $oFO->getVersion() ); // so they've hidden it. Now we set the current version so it doesn't display below
				return;
			}

			if ( !$this->getIfShowAdminNotices() ) {
				return;
			}

			$oWp = $this->loadWpFunctions();
			$sBaseFile = $this->getController()->getPluginBaseFile();
			if ( !$oWp->getIsPage_Updates() && $oWp->getIsPluginUpdateAvailable( $sBaseFile ) ) { // Don't show on the update page

				$aRenderData = array(
					'notice_attributes' => $aNoticeAttributes,
					'render_slug' => 'plugin-update-available',
					'strings' => array(
						'plugin_update_available' => sprintf( __( 'There is an update available for the "%s" plugin.' ), $this->getController()->getHumanName() ),
						'click_update' => __( 'Please click to update immediately' ),
						'dismiss' => __( 'Dismiss this notice' )
					),
					'hrefs' => array(
						'upgrade_link' =>  $oWp->getPluginUpgradeLink( $sBaseFile )
					)
				);
				$this->insertAdminNotice( $aRenderData );
			}
		}

		/**
		 * @see autoAddToAdminNotices()
		 * @param array $aNoticeAttributes
		 */
		protected function addNotice_translate_plugin( $aNoticeAttributes ) {

			if ( $this->getIfShowAdminNotices() ) {
				$aRenderData = array(
					'notice_attributes' => $aNoticeAttributes,
					'strings' => array(
						'like_to_help' => sprintf( __( "Would you like to help translate the %s plugin into your language?" ), $this->getController()->getHumanName() ),
						'head_over_to' => sprintf( __( 'Head over to: %s' ), '' ),
						'site_url' => 'translate.icontrolwp.com',
						'dismiss' => __( 'Dismiss this notice' )
					),
					'hrefs' => array(
						'translate' => 'http://translate.icontrolwp.com'
					)
				);
				$this->insertAdminNotice( $aRenderData );
			}
		}

		/**
		 * @see autoAddToAdminNotices()
		 * @param array $aNoticeAttributes
		 */
		protected function addNotice_post_plugin_update( $aNoticeAttributes ) {
			$oFO = $this->getFeatureOptions();

			$oWpUsers = $this->loadWpUsersProcessor();
			$sAdminNoticeMetaKey = $oFO->doPluginPrefix( 'post-plugin-update' );
			if ( $this->loadAdminNoticesProcessor()->getAdminNoticeIsDismissed( 'post-plugin-update' ) ) {
				$oWpUsers->updateUserMeta( $sAdminNoticeMetaKey, $oFO->getVersion() ); // so they've hidden it. Now we set the current version so it doesn't display
				return;
			}

			if ( !$this->getIfShowAdminNotices() ) {
				return;
			}

			$sHumanName = $this->getController()->getHumanName();
			if ( $this->getInstallationDays() <= 1 ) {
				$sMessage = sprintf(
					__( "Notice - %s" ),
					sprintf( __( "The %s plugin does not automatically turn on certain features when you install." ), $sHumanName )
				);
			}
			else {
				$sMessage = sprintf(
					__( "Notice - %s" ),
					sprintf( __( "The %s plugin has been recently upgraded, but please remember that new features may not be automatically enabled." ), $sHumanName )
				);
			}

			$aRenderData = array(
				'notice_attributes' => $aNoticeAttributes,
				'strings' => array(
					'main_message' => $sMessage,
					'read_homepage' => __( 'Click to read about any important updates from the plugin home page.' ),
					'link_title' => $sHumanName,
					'dismiss' => __( 'Dismiss this notice' )
				),
				'hrefs' => array(
					'read_homepage' => 'http://icwp.io/27',
				),
			);
			$this->insertAdminNotice( $aRenderData );
		}

		/**
		 * @return bool
		 */
		public function getIsDeleteOnDeactivate() {
			return $this->getFeatureOptions()->getOptIs( 'delete_on_deactivate', 'Y' );
		}

		/**
		 * @param bool $bShow
		 * @return bool
		 */
		public function getIsShowMarketing( $bShow ) {
			if ( !$bShow ) {
				return $bShow;
			}

			if ( $this->getInstallationDays() < 1 ) {
				$bShow = false;
			}

			$oWpFunctions = $this->loadWpFunctions();
			if ( class_exists( 'Worpit_Plugin' ) ) {
				if ( method_exists( 'Worpit_Plugin', 'IsLinked' ) ) {
					$bShow = !Worpit_Plugin::IsLinked();
				}
				else if ( $oWpFunctions->getOption( Worpit_Plugin::$VariablePrefix.'assigned' ) == 'Y'
				          && $oWpFunctions->getOption( Worpit_Plugin::$VariablePrefix.'assigned_to' ) != '' ) {

					$bShow = false;
				}
			}
			return $bShow;
		}

		/**
		 * @return int
		 */
		protected function getInstallationDays() {
			$nTimeInstalled = $this->getFeatureOptions()->getPluginInstallationTime();
			if ( empty( $nTimeInstalled ) ) {
				return 0;
			}
			return round( ( $this->loadDataProcessor()->time() - $nTimeInstalled ) / DAY_IN_SECONDS );
		}

		/**
		 * @return bool
		 */
		protected function getIfShowAdminNotices() {
			return $this->getFeatureOptions()->getOptIs( 'enable_upgrade_admin_notice', 'Y' );
		}
	}

endif;