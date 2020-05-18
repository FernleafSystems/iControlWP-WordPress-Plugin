<?php

class ICWP_APP_Api_Internal_User_List extends ICWP_APP_Api_Internal_Base {

	/**
	 * @inheritDoc
	 */
	public function process() {
		if ( !function_exists( 'get_users' ) ) {
			include( ABSPATH.'wp-includes/user.php' );
		}

		$aData = [];
		$aActionParams = $this->getActionParams();
		$aListParts = $aActionParams[ 'parts' ];

		// Get Users
		if ( in_array( 'users', $aListParts ) ) {
			$aFields = [
				'ID',
				'user_login',
				'display_name',
				'user_email',
				'user_registered'
			];
			$aUsers = get_users( [ 'fields' => $aFields ] );

			$aOutputUsers = [];
			foreach ( $aUsers as $nCount => $oUser ) {

				$aOutputUsers[ $nCount ] = [];
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