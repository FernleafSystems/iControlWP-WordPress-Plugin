<?php

if ( !class_exists( 'ICWP_APP_Api_Internal_Comments_Retrieve', false ) ):

	require_once( dirname( dirname( __FILE__ ) ).'/base.php' );

	class ICWP_APP_Api_Internal_Comments_Retrieve extends ICWP_APP_Api_Internal_Base {

		/**
		 * @return ApiResponse
		 */
		public function process() {

			$aActionParams = $this->getActionParams();
			$aRetrieveParams = $aActionParams[ 'retrieve_params' ];

			//cater for multiple comment statuses and multiple comment types
			$sAllStatuses = $aRetrieveParams[ 'status' ];
			$sAllTypes = $aRetrieveParams[ 'type' ];
			$aCommentStatusToLookup = explode( ',', $sAllStatuses );
			$aCommentTypesToLookup = explode( ',', $sAllTypes );

			$oWpCommentsHandler = $this->loadWpCommentsProcessor();
			$aResults = array();
			foreach ( $aCommentStatusToLookup as $sStatus ) {
				$aRetrieveParams[ 'status' ] = $sStatus;
				$aResults = array_merge( $aResults, $oWpCommentsHandler->getCommentsOfTypes( $aCommentTypesToLookup, $aRetrieveParams ) );
			}

			//Get Post IDs / Titles
			$aPostTitles = array();
			foreach ( $aResults as &$aComment ) {
				if ( !in_array( $aComment[ 'comment_post_ID' ], $aPostTitles ) ) {
					$aPostTitles[ $aComment[ 'comment_post_ID' ] ] = get_the_title( $aComment[ 'comment_post_ID' ] );
				}
				$aComment[ 'post_title' ] = $aPostTitles[ $aComment[ 'comment_post_ID' ] ];
			}

			$aData = array(
				'comments' => $aResults
			);
			return $this->success( $aData );
		}
	}

endif;