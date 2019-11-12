<?php

class ICWP_APP_OptionsVO extends ICWP_APP_Foundation {

	/**
	 * @var array
	 */
	protected $aOptionsValues;

	/**
	 * @var array
	 */
	protected $aChangedOptionsTracker;

	/**
	 * @var array
	 */
	protected $aRawOptionsConfigData;

	/**
	 * @var boolean
	 */
	protected $bNeedSave;

	/**
	 * @var boolean
	 */
	protected $bRebuildFromFile = false;

	/**
	 * @var string
	 */
	protected $aOptionsKeys;

	/**
	 * @var string
	 */
	protected $sOptionsStorageKey;

	/**
	 *  by default we load from saved
	 * @var string
	 */
	protected $bLoadFromSaved = true;

	/**
	 * @var string
	 */
	protected $sOptionsName;

	/**
	 * @param string $sOptionsName
	 */
	public function __construct( $sOptionsName ) {
		$this->sOptionsName = $sOptionsName;
	}

	/**
	 * @return bool
	 */
	public function cleanTransientStorage() {
		return $this->loadWP()->deleteTransient( $this->getConfigStorageKey() );
	}

	/**
	 * @return bool
	 */
	public function doOptionsSave() {
		if ( !$this->getNeedSave() ) {
			return true;
		}
		$this->cleanOptions();
		$this->setNeedSave( false );
		return $this->loadWP()
					->updateOption( $this->getOptionsStorageKey(), $this->getAllOptionsValues() );
	}

	/**
	 * @return bool
	 */
	public function doOptionsDelete() {
		$oWp = $this->loadWP();
		$oWp->deleteTransient( $this->getConfigStorageKey() );
		return $oWp->deleteOption( $this->getOptionsStorageKey() );
	}

	/**
	 * @return array
	 */
	public function getAllOptionsValues() {
		return $this->loadOptionsValuesFromStorage();
	}

	/**
	 * Returns an array of all the transferable options and their values
	 * @return array
	 */
	public function getTransferableOptions() {

		$aOptions = $this->getAllOptionsValues();
		$aRawOptions = $this->getRawData_AllOptions();
		$aTransferable = array();
		foreach ( $aRawOptions as $nKey => $aOptionData ) {
			if ( isset( $aOptionData[ 'transferable' ] ) && $aOptionData[ 'transferable' ] === true ) {
				$aTransferable[ $aOptionData[ 'key' ] ] = $aOptions[ $aOptionData[ 'key' ] ];
			}
		}
		return $aTransferable;
	}

	/**
	 * @param $sProperty
	 * @return null|mixed
	 */
	public function getFeatureProperty( $sProperty ) {
		$aRawConfig = $this->getRawData_FullFeatureConfig();
		return ( isset( $aRawConfig[ 'properties' ] ) && isset( $aRawConfig[ 'properties' ][ $sProperty ] ) ) ? $aRawConfig[ 'properties' ][ $sProperty ] : null;
	}

	/**
	 * @param string
	 * @return null|array
	 */
	public function getFeatureDefinition( $sDefinition ) {
		$aRawConfig = $this->getRawData_FullFeatureConfig();
		return ( isset( $aRawConfig[ 'definitions' ] ) && isset( $aRawConfig[ 'definitions' ][ $sDefinition ] ) ) ? $aRawConfig[ 'definitions' ][ $sDefinition ] : null;
	}

	/**
	 * @param string $sReq
	 * @return null|mixed
	 */
	public function getFeatureRequirement( $sReq ) {
		$aReqs = $this->getRawData_Requirements();
		return ( is_array( $aReqs ) && isset( $aReqs[ $sReq ] ) ) ? $aReqs[ $sReq ] : null;
	}

	/**
	 * @return array
	 */
	public function getAdminNotices() {
		$aRawConfig = $this->getRawData_FullFeatureConfig();
		return ( isset( $aRawConfig[ 'admin_notices' ] ) && is_array( $aRawConfig[ 'admin_notices' ] ) ) ? $aRawConfig[ 'admin_notices' ] : array();
	}

	/**
	 * @return string
	 */
	public function getFeatureTagline() {
		return $this->getFeatureProperty( 'tagline' );
	}

	/**
	 * Determines whether the given option key is a valid option
	 * @param string
	 * @return boolean
	 */
	public function getIsValidOptionKey( $sOptionKey ) {
		return in_array( $sOptionKey, $this->getOptionsKeys() );
	}

	/**
	 * @return array
	 */
	public function getHiddenOptions() {

		$aRawData = $this->getRawData_FullFeatureConfig();
		$aOptionsData = array();

		foreach ( $aRawData[ 'sections' ] as $nPosition => $aRawSection ) {

			// if hidden isn't specified we skip
			if ( !isset( $aRawSection[ 'hidden' ] ) || !$aRawSection[ 'hidden' ] ) {
				continue;
			}
			foreach ( $this->getRawData_AllOptions() as $aRawOption ) {

				if ( $aRawOption[ 'section' ] != $aRawSection[ 'slug' ] ) {
					continue;
				}
				$aOptionsData[ $aRawOption[ 'key' ] ] = $this->getOpt( $aRawOption[ 'key' ] );
			}
		}
		return $aOptionsData;
	}

	/**
	 * @return array
	 */
	public function getLegacyOptionsConfigData() {

		$aRawData = $this->getRawData_FullFeatureConfig();
		$aLegacyData = array();

		foreach ( $aRawData[ 'sections' ] as $nPosition => $aRawSection ) {

			if ( isset( $aRawSection[ 'hidden' ] ) && $aRawSection[ 'hidden' ] ) {
				continue;
			}

			$aLegacySection = array();
			$aLegacySection[ 'section_primary' ] = isset( $aRawSection[ 'primary' ] ) && $aRawSection[ 'primary' ];
			$aLegacySection[ 'section_slug' ] = $aRawSection[ 'slug' ];
			$aLegacySection[ 'section_options' ] = array();
			foreach ( $this->getRawData_AllOptions() as $aRawOption ) {

				if ( $aRawOption[ 'section' ] != $aRawSection[ 'slug' ] ) {
					continue;
				}

				if ( isset( $aRawOption[ 'hidden' ] ) && $aRawOption[ 'hidden' ] ) {
					continue;
				}

				$aLegacyRawOption = array();
				$aLegacyRawOption[ 'key' ] = $aRawOption[ 'key' ];
				$aLegacyRawOption[ 'value' ] = ''; //value
				$aLegacyRawOption[ 'default' ] = $aRawOption[ 'default' ];
				$aLegacyRawOption[ 'type' ] = $aRawOption[ 'type' ];

				$aLegacyRawOption[ 'value_options' ] = array();
				if ( in_array( $aLegacyRawOption[ 'type' ], array( 'select', 'multiple_select' ) ) ) {
					foreach ( $aRawOption[ 'value_options' ] as $aValueOptions ) {
						$aLegacyRawOption[ 'value_options' ][ $aValueOptions[ 'value_key' ] ] = $aValueOptions[ 'text' ];
					}
				}

				$aLegacyRawOption[ 'info_link' ] = isset( $aRawOption[ 'link_info' ] ) ? $aRawOption[ 'link_info' ] : '';
				$aLegacyRawOption[ 'blog_link' ] = isset( $aRawOption[ 'link_blog' ] ) ? $aRawOption[ 'link_blog' ] : '';
				$aLegacySection[ 'section_options' ][] = $aLegacyRawOption;
			}

			if ( count( $aLegacySection[ 'section_options' ] ) > 0 ) {
				$aLegacyData[ $nPosition ] = $aLegacySection;
			}
		}
		return $aLegacyData;
	}

	/**
	 * @return array
	 */
	public function getAdditionalMenuItems() {
		return $this->getRawData_MenuItems();
	}

	/**
	 * @return string
	 */
	public function getNeedSave() {
		return $this->bNeedSave;
	}

	/**
	 * @param string $sOptionKey
	 * @param mixed  $mDefault
	 * @return mixed
	 */
	public function getOpt( $sOptionKey, $mDefault = false ) {
		$aOptionsValues = $this->getAllOptionsValues();
		if ( !isset( $aOptionsValues[ $sOptionKey ] ) ) {
			$this->setOpt( $sOptionKey, $this->getOptDefault( $sOptionKey, $mDefault ), true );
		}
		return $this->aOptionsValues[ $sOptionKey ];
	}

	/**
	 * @param string $sOptionKey
	 * @param mixed  $mDefault
	 * @return mixed|null
	 */
	public function getOptDefault( $sOptionKey, $mDefault = null ) {
		$aOptions = $this->getRawData_AllOptions();
		foreach ( $aOptions as $aOption ) {
			if ( $aOption[ 'key' ] == $sOptionKey ) {
				if ( isset( $aOption[ 'value' ] ) ) {
					return $aOption[ 'value' ];
				}
				else if ( isset( $aOption[ 'default' ] ) ) {
					return $aOption[ 'default' ];
				}
			}
		}
		return $mDefault;
	}

	/**
	 * @param         $sKey
	 * @param mixed   $mValueToTest
	 * @param boolean $bStrict
	 * @return bool
	 */
	public function getOptIs( $sKey, $mValueToTest, $bStrict = false ) {
		$mOptionValue = $this->getOpt( $sKey );
		return $bStrict ? $mOptionValue === $mValueToTest : $mOptionValue == $mValueToTest;
	}

	/**
	 * @return string
	 */
	public function getOptionsKeys() {
		if ( !isset( $this->aOptionsKeys ) ) {
			$this->aOptionsKeys = array();
			foreach ( $this->getRawData_AllOptions() as $aOption ) {
				$this->aOptionsKeys[] = $aOption[ 'key' ];
			}
		}
		return $this->aOptionsKeys;
	}

	/**
	 * @return string
	 */
	public function getOptionsName() {
		return $this->sOptionsName;
	}

	/**
	 * @return string
	 */
	public function getOptionsStorageKey() {
		return $this->sOptionsStorageKey;
	}

	/**
	 * @return array
	 */
	public function getStoredOptions() {
		try {
			return $this->loadOptionsValuesFromStorage();
		}
		catch ( Exception $oE ) {
			return array();
		}
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getRawData_FullFeatureConfig() {
		if ( empty( $this->aRawOptionsConfigData ) ) {
			$this->aRawOptionsConfigData = $this->readConfiguration();
		}
		return $this->aRawOptionsConfigData;
	}

	/**
	 * Return the section of the Raw config that is the "options" key only.
	 * @return array
	 */
	protected function getRawData_AllOptions() {
		$aAllRawOptions = $this->getRawData_FullFeatureConfig();
		return isset( $aAllRawOptions[ 'options' ] ) ? $aAllRawOptions[ 'options' ] : array();
	}

	/**
	 * Return the section of the Raw config that is the "options" key only.
	 * @return array
	 */
	protected function getRawData_Requirements() {
		$aAllRawOptions = $this->getRawData_FullFeatureConfig();
		return isset( $aAllRawOptions[ 'requirements' ] ) ? $aAllRawOptions[ 'requirements' ] : array();
	}

	/**
	 * Return the section of the Raw config that is the "options" key only.
	 * @return array
	 */
	protected function getRawData_MenuItems() {
		$aAllRawOptions = $this->getRawData_FullFeatureConfig();
		return isset( $aAllRawOptions[ 'menu_items' ] ) ? $aAllRawOptions[ 'menu_items' ] : array();
	}

	/**
	 * Return the section of the Raw config that is the "options" key only.
	 * @param string $sOptionKey
	 * @return array
	 */
	public function getRawData_SingleOption( $sOptionKey ) {
		$aAllRawOptions = $this->getRawData_AllOptions();
		if ( is_array( $aAllRawOptions ) ) {
			foreach ( $aAllRawOptions as $aOption ) {
				if ( isset( $aOption[ 'key' ] ) && ( $sOptionKey == $aOption[ 'key' ] ) ) {
					return $aOption;
				}
			}
		}
		return null;
	}

	/**
	 * @return boolean
	 */
	public function getRebuildFromFile() {
		return $this->bRebuildFromFile;
	}

	/**
	 * @param string $sOptionKey
	 * @return boolean
	 */
	public function resetOptToDefault( $sOptionKey ) {
		return $this->setOpt( $sOptionKey, $this->getOptDefault( $sOptionKey ), true );
	}

	/**
	 * @param string $sKey
	 */
	public function setOptionsStorageKey( $sKey ) {
		$this->sOptionsStorageKey = $sKey;
	}

	/**
	 * @return boolean
	 */
	public function getIfLoadOptionsFromStorage() {
		return $this->bLoadFromSaved;
	}

	/**
	 * @param boolean $bLoadFromSaved
	 */
	public function setIfLoadOptionsFromStorage( $bLoadFromSaved ) {
		$this->bLoadFromSaved = $bLoadFromSaved;
	}

	/**
	 * @param boolean $bNeed
	 */
	public function setNeedSave( $bNeed ) {
		$this->bNeedSave = $bNeed;
	}

	/**
	 * @param boolean $bRebuild
	 */
	public function setRebuildFromFile( $bRebuild ) {
		$this->bRebuildFromFile = $bRebuild;
	}

	/**
	 * @param array $aOptions
	 * @return $this
	 */
	public function setMultipleOptions( $aOptions ) {
		if ( is_array( $aOptions ) ) {
			foreach ( $aOptions as $sKey => $mValue ) {
				$this->setOpt( $sKey, $mValue );
			}
		}
		return $this;
	}

	/**
	 * @param string  $sOptionKey
	 * @param mixed   $mValue
	 * @param boolean $bForce
	 * @return mixed
	 */
	public function setOpt( $sOptionKey, $mValue, $bForce = false ) {

		if ( $bForce || $this->getOpt( $sOptionKey ) !== $mValue ) {
			$this->setNeedSave( true );

			//Load the config and do some pre-set verification where possible. This will slowly grow.
			$aOption = $this->getRawData_SingleOption( $sOptionKey );
			if ( !empty( $aOption[ 'type' ] ) ) {
				if ( $aOption[ 'type' ] == 'boolean' && !is_bool( $mValue ) ) {
					return $this->resetOptToDefault( $sOptionKey );
				}
			}
			$this->trackOption( $sOptionKey );
			$this->aOptionsValues[ $sOptionKey ] = $mValue;
		}
		return true;
	}

	/**
	 * Will return an option value to the original value if it was changed in this page load.
	 * @param string $sKey
	 * @return bool
	 */
	public function revertChangedOption( $sKey ) {
		if ( !empty( $this->aChangedOptionsTracker ) && is_array( $this->aChangedOptionsTracker ) && isset( $this->aChangedOptionsTracker[ $sKey ] ) ) {
			return $this->setOpt( $sKey, $this->aChangedOptionsTracker[ $sKey ] );
		}
		return false;
	}

	/**
	 * @param string $sKey
	 */
	private function trackOption( $sKey ) {
		if ( !isset( $this->aChangedOptionsTracker ) ) {
			$this->aChangedOptionsTracker = array();
		}
		// Meaning we only track once, and we don't overwrite if an option is set multiple times.
		if ( !isset( $this->aChangedOptionsTracker[ $sKey ] ) && isset( $this->aOptionsValues[ $sKey ] ) ) {
			$this->aChangedOptionsTracker[ $sKey ] = $this->aOptionsValues[ $sKey ];
		}
	}

	/**
	 * @param string $sOptionKey
	 * @return mixed
	 */
	public function unsetOpt( $sOptionKey ) {

		unset( $this->aOptionsValues[ $sOptionKey ] );
		$this->setNeedSave( true );
		return true;
	}

	/** PRIVATE STUFF */

	/**
	 */
	private function cleanOptions() {
		if ( empty( $this->aOptionsValues ) || !is_array( $this->aOptionsValues ) ) {
			return;
		}
		foreach ( $this->aOptionsValues as $sKey => $mValue ) {
			if ( !$this->getIsValidOptionKey( $sKey ) ) {
				$this->setNeedSave( true );
				unset( $this->aOptionsValues[ $sKey ] );
			}
		}
	}

	/**
	 * @param bool $bReload
	 * @return array|mixed
	 * @throws Exception
	 */
	private function loadOptionsValuesFromStorage( $bReload = false ) {

		if ( $bReload || empty( $this->aOptionsValues ) ) {

			if ( $this->getIfLoadOptionsFromStorage() ) {

				$sStorageKey = $this->getOptionsStorageKey();
				if ( empty( $sStorageKey ) ) {
					throw new Exception( 'Options Storage Key Is Empty' );
				}
				$this->aOptionsValues = $this->loadWP()->getOption( $sStorageKey, array() );
			}
		}
		if ( !is_array( $this->aOptionsValues ) ) {
			$this->aOptionsValues = array();
			$this->setNeedSave( true );
		}
		return $this->aOptionsValues;
	}

	/**
	 * @return array
	 */
	private function readConfiguration() {
		$oWp = $this->loadWP();

		$aConfig = $oWp->getOption( $this->getConfigStorageKey() );

		$bRebuild = $this->getRebuildFromFile() || empty( $aConfig );
		if ( !$bRebuild && !empty( $aConfig ) && is_array( $aConfig ) ) {

			if ( !isset( $aConfig[ 'meta_modts' ] ) ) {
				$aConfig[ 'meta_modts' ] = 0;
			}
			$bRebuild = $this->getConfigModTime() > $aConfig[ 'meta_modts' ];
		}

		if ( $bRebuild ) {
			try {
				$aConfig = $this->readConfigurationJson();
			}
			catch ( Exception $oE ) {
				if ( $oWp->isDebug() ) {
					trigger_error( $oE->getMessage() );
				}
				$aConfig = array();
			}
			$aConfig[ 'meta_modts' ] = $this->getConfigModTime();
			$oWp->updateOption( $this->getConfigStorageKey(), $aConfig );
		}

		$this->setRebuildFromFile( $bRebuild );
		return $aConfig;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	private function readConfigurationJson() {
		$sPath = $this->getPathToConfig();
		if ( empty( $sPath ) || !$this->loadFS()->isFile( $sPath ) ) {
			throw new Exception( sprintf( 'Configuration file "%s" does not exist.', $this->getPathToConfig() ) );
		}

		$aConfig = json_decode( $this->loadDP()->readFileContentsUsingInclude( $sPath ), true );

		if ( empty( $aConfig ) ) {
			throw new Exception( sprintf( 'Reading JSON configuration from file "%s" failed.', $this->getOptionsName() ) );
		}
		return $aConfig;
	}

	/**
	 * @return string
	 */
	private function getConfigStorageKey() {
		return 'icwp_app_'.md5( $this->getPathToConfig() );
	}

	/**
	 * @return string
	 */
	protected function getConfigModTime() {
		return $this->loadFS()->getModifiedTime( $this->getPathToConfig() );
	}

	/**
	 * @return string
	 */
	public function getPathToConfig() {
		return dirname( __FILE__ ).'/../'.sprintf( 'config/feature-%s.php', $this->getOptionsName() );
	}

	/**
	 * @return string
	 * @deprecated 3.7
	 */
	private function getSpecTransientStorageKey() {
		return $this->getConfigStorageKey();
	}

	/**
	 * @return string
	 * @deprecated 3.7
	 */
	private function getConfigFilePath() {
		return $this->getPathToConfig();
	}
}