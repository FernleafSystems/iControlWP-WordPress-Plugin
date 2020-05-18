<?php

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi;

/**
 * Class ICWP_APP_Processor_Plugin_Api_Internal
 */
class ICWP_APP_Processor_Plugin_Api_Internal extends ICWP_APP_Processor_Plugin_Api {

	/**
	 * @return LegacyApi\ApiResponse|mixed
	 */
	protected function processAction() {
		$sActionName = $this->getCurrentApiActionName();
		if ( !$this->isActionSupported( $sActionName ) ) {
			return $this->setErrorResponse(
				sprintf( 'Action "%s" is not currently supported.', $sActionName )
				- 1 //TODO: Set a code
			);
		}
		return $this->processActionHandler();
	}

	/**
	 * @return string
	 * @throws \Exception
	 */
	protected function findLegacyApiClass() :string {
		$sActionName = $this->getCurrentApiActionName();
		$aPs = array_map( 'ucfirst', explode( '_', $sActionName ) );
		if ( count( $aPs ) !== 2 ) {
			throw new Exception( sprintf( 'Unsupported Action Name: %s', $sActionName ) );
		}
		$sClassName = '\\FernleafSystems\\Wordpress\\Plugin\\iControlWP\\LegacyApi\\Internal\\'.$aPs[ 0 ].'\\'.$aPs[ 1 ];
		error_log( $sClassName );
		if ( !@class_exists( $sClassName ) ) {
			throw new Exception( sprintf( 'Class Not Found: %s', $sClassName ) );
		}
		return $sClassName;
	}

	/**
	 * @return string
	 * @throws \Exception
	 */
	protected function findOldApi() :string {
		$sActionName = $this->getCurrentApiActionName();
		$aParts = explode( '_', $sActionName );

		$sBase = dirname( dirname( __FILE__ ) ).DIRECTORY_SEPARATOR.'api'.DIRECTORY_SEPARATOR.'internal'.DIRECTORY_SEPARATOR;
		$sFullPath = $sBase.$aParts[ 0 ].DIRECTORY_SEPARATOR.$aParts[ 1 ].'.php';
		require_once( $sFullPath );

		/** @var ICWP_APP_Api_Internal_Base $oApi */
		$sClassName = 'ICWP_APP_Api_Internal_'.ucfirst( $aParts[ 0 ] ).'_'.ucfirst( $aParts[ 1 ] );
		if ( !class_exists( $sClassName, false ) ) {
			throw new Exception( sprintf( 'Class %s does not exist.', $sClassName ) );
		}
		return $sClassName;
	}

	/**
	 * @return LegacyApi\ApiResponse
	 */
	protected function processActionHandler() {

		try {
			$sClassName = $this->findLegacyApiClass();
		}
		catch ( Exception $oE ) {
			try {
				$sClassName = $this->findOldApi();
			}
			catch ( Exception $oE ) {
				return $this->setErrorResponse( $oE->getMessage() );
			}
		}

		/** @var \ICWP_APP_Api_Internal_Base|\FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Base $oApi */
		$oApi = new $sClassName();
		$oApi->setRequestParams( $this->getRequestParams() )
			 ->setStandardResponse( $this->getStandardResponse() )
			 ->preProcess();

		return call_user_func( [ $oApi, 'process' ] );
	}

	/**
	 * @param string $sAction
	 * @return bool
	 */
	protected function isActionSupported( $sAction ) {
		/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
		$oFO = $this->getFeatureOptions();
		return in_array( $sAction, $oFO->getSupportedInternalApiAction() );
	}

	/**
	 * @return string
	 */
	protected function getCurrentApiActionName() {
		return $this->getRequestParams()->getApiAction();
	}

	/**
	 * @return LegacyApi\ApiResponse
	 * @deprecated
	 */
	protected function process() {
		$sActionName = $this->getCurrentApiActionName();
		$aParts = explode( '_', $sActionName );

		$sBase = dirname( dirname( __FILE__ ) ).DIRECTORY_SEPARATOR.'api'.DIRECTORY_SEPARATOR.'internal'.DIRECTORY_SEPARATOR;
		$sFullPath = $sBase.$aParts[ 0 ].DIRECTORY_SEPARATOR.$aParts[ 1 ].'.php';
		require_once( $sFullPath );

		/** @var ICWP_APP_Api_Internal_Base $oApi */
		$sClassName = 'ICWP_APP_Api_Internal_'.ucfirst( $aParts[ 0 ] ).'_'.ucfirst( $aParts[ 1 ] );
		if ( !class_exists( $sClassName, false ) ) {
			return $this->setErrorResponse( sprintf( 'Class %s does not exist.', $sClassName ) );
		}
		$oApi = new $sClassName();
		$oApi->setRequestParams( $this->getRequestParams() )
			 ->setStandardResponse( $this->getStandardResponse() );
		$oApi->preProcess();
		return call_user_func( [ $oApi, 'process' ] );
	}
}