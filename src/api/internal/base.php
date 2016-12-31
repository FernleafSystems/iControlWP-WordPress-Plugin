<?php

if ( !class_exists( 'ICWP_APP_Api_Internal_Base', false ) ):

	class ICWP_APP_Api_Internal_Base extends ICWP_APP_Foundation {

		/**
		 * @var ApiResponse
		 */
		protected $oActionResponse;

		/**
		 * @var RequestParameters
		 */
		protected $oRequestParams;

		/**
		 * @return ApiResponse
		 */
		public function getStandardResponse() {
			if ( is_null( $this->oActionResponse ) ) {
				require_once( dirname( dirname( __FILE__ ) ).DIRECTORY_SEPARATOR.'ApiResponse.php' );
				$this->oActionResponse = new ApiResponse();
			}
			return $this->oActionResponse;
		}

		/**
		 * @param ApiResponse $oActionResponse
		 * @return $this
		 */
		public function setStandardResponse( $oActionResponse ) {
			$this->oActionResponse = $oActionResponse;
			return $this;
		}

		/**
		 * @param array  $aExecutionData
		 * @param string $sMessage
		 * @return ApiResponse
		 */
		protected function success( $aExecutionData = array(), $sMessage = '' ) {
			return $this->getStandardResponse()
						->setSuccess( true )
						->setData( empty( $aExecutionData ) ? array( 'success' => 1 ) : $aExecutionData )
						->setMessage( sprintf( 'INTERNAL Package Execution SUCCEEDED with message: "%s".', $sMessage ) )
						->setCode( 0 );
		}

		/**
		 * @param string $sErrorMessage
		 * @param int $nErrorCode
		 * @param mixed $mErrorData
		 * @return ApiResponse
		 */
		protected function fail( $sErrorMessage = '', $nErrorCode = -1, $mErrorData = '' ) {
			return $this->getStandardResponse()
						->setFailed()
						->setErrorMessage( $sErrorMessage )
						->setCode( $nErrorCode )
						->setData( $mErrorData );
		}

		/**
		 * @return array
		 */
		protected function getActionParams() {
			return $this->getRequestParams()->getActionParams();
		}

		/**
		 * @return RequestParameters
		 */
		public function getRequestParams() {
			return $this->oRequestParams;
		}

		/**
		 * @param RequestParameters $oRequestParams
		 * @return ICWP_APP_Api_Internal_Base
		 */
		public function setRequestParams( $oRequestParams ) {
			$this->oRequestParams = $oRequestParams;
			return $this;
		}

		/**
		 * @return ICWP_APP_WpCollectInfo
		 */
		protected function getWpCollector() {
			require_once( dirname( __FILE__ ) . '/../../common/icwp-wpcollectinfo.php' );
			return ICWP_APP_WpCollectInfo::GetInstance();
		}
	}

endif;