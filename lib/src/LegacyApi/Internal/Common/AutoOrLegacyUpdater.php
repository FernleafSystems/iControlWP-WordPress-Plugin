<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Common;

trait AutoOrLegacyUpdater {

	protected function isMethodAuto() :bool {
		return $this->loadWP()->getWordpressIsAtLeastVersion( '3.8.2' )
			   && $this->getActionParam( 'update_method', 'legacy' ) !== 'legacy';
	}

	/**
	 * @param string|object $mAsset
	 * @return mixed|void
	 */
	protected function processAuto( $mAsset ) {
	}

	/**
	 * @param string $mAsset
	 * @return mixed|void
	 */
	protected function processLegacy( $mAsset ) {
	}
}