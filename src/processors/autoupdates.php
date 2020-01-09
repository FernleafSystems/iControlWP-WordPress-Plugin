<?php

class ICWP_APP_Processor_Autoupdates extends ICWP_APP_Processor_BaseApp {

	/**
	 * @var boolean
	 */
	protected $bDoForceRunAutoupdates = false;

	/**
	 * @param boolean $bDoForceRun
	 */
	public function setForceRunAutoupdates( $bDoForceRun ) {
		$this->bDoForceRunAutoupdates = $bDoForceRun;
	}

	/**
	 * @return boolean
	 */
	public function getIfForceRunAutoupdates() {
		return apply_filters( $this->getFeatureOptions()
								   ->doPluginPrefix( 'force_autoupdate' ), $this->bDoForceRunAutoupdates );
	}

	/**
	 */
	public function run() {

		$nFilterPriority = $this->getHookPriority();

		$oDp = $this->loadDP();
		if ( $oDp->FetchGet( 'forcerun' ) == 1 ) {
			$this->setForceRunAutoupdates( true );
		}

		add_filter( 'allow_minor_auto_core_updates', [ $this, 'autoupdate_core_minor' ], $nFilterPriority );
		add_filter( 'allow_major_auto_core_updates', [ $this, 'autoupdate_core_major' ], $nFilterPriority );

		add_filter( 'auto_update_translation', [ $this, 'autoupdate_translations' ], $nFilterPriority, 2 );
		add_filter( 'auto_update_plugin', [ $this, 'autoupdate_plugins' ], $nFilterPriority, 2 );
		add_filter( 'auto_update_theme', [ $this, 'autoupdate_themes' ], $nFilterPriority, 2 );

		if ( $this->getIsOption( 'enable_autoupdate_ignore_vcs', 'Y' ) ) {
			add_filter( 'automatic_updates_is_vcs_checkout', [ $this, 'disable_for_vcs' ], 10, 2 );
		}

		if ( $this->getIsOption( 'enable_autoupdate_disable_all', 'Y' ) ) {
			add_filter( 'automatic_updater_disabled', '__return_true', $nFilterPriority );
		}

		add_filter( 'auto_core_update_send_email', [
			$this,
			'autoupdate_send_email'
		], $nFilterPriority, 1 ); //more parameter options here for later
		add_filter( 'auto_core_update_email', [
			$this,
			'autoupdate_email_override'
		], $nFilterPriority, 1 ); //more parameter options here for later

		add_action( 'wp_loaded', [ $this, 'force_run_autoupdates' ] );

		// Adds automatic update indicator icon to all plugin meta in plugin listing.
//			add_filter( 'plugin_row_meta', array( $this, 'fAddAutomaticUpdatePluginMeta' ), $nFilterPriority, 2 );

		// Adds automatic update indicator column to all plugins in plugin listing.
		add_filter( 'manage_plugins_columns', [ $this, 'fAddPluginsListAutoUpdateColumn' ] );
	}

	/**
	 * Will force-run the WordPress automatic updates process and then redirect to the updates screen.
	 *
	 * @return bool
	 */
	public function force_run_autoupdates() {

		if ( !$this->getIfForceRunAutoupdates() ) {
			return true;
		}
		return $this->loadWP()->doForceRunAutomaticUpdates();
	}

	/**
	 * This is a filter method designed to say whether a major core WordPress upgrade should be permitted,
	 * based on the plugin settings.
	 *
	 * @param boolean $bUpdate
	 * @return boolean
	 */
	public function autoupdate_core_major( $bUpdate ) {
		if ( $this->getIsOption( 'autoupdate_core', 'core_never' ) ) {
			return false;
		}
		elseif ( $this->getIsOption( 'autoupdate_core', 'core_major' ) ) {
			return true;
		}
		return $bUpdate;
	}

	/**
	 * This is a filter method designed to say whether a minor core WordPress upgrade should be permitted,
	 * based on the plugin settings.
	 *
	 * @param boolean $bUpdate
	 * @return boolean
	 */
	public function autoupdate_core_minor( $bUpdate ) {
		if ( $this->getIsOption( 'autoupdate_core', 'core_never' ) ) {
			return false;
		}
		elseif ( $this->getIsOption( 'autoupdate_core', 'core_minor' ) ) {
			return true;
		}
		return $bUpdate;
	}

	/**
	 * This is a filter method designed to say whether a WordPress translations upgrades should be permitted,
	 * based on the plugin settings.
	 *
	 * @param boolean $bUpdate
	 * @param string  $sSlug
	 * @return boolean
	 */
	public function autoupdate_translations( $bUpdate, $sSlug ) {
		if ( $this->getIsOption( 'enable_autoupdate_translations', 'Y' ) ) {
			return true;
		}
		return $bUpdate;
	}

	/**
	 * This is a filter method designed to say whether WordPress plugin upgrades should be permitted,
	 * based on the plugin settings.
	 *
	 * @param bool         $bDoAutoUpdate
	 * @param \stdClass|string $mItem
	 * @return boolean
	 */
	public function autoupdate_plugins( $bDoAutoUpdate, $mItem ) {

		// first, is global auto updates for plugins set
		if ( $this->getIsOption( 'enable_autoupdate_plugins', 'Y' ) ) {
			return true;
		}

		if ( is_object( $mItem ) && isset( $mItem->plugin ) ) { // WP 3.8.2+
			$sItemFile = $mItem->plugin;
		}
		elseif ( is_string( $mItem ) ) { // WP pre-3.8.2
			$sItemFile = $mItem;
		}
		// at this point we don't have a slug to use so we just return the current update setting
		else {
			return $bDoAutoUpdate;
		}

		/** @var ICWP_APP_FeatureHandler_Autoupdates $oFO */
		$oFO = $this->getFeatureOptions();
		// If it's this plugin and autoupdate this plugin is set...
		if ( $sItemFile === $oFO->getController()->getPluginBaseFile() ) {
			if ( false && $this->getIsOption( 'autoupdate_plugin_self', 'Y' ) ) { // false since 2.9.3
				$bDoAutoUpdate = true;
			}
		}

		$aAutoupdateFiles = $oFO->getAutoUpdates( 'plugins' );
		if ( !empty( $aAutoupdateFiles ) && is_array( $aAutoupdateFiles ) && in_array( $sItemFile, $aAutoupdateFiles ) ) {
			$bDoAutoUpdate = true;
		}
		return $bDoAutoUpdate;
	}

	/**
	 * This is a filter method designed to say whether WordPress theme upgrades should be permitted,
	 * based on the plugin settings.
	 *
	 * @param boolean         $bDoAutoUpdate
	 * @param stdClass|string $mItem
	 *
	 * @return boolean
	 */
	public function autoupdate_themes( $bDoAutoUpdate, $mItem ) {

		// first, is global auto updates for themes set
		if ( $this->getIsOption( 'enable_autoupdate_themes', 'Y' ) ) {
			return true;
		}

		if ( is_object( $mItem ) && isset( $mItem->theme ) ) { // WP 3.8.2+
			$sItemFile = $mItem->theme;
		}
		elseif ( is_string( $mItem ) ) { // WP pre-3.8.2
			$sItemFile = $mItem;
		}
		// at this point we don't have a slug to use so we just return the current update setting
		else {
			return $bDoAutoUpdate;
		}

		$aAutoupdateFiles = $this->getFeatureOptions()->getAutoUpdates( 'themes' );
		if ( !empty( $aAutoupdateFiles ) && is_array( $aAutoupdateFiles ) && in_array( $sItemFile, $aAutoupdateFiles ) ) {
			$bDoAutoUpdate = true;
		}
		return $bDoAutoUpdate;
	}

	/**
	 * This is a filter method designed to say whether WordPress automatic upgrades should be permitted
	 * if a version control system is detected.
	 *
	 * @param $checkout
	 * @param $context
	 * @return boolean
	 */
	public function disable_for_vcs( $checkout, $context ) {
		return false;
	}

	/**
	 * A filter on whether or not a notification email is send after core upgrades are attempted.
	 *
	 * @param boolean $bSendEmail
	 * @return boolean
	 */
	public function autoupdate_send_email( $bSendEmail ) {
		return $this->getIsOption( 'enable_upgrade_notification_email', 'Y' );
	}

	/**
	 * A filter on the target email address to which to send upgrade notification emails.
	 *
	 * @param array $aEmailParams
	 * @return array
	 */
	public function autoupdate_email_override( $aEmailParams ) {
		$sOverride = $this->getOption( 'override_email_address', '' );
		if ( !empty( $sOverride ) && is_email( $sOverride ) ) {
			$aEmailParams[ 'to' ] = $sOverride;
		}
		return $aEmailParams;
	}

	/**
	 * @filter
	 * @param array  $aPluginMeta
	 * @param string $sPluginBaseFileName
	 * @return array
	 */
	public function fAddAutomaticUpdatePluginMeta( $aPluginMeta, $sPluginBaseFileName ) {

		// first we prevent collision between iControlWP <-> Simple Firewall by not duplicating icons
		foreach ( $aPluginMeta as $sMetaItem ) {
			if ( strpos( $sMetaItem, 'icwp-pluginautoupdateicon' ) !== false ) {
				return $aPluginMeta;
			}
		}
		$bUpdate = $this->loadWP()->getIsPluginAutomaticallyUpdated( $sPluginBaseFileName );
		$sHtml = $this->getPluginAutoupdateIconHtml( $bUpdate );
		array_unshift( $aPluginMeta, sprintf( '%s', $sHtml ) );
		return $aPluginMeta;
	}

	/**
	 * Adds the column to the plugins listing table to indicate whether WordPress will automatically update the plugins
	 *
	 * @param array $aColumns
	 * @return array
	 */
	public function fAddPluginsListAutoUpdateColumn( $aColumns ) {
		if ( !isset( $aColumns[ 'icwp_autoupdate' ] ) ) {
			$aColumns[ 'icwp_autoupdate' ] = 'Auto Update';
			add_action( 'manage_plugins_custom_column', [
				$this,
				'aPrintPluginsListAutoUpdateColumnContent'
			], $this->getHookPriority(), 2 );
		}
		return $aColumns;
	}

	/**
	 * @param string $sColumnName
	 * @param string $sPluginBaseFileName
	 */
	public function aPrintPluginsListAutoUpdateColumnContent( $sColumnName, $sPluginBaseFileName ) {
		if ( $sColumnName != 'icwp_autoupdate' ) {
			return;
		}
		$bUpdate = $this->loadWP()->getIsPluginAutomaticallyUpdated( $sPluginBaseFileName );
		echo $this->getPluginAutoupdateIconHtml( $bUpdate );
	}

	/**
	 * @param boolean $bIsAutoupdate
	 *
	 * @return string
	 */
	protected function getPluginAutoupdateIconHtml( $bIsAutoupdate ) {
		return sprintf(
			'<span title="%s" class="icwp-pluginautoupdateicon dashicons dashicons-%s"></span>',
			$bIsAutoupdate ? 'Updates are applied automatically by WordPress' : 'Updates are applied manually by Administrators',
			$bIsAutoupdate ? 'update' : 'hammer'
		);
	}

	/**
	 * Removes all filters that have been added from auto-update related WordPress filters
	 */
	protected function removeAllAutoupdateFilters() {
		$aFilters = [
			'allow_minor_auto_core_updates',
			'allow_major_auto_core_updates',
			'auto_update_translation',
			'auto_update_plugin',
			'auto_update_theme',
			'automatic_updates_is_vcs_checkout',
			'automatic_updater_disabled'
		];
		foreach ( $aFilters as $sFilter ) {
			remove_all_filters( $sFilter );
		}
	}

	/**
	 * @return int
	 */
	protected function getHookPriority() {
		return $this->getOption( 'action_hook_priority', 1001 );
	}
}