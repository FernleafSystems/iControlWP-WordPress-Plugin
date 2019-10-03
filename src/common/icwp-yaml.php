<?php

class ICWP_APP_YamlProcessor {

	/**
	 * @var ICWP_APP_YamlProcessor
	 */
	protected static $oInstance = null;

	/**
	 * @var sfYaml
	 */
	protected static $oYaml;

	/**
	 * @return ICWP_APP_YamlProcessor
	 */
	public static function GetInstance() {
		if ( is_null( self::$oInstance ) ) {
			self::$oInstance = new self();
		}
		return self::$oInstance;
	}

	/**
	 * @param string $sYamlString
	 * @return array|null
	 */
	public function parseYamlString( $sYamlString ) {
		$aParsedResult = $this->parseSymfony( $sYamlString );
		if ( is_null( $aParsedResult ) ) {
			$aParsedResult = $this->parseSpyc( $sYamlString );
		}
		return $aParsedResult;
	}

	/**
	 * @param array $aDataToDump
	 * @return string
	 */
	public function dumpArrayToYaml( $aDataToDump ) {
		$oParser = $this->getSymfonyYamlParser();
		return $oParser->dump( $aDataToDump );
	}

	/**
	 * @param string $sYamlString
	 * @return array|null
	 */
	protected function parseSymfony( $sYamlString ) {

		$aData = null;
		$oParser = $this->getSymfonyYamlParser();
		if ( $oParser != false ) {
			try {
				$aData = $oParser->load( $sYamlString );
			}
			catch ( Exception $oE ) {
				$aData = null;
			}
		}
		return $aData;
	}

	/**
	 * @param string $sYamlString
	 * @return array|null
	 */
	protected function parseSpyc( $sYamlString ) {
		$aData = null;
		if ( $this->loadSpycYamlParser() ) {
			$aData = Spyc::YAMLLoadString( $sYamlString );
		}
		return $aData;
	}

	/**
	 */
	protected function loadSpycYamlParser() {
		if ( !class_exists( 'Spyc', false ) ) {
			require_once( dirname( __FILE__ ).'/yaml/Spyc.php' );
		}
		return class_exists( 'Spyc', false );
	}

	/**
	 */
	protected function loadSymfonyYamlParser() {
		if ( !class_exists( 'sfYaml', false ) ) {
			require_once( dirname( __FILE__ ).'/yaml/symfony/sfYaml.php' );
		}
		return class_exists( 'sfYaml', false );
	}

	/**
	 * @return bool|sfYaml
	 */
	protected function getSymfonyYamlParser() {
		if ( !isset( self::$oYaml ) ) {
			if ( $this->loadSymfonyYamlParser() ) {
				self::$oYaml = new sfYaml();
			}
			else {
				self::$oYaml = false;
			}
		}
		return self::$oYaml;
	}
}