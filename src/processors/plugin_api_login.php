<?php

/**
 * Class ICWP_APP_Processor_Plugin_Api_Login
 */
class ICWP_APP_Processor_Plugin_Api_Login extends ICWP_APP_Processor_Plugin_Api {

	const LoginTokenKey = 'icwplogintoken';

	/**
	 * Override so that we don't run the handshaking etc.
	 * @return ApiResponse
	 */
	public function run() {
		$this->preActionEnvironmentSetup();
		return $this->processAction();
	}

	/**
	 * @return ApiResponse
	 */
	protected function processAction() {
		$oReqParams = $this->getRequestParams();
		$oWp = $this->loadWP();

		$this->getStandardResponse()->setDie( true );

		$sRequestToken = $oReqParams->getStringParam( 'token' );
		if ( empty( $sRequestToken ) ) {
			$sErrorMessage = 'No valid Login Token was sent.';
			return $this->setErrorResponse(
				$sErrorMessage,
				-1 //TODO: Set a code
			);
		}

		$sStoredToken = $oWp->getTransient( self::LoginTokenKey );
		$oWp->deleteTransient( self::LoginTokenKey ); // One chance per token
		if ( empty( $sStoredToken ) || strlen( $sStoredToken ) != 32 ) {
			$sErrorMessage = 'Login Token is not present or is not of the correct format.';
			return $this->setErrorResponse(
				$sErrorMessage,
				-1 //TODO: Set a code
			);
		}

		if ( $sStoredToken !== $sRequestToken ) {
			$sErrorMessage = 'Login Tokens do not match.';
			return $this->setErrorResponse(
				$sErrorMessage,
				-1 //TODO: Set a code
			);
		}

		$oWpUsers = $this->loadWpUsers();

		$sUsername = $oReqParams->getStringParam( 'username' );
		$oUser = $oWpUsers->getUserByUsername( $sUsername );
		if ( empty( $sUsername ) || empty( $oUser ) ) {
			$aUserRecords = version_compare( $oWp->getWordpressVersion(), '3.1', '>=' ) ? get_users( 'role=administrator' ) : array();
			if ( empty( $aUserRecords[ 0 ] ) ) {
				$sErrorMessage = 'Failed to find an administrator user.';
				return $this->setErrorResponse(
					$sErrorMessage,
					-1 //TODO: Set a code
				);
			}
			$oUser = $aUserRecords[ 0 ];
		}

		// By-passes the 2FA process on Shield
		add_filter( 'odp-shield-2fa_skip', '__return_true' );

		$bLoginSuccess = $oWpUsers->setUserLoggedIn( $oUser->get( 'user_login' ) );
		if ( !$bLoginSuccess ) {
			return $this->setErrorResponse(
				sprintf( 'There was a problem logging you in as "%s".', $oUser->get( 'user_login' ) ),
				-1 //TODO: Set a code
			);
		}

		$sRedirectPath = $oReqParams->getStringParam( 'redirect' );
		if ( strlen( $sRedirectPath ) == 0 ) {
			$oWp->redirectToAdmin();
		}
		else {
			$oWp->doRedirect( $sRedirectPath );
		}
		die();
	}
}