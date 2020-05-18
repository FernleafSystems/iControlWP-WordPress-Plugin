<?php

/* http://codex.wordpress.org/Function_Reference/wp_insert_user
 * When performing an update operation, user_pass should be the hashed password and not the plain text password
 'ID' - if updating
	'user_email' => $user_email,
	'user_login' => $user_login,
	'user_pass' => $user_pass,
	'role' => $role,
	'first_name' => $user_pass,
	'last_name' => $user_pass,
	'dislay_name' =>
	'user_url' =>
	'user_registered' => $user_registered,
	'display_name' => $display_name,
 */

class ICWP_APP_Api_Internal_User_Create extends ICWP_APP_Api_Internal_Base {

	/**
	 * @inheritDoc
	 */
	public function process() {

		$aActionParams = $this->getActionParams();
		$aUser = $aActionParams[ 'user' ];
		if ( $aUser[ 'role' ] == 'default' ) {
			$aUser[ 'role' ] = get_option( 'default_role' );
		}

		$mNewUserId = $this->loadWpUsers()->createUser(
			$aUser,
			isset( $aActionParams[ 'send_notification' ] ) && $aActionParams[ 'send_notification' ]
		);

		if ( is_wp_error( $mNewUserId ) ) {
			return $this->fail( 'Could not create user with error: '.$mNewUserId->get_error_message() );
		}

		$aData = [
			'new_user_id'   => $mNewUserId,
			'new_user_data' => $aUser,
		];
		return $this->success( $aData );
	}
}