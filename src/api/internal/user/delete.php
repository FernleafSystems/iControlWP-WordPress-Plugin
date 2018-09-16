<?php

if ( !class_exists( 'ICWP_APP_Api_Internal_User_Delete', false ) ):

	require_once( dirname( dirname( __FILE__ ) ).'/base.php' );

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

	class ICWP_APP_Api_Internal_User_Delete extends ICWP_APP_Api_Internal_Base {

		/**
		 * @return ApiResponse
		 */
		public function process() {

			//Ensure we have the delete function available

			$aActionParams = $this->getActionParams();
			$nUserId = (int)$aActionParams[ 'user_id' ];
			$nReassignUserId = isset( $aActionParams[ 'reassign_id' ] ) ? $aActionParams[ 'reassign_id' ] : null;

			// Validate User ID

			try {
				$bResult = $this->loadWpUsersProcessor()->deleteUser(
					$nUserId,
					false,
					$nReassignUserId
				);
			}
			catch ( Exception $oE ) {
				return $this->fail( $oE->getMessage() );
			}

			$aData = array( 'result' => $bResult );
			return $this->success( $aData );
		}
	}

endif;