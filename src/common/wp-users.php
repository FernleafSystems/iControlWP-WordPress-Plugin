<?php
if ( !class_exists( 'ICWP_APP_WpUsers', false ) ):

	class ICWP_APP_WpUsers extends ICWP_APP_Foundation {

		/**
		 * @var ICWP_APP_WpUsers
		 */
		protected static $oInstance = NULL;

		private function __construct() {}

		/**
		 * @return ICWP_APP_WpUsers
		 */
		public static function GetInstance() {
			if ( is_null( self::$oInstance ) ) {
				self::$oInstance = new self();
			}
			return self::$oInstance;
		}

		/**
		 * If setting password, do not send the hashed password as this will hash it for you
		 *
		 * @param array $aNewUserData
		 * @param bool $bSendNotification
		 * @return int|WP_Error
		 */
		public function createUser( $aNewUserData, $bSendNotification = false ) {

			$aUserDefaults = array(
				'user_registered' => strftime( '%F %T', time() ),
				'display_name' => false,
				'user_url' => '',
				'description'	=> ''
			);

			//set defaults for unset vars
			$aNewUser = wp_parse_args( $aNewUserData, $aUserDefaults );
			if ( !empty( $aNewUser[ 'user_pass' ] ) ) {
				$aNewUser[ 'user_pass' ] = wp_hash_password( $aNewUser[ 'user_pass' ] );
			}
			$mNewUserId = wp_insert_user( $aNewUser );

			if ( $bSendNotification && !is_wp_error( $mNewUserId ) && function_exists( 'wp_new_user_notification' ) ) {
				wp_new_user_notification( $mNewUserId );
			}
			return $mNewUserId;
		}

		/**
		 * @param int $nUserId
		 * @param bool $bPermitAdminDelete
		 * @param int $nReassignUserId
		 * @return bool
		 * @throws Exception
		 */
		public function deleteUser( $nUserId, $bPermitAdminDelete = false, $nReassignUserId = null ) {
			if ( !function_exists( 'wp_delete_user' ) ) {
				include( ABS_PATH.'wp-admin/includes/user.php' );
				if ( !function_exists( 'wp_delete_user' ) ) {
					throw new Exception( 'Could not find the function wp_delete_user()' );
				}
			}
			if ( empty( $nUserId ) ) {
				throw new Exception( 'User ID value was not set' );
			}
			if ( $nUserId <= 0 ) {
				throw new Exception( sprintf( 'Supplied User ID "%s" to delete was less than or equal to zero', $nUserId ) );
			}

			$oUserToDelete = $this->getUserById( $nUserId );
			if ( empty( $oUserToDelete ) ) {
				throw new Exception( sprintf( 'Could not load User with ID "%s" to delete', $nUserId ) );
			}
			if ( !$bPermitAdminDelete && $this->isUserAdmin( $oUserToDelete ) ) {
				throw new Exception( sprintf( 'Attempting to delete Administrator User ID "%s"', $nUserId ) );
			}

			return wp_delete_user( $nUserId, $nReassignUserId );
		}

		/**
		 * @param string $sKey
		 * @param integer $nUserId		-user ID
		 * @return boolean
		 */
		public function deleteUserMeta( $sKey, $nUserId = null ) {
			if ( empty( $nUserId ) ) {
				$nUserId = $this->getCurrentWpUserId();
			}
			$bSuccess = false;
			if ( $nUserId > 0 ) {
				$bSuccess = delete_user_meta( $nUserId, $sKey );
			}
			return $bSuccess;
		}

		/**
		 * @param array $aLoginUrlParams
		 */
		public function forceUserRelogin( $aLoginUrlParams = array() ) {
			$this->logoutUser();
			$this->loadWpFunctionsProcessor()->redirectToLogin( $aLoginUrlParams );
		}

		/**
		 * @return integer
		 */
		public function getCurrentUserLevel() {
			$oUser = $this->getCurrentWpUser();
			return ( is_object($oUser) && ($oUser instanceof WP_User) )? $oUser->get( 'user_level' ) : -1;
		}

		/**
		 * @return bool
		 */
		public function getCanAddUpdateCurrentUserMeta() {
			$bCanMeta = false;
			try {
				if ( $this->isUserLoggedIn() ) {
					$sKey = 'icwp-flag-can-store-user-meta';
					$sMeta = $this->getUserMeta( $sKey );
					if ( $sMeta == 'icwp' ) {
						$bCanMeta = true;
					}
					else {
						$bCanMeta = $this->updateUserMeta( $sKey, 'icwp' );
					}
				}
			}
			catch( Exception $oE ) { }
			return $bCanMeta;
		}

		/**
		 * @return null|WP_User
		 */
		public function getCurrentWpUser() {
			if ( $this->isUserLoggedIn() ) {
				$oUser = wp_get_current_user();
				if ( is_object( $oUser ) && $oUser instanceof WP_User ) {
					return $oUser;
				}
			}
			return null;
		}

		/**
		 * @return int - 0 if not logged in or can't get the current User
		 */
		public function getCurrentWpUserId() {
			$oUser = $this->getCurrentWpUser();
			$nId = is_null( $oUser ) ? 0 : $oUser->ID;
			return $nId;
		}

		/**
		 * @param $sUsername
		 * @return false|WP_User
		 */
		public function getUserByUsername( $sUsername ) {
			if ( empty( $sUsername ) ) {
				return false;
			}

			if ( version_compare( $this->loadWpFunctionsProcessor()->getWordpressVersion(), '2.8.0', '<' ) ) {
				$oUser = get_userdatabylogin( $sUsername );
			}
			else {
				$oUser = get_user_by( 'login', $sUsername );
			}

			return $oUser;
		}

		/**
		 * @param int $nId
		 * @return WP_User|null
		 */
		public function getUserById( $nId ) {
			if ( version_compare( $this->loadWpFunctionsProcessor()->getWordpressVersion(), '2.8.0', '<' ) || !function_exists( 'get_user_by' ) ) {
				return null;
			}
			return get_user_by( 'id', $nId );
		}

		/**
		 * @param string $sKey should be already prefixed
		 * @param int|null $nUserId - if omitted get for current user
		 * @return false|string
		 */
		public function getUserMeta( $sKey, $nUserId = null ) {
			if ( empty( $nUserId ) ) {
				$nUserId = $this->getCurrentWpUserId();
			}

			$mResult = false;
			if ( $nUserId > 0 ) {
				$mResult = get_user_meta( $nUserId, $sKey, true );
			}
			return $mResult;
		}

		/**
		 * @param WP_User|null $oUser
		 * @return bool
		 */
		public function isUserAdmin( $oUser = null ) {
			if ( empty( $oUser ) ) {
				$bIsAdmin = $this->isUserLoggedIn() && current_user_can( 'manage_options' );
			}
			else {
				$bIsAdmin = user_can( $oUser, 'manage_options' );
			}
			return $bIsAdmin;
		}

		/**
		 * @return bool
		 * @throws Exception
		 */
		public function isUserLoggedIn() {
			if ( !function_exists( 'is_user_logged_in' ) ) {
				throw new Exception( sprintf( 'Function %s is not ready - you are calling it too early in the WP load.', 'is_user_logged_in()' ) );
			}
			return is_user_logged_in();
		}

		/**
		 * @param string $sRedirectUrl
		 */
		public function logoutUser( $sRedirectUrl = '' ) {
			empty( $sRedirectUrl ) ? wp_logout() : wp_logout_url( $sRedirectUrl );
		}

		/**
		 * Updates the user meta data for the current (or supplied user ID)
		 *
		 * @param string $sKey
		 * @param mixed $mValue
		 * @param integer $nUserId		-user ID
		 * @return boolean
		 */
		public function updateUserMeta( $sKey, $mValue, $nUserId = null ) {
			if ( empty( $nUserId ) ) {
				$nUserId = $this->getCurrentWpUserId();
			}

			$bSuccess = false;
			if ( $nUserId > 0 ) {
				$bSuccess = update_user_meta( $nUserId, $sKey, $mValue );
			}
			return $bSuccess;
		}

		/**
		 * @param string $sUsername
		 * @param bool $bSilentLogin
		 * @return bool
		 */
		public function setUserLoggedIn( $sUsername, $bSilentLogin = false ) {
			if ( !defined( 'COOKIEHASH' ) ) {
				wp_cookie_constants();
			}

			$oUser = $this->getUserByUsername( $sUsername );
			if ( !is_a( $oUser, 'WP_User' ) ) {
				return false;
			}

			wp_clear_auth_cookie();
			wp_set_current_user( $oUser->ID, $oUser->get( 'user_login' ) );
			wp_set_auth_cookie( $oUser->ID, true );
			if ( !$bSilentLogin ) {
				do_action( 'wp_login', $oUser->get( 'user_login' ), $oUser );
			}
			return true;
		}
	}

endif;