<?php

if ( class_exists( 'ICWP_APP_Api_Internal_User_List', false ) ) {
	return;
}

require_once( dirname( dirname( __FILE__ ) ).'/base.php' );

class ICWP_APP_Api_Internal_User_List extends ICWP_APP_Api_Internal_Base {

	/**
	 * @return ApiResponse
	 */
	public function process() {
		if ( !function_exists( 'get_users' ) ) {
			include( ABS_PATH.'wp-includes/user.php' );
		}

		$aData = array();
		$aActionParams = $this->getActionParams();
		$aListParts = $aActionParams[ 'parts' ];

		// Get Users
		if ( in_array( 'users', $aListParts ) ) {
			$aFields = array(
				'ID',
				'user_login',
				'display_name',
				'user_email',
				'user_registered'
			);
			$aUsers = get_users( array( 'fields' => $aFields ) );

			$aOutputUsers = array();
			foreach ( $aUsers as $nCount => $oUser ) {

				$aOutputUsers[ $nCount ] = array();
				foreach ( $aFields as $sField ) {
					$aOutputUsers[ $nCount ][ $sField ] = $oUser->{$sField};
				}
			}
			$aData[ 'raw' ] = $aUsers;
			$aData[ 'wpusers' ] = $aOutputUsers;
		}

		// Get Roles
		if ( in_array( 'roles', $aListParts ) ) {
			global $wp_roles;

			if ( is_object( $wp_roles ) ) {
				$aData[ 'wproles' ] = $wp_roles->roles;
			}
			else {
				$aData[ 'wproles' ] = '$wp_roles not set';
			}
		}

		return $this->success( $aData );
	}
}