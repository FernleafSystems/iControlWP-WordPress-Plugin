<?php

class ICWP_APP_Processor_GoogleAnalytics extends ICWP_APP_Processor_BaseApp {

	/**
	 * @var array
	 */
	private $aGaOptions;

	public function run() {
		add_action( 'wp', array( $this, 'onWp' ) );
	}

	public function onWp() {
		$this->migrateOptions();

		$sID = $this->getTrackingId();
		if ( !empty( $sID ) && !$this->getIfIgnoreUser() ) {
			add_action( $this->getWpHook(), array( $this, 'doPrintGoogleAnalytics' ), 100 );
			if ( $this->getAnalyticsMode() == 'tagman' ) {
				add_action( 'wp_body_open', array( $this, 'printTagsBody' ) );
			}
		}
	}

	/**
	 * Added with 3.7.0
	 */
	private function migrateOptions() {
		$oMod = $this->getFeatureOptions();
		if ( $oMod->getOpt( 'analytics_mode' ) == 'unset' ) {
			$oMod->setOpt(
				'analytics_mode',
				( $oMod->getOpt( 'enable_universal_analytics' ) == 'Y' ) ? 'universal' : 'classic'
			);
		}
	}

	/**
	 * @return void|string
	 */
	public function doPrintGoogleAnalytics() {
		switch ( $this->getAnalyticsMode() ) {
			case 'universal':
				$sGA = $this->getAnalyticsCode_Universal();
				break;
			case 'tagman':
				$sGA = $this->getAnalyticsCode_TagManager();
				break;
			case 'sitetag':
				$sGA = $this->getAnalyticsCode_SiteTag();
				break;
			default:
			case 'classic':
				$sGA = $this->getAnalyticsCode_Classic();
				break;
		}
		echo $sGA;
	}

	/**
	 * @return string
	 */
	private function getAnalyticsMode() {
		$aOpts = $this->getGaOpts();
		return $aOpts[ 'analytics_mode' ];
	}

	/**
	 * @return string
	 */
	private function getTrackingId() {
		$aOpts = $this->getGaOpts();
		return $aOpts[ 'tracking_id' ];
	}

	/**
	 * @return array
	 */
	private function getGaOpts() {
		$oMod = $this->getFeatureOptions();
		if ( empty( $this->aGaOptions ) ) {
			$this->aGaOptions = array(
				'tracking_id'            => $oMod->getOpt( 'tracking_id' ),
				'analytics_mode'         => $oMod->getOpt( 'analytics_mode' ),
				'ignore_logged_in_user'  => $oMod->getOpt( 'ignore_logged_in_user' ),
				'ignore_from_user_level' => $oMod->getOpt( 'ignore_from_user_level', 11 ),
				'in_footer'              => $oMod->getOpt( 'in_footer' ),
			);
		}
		return $this->aGaOptions;
	}

	/**
	 * @return bool
	 */
	private function getIfIgnoreUser() {
		$bIgnore = false;

		$aOpts = $this->getGaOpts();
		$nCurrentUserLevel = $this->loadWpUsers()->getCurrentUserLevel();
		if ( ( $aOpts[ 'ignore_logged_in_user' ] == 'Y' ) && $nCurrentUserLevel >= 0 ) { // logged in
			$nIgnoreFromUserLevel = $aOpts[ 'ignore_from_user_level' ];
			if ( $nCurrentUserLevel >= $nIgnoreFromUserLevel ) {
				$bIgnore = true;
			}
		}

		return $bIgnore;
	}

	/**
	 * @return string
	 */
	private function getAnalyticsCode_Classic() {
		$sRaw = "
				<!-- Google Analytics by iControlWP -->
				<script type=\"text/javascript\">//<![CDATA[
					var _gaq=_gaq||[];
					_gaq.push(['_setAccount','{{TRACKING_ID}}']);
					_gaq.push(['_trackPageview']);
					( function() {
						var ga=document.createElement('script');
						ga.type='text/javascript';
						ga.async=true;
						ga.src=('https:'==document.location.protocol?'https://ssl':'http://www')+'.google-analytics.com/ga.js';
						var s=document.getElementsByTagName('script')[0];
						s.parentNode.insertBefore(ga,s);
					})();
				 //]]></script>\n";
		return str_replace( '{{TRACKING_ID}}', $this->getTrackingId(), $sRaw );
	}

	/**
	 * @return string
	 */
	public function getAnalyticsCode_TagManager() {
		$sRaw = "
			<!-- Google Tag Manager by iControlWP -->
			<script>(function(w,d,s,l,i){
				w[l]=w[l]||[];
				w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});
				var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';
				j.async=true;
				j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
			})(window,document,'script','dataLayer','{{TRACKING_ID}}');</script>
			<!-- End Google Tag Manager -->\n";
		return str_replace( '{{TRACKING_ID}}', $this->getTrackingId(), $sRaw );
	}

	/**
	 * @return string
	 */
	public function getAnalyticsCode_SiteTag() {
		$sRaw = "
		<!-- Global site tag (gtag.js) by iControlWP  -->
		<script async src=\"https://www.googletagmanager.com/gtag/js?id={{TRACKING_ID}}\"></script>
		<script>
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}
			gtag('js', new Date());
			gtag('config', '{{TRACKING_ID}}');
		</script>\n";
		return str_replace( '{{TRACKING_ID}}', $this->getTrackingId(), $sRaw );
	}

	/**
	 */
	public function printTagsBody() {
		$sRaw = '
			<!-- Google Tag Manager (noscript) by iControlWP -->
			<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{TRACKING_ID}}"
			height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
			<!-- End Google Tag Manager (noscript) -->
		';
		echo str_replace( '{{TRACKING_ID}}', $this->getTrackingId(), $sRaw );
	}

	/**
	 * @return string
	 */
	public function getAnalyticsCode_Universal() {
		$sRaw = "
				<!-- Google Analytics (Universal) by iControlWP -->
				<script type=\"text/javascript\">//<![CDATA[
				  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
				  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
				  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
				  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

				  ga('create', '{{TRACKING_ID}}', 'auto');
				  ga('require', 'displayfeatures');
				  ga('send', 'pageview');
				 //]]></script>\n";
		return str_replace( '{{TRACKING_ID}}', $this->getTrackingId(), $sRaw );
	}

	/**
	 * @return string
	 */
	private function getWpHook() {
		$aOpts = $this->getGaOpts();
		$sHook = 'wp_head';
		if ( $this->getAnalyticsMode() != 'tagman' && $aOpts[ 'in_footer' ] == 'Y' ) {
			$sHook = 'wp_print_footer_scripts';
		}
		return $sHook;
	}
}