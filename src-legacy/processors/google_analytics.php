<?php

if ( !class_exists( 'ICWP_APP_Processor_GoogleAnalytics', false ) ):

	require_once( dirname(__FILE__).'/base_app.php' );

	class ICWP_APP_Processor_GoogleAnalytics extends ICWP_APP_Processor_BaseApp {

		/**
		 * @var array
		 */
		private $aGaOptions;

		/**
		 */
		public function run() {
			$oFO = $this->getFeatureOptions();
			$this->aGaOptions = array(
				'tracking_id' => $oFO->getOpt( 'tracking_id' ),
				'enable_google_analytics' => $oFO->getOpt( 'enable_google_analytics' ),
				'enable_universal_analytics' => $oFO->getOpt( 'enable_universal_analytics' ),
				'ignore_logged_in_user' => $oFO->getOpt( 'ignore_logged_in_user' ),
				'ignore_from_user_level' => $oFO->getOpt( 'ignore_from_user_level', 11 ),
				'in_footer' => $oFO->getOpt( 'in_footer' ),
			);
			add_action( $this->getWpHook(), array( $this, 'doPrintGoogleAnalytics' ), 100 );
		}

		/**
		 * @return void|string
		 */
		public function doPrintGoogleAnalytics() {

			if ( $this->getIfIgnoreUser() || strlen( $this->aGaOptions[ 'tracking_id' ] ) <= 0 ) {
				return;
			}
			echo ( $this->aGaOptions[ 'enable_universal_analytics' ] == 'Y' ) ? $this->getAnalyticsCode_Universal() : $this->getAnalyticsCode();
		}

		/**
		 * @return bool
		 */
		protected function getIfIgnoreUser() {
			$bIgnoreLoggedInUser = $this->aGaOptions[ 'ignore_logged_in_user' ] == 'Y';
			$nCurrentUserLevel = $this->loadWpUsers()->getCurrentUserLevel();
			if ( $bIgnoreLoggedInUser && $nCurrentUserLevel >= 0 ) { // logged in
				$nIgnoreFromUserLevel = $this->aGaOptions[ 'ignore_from_user_level' ];
				if ( $nCurrentUserLevel >= $nIgnoreFromUserLevel ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * @return string
		 */
		public function getAnalyticsCode() {
			$sRaw = "
				<!-- Google Analytics Tracking by iControlWP -->
				<script type=\"text/javascript\">//<![CDATA[
					var _gaq=_gaq||[];
					_gaq.push(['_setAccount','%s']);
					_gaq.push(['_trackPageview']);
					( function() {
						var ga=document.createElement('script');
						ga.type='text/javascript';
						ga.async=true;
						ga.src=('https:'==document.location.protocol?'https://ssl':'http://www')+'.google-analytics.com/ga.js';
						var s=document.getElementsByTagName('script')[0];
						s.parentNode.insertBefore(ga,s);
					})();
				 //]]></script>
			";
			return sprintf( $sRaw, $this->aGaOptions[ 'tracking_id' ] );
		}

		/**
		 * @return string
		 */
		public function getAnalyticsCode_Universal() {
			$sRaw = "
				<!-- Google Analytics Tracking by iControlWP -->
				<script type=\"text/javascript\">//<![CDATA[
				  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
				  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
				  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
				  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

				  ga('create', '%s', 'auto');
				  ga('require', 'displayfeatures');
				  ga('send', 'pageview');
				 //]]></script>
			";
			return sprintf( $sRaw, $this->aGaOptions[ 'tracking_id' ] );
		}

		/**
		 * @return string
		 */
		protected function getWpHook() {
			if ( $this->aGaOptions[ 'in_footer' ] == 'Y' ) {
				return 'wp_print_footer_scripts';
			}
			return 'wp_head';
		}
	}

endif;