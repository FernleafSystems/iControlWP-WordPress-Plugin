<?php

if ( !class_exists( 'ICWP_APP_Processor_BaseApp', false ) ):

	require_once( dirname(__FILE__).ICWP_DS.'base.php' );

	abstract class ICWP_APP_Processor_BaseApp extends ICWP_APP_Processor_Base {

		/**
		 * @return RequestParameters
		 */
		protected function getRequestParams() {
			return $this->getFeatureOptions()->getRequestParams();
		}

		/**
		 * @return array
		 */
		protected function getActionParams() {
			return $this->getRequestParams()->getActionParams();
		}
	}

endif;