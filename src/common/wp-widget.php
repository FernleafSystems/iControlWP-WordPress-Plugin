<?php

/**
 * Class ICWP_APP_WpWidget
 */
class ICWP_APP_WpWidget extends WP_Widget {

	/**
	 * @param array  $aWidgetArguments
	 * @param string $sTitle
	 * @param string $sContent
	 */
	protected function standardRender( $aWidgetArguments, $sTitle = '', $sContent = '' ) {
		echo $aWidgetArguments[ 'before_widget' ];
		if ( !empty( $sTitle ) ) {
			echo $aWidgetArguments[ 'before_title' ].$sTitle.$aWidgetArguments[ 'after_title' ];
		}
		echo $sContent.$aWidgetArguments[ 'after_widget' ];
	}
}