<?php
$sBaseDirName = dirname(__FILE__).DIRECTORY_SEPARATOR;
include_once( $sBaseDirName.'icwp_options_helper.php' );
include_once( $sBaseDirName.'widgets/icwp_widgets.php' );

$sPluginName = 'iControlWP';
?>
<div class="wrap">
	<div class="bootstrap-wpadmin <?php echo isset($icwp_sFeatureSlug) ? $icwp_sFeatureSlug : ''; ?>">
		<div class="row">
			<div class="span12">
				<?php include_once( $sBaseDirName.'snippets/state_summary.php' ); ?>
			</div>
		</div>
<?php
printOptionsPageHeader( $icwp_sFeatureName, $icwp_sPluginName );
