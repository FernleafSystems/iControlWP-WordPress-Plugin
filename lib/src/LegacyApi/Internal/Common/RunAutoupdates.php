<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Common;

class RunAutoupdates {

	/**
	 * @param string $sFile
	 */
	public function plugin( $sFile ) {
		$this->prepFilters();
		add_filter( 'auto_update_plugin', function ( $bDoAutoUpdate, $mItem ) use ( $sFile ) {
			return isset( $mItem->plugin ) && $mItem->plugin === $sFile;
		}, PHP_INT_MAX, 2 );
		wp_maybe_auto_update();
	}

	/**
	 * @param string $sStyleSheet
	 */
	public function theme( $sStyleSheet ) {
		$this->prepFilters();
		add_filter( 'auto_update_theme', function ( $bDoAutoUpdate, $mItem ) use ( $sStyleSheet ) {
			return isset( $mItem->theme ) && $mItem->theme === $sStyleSheet;
		}, PHP_INT_MAX, 2 );
		wp_maybe_auto_update();
	}

	/**
	 * @param \stdClass $oCoreUpdate
	 */
	public function core( $oCoreUpdate ) {
		$this->prepFilters( false );
		add_filter( 'auto_update_core', function ( $bDoAutoUpdate, $mItem ) use ( $oCoreUpdate ) {
			return isset( $oCoreUpdate->current ) && isset( $mItem->current )
				   && $oCoreUpdate->current === $mItem->current;
		}, PHP_INT_MAX, 2 );
		wp_maybe_auto_update();
	}

	/**
	 * @param bool $bDisableDefaultCore - use to ensure default Core upgrades don't happen
	 */
	private function prepFilters( $bDisableDefaultCore = true ) {
		$aFilters = [
			'auto_update_plugin',
			'auto_update_theme',
			'auto_update_core',
			'automatic_updater_disabled',
			'send_core_update_notification_email',
			'automatic_updates_complete',
		];
		foreach ( $aFilters as $sFilter ) {
			remove_all_filters( $sFilter );
		}
		add_filter( 'automatic_updater_disabled', '__return_false', PHP_INT_MAX );
		add_filter( 'send_core_update_notification_email', '__return_false', PHP_INT_MAX );
		if ( $bDisableDefaultCore ) {
			add_filter( 'auto_update_core', '__return_false', PHP_INT_MAX );
		}
	}
}