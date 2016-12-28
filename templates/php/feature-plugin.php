<?php

$sUrlServiceHomeHelp = 'http://icwp.io/help';
$sUrlServiceHomeFeatures = 'http://icwp.io/features';

$bWhitelabelled = ($aPluginLabels['Name'] != 'iControlWP');
?>

	<script type="text/javascript">
		jQuery( document ).ready(
			function () {
				jQuery( 'input.confirm-plugin-reset' ).on( 'click',
					function() {
						var $oThis = jQuery( this );
						if ( $oThis.is( ':checked' ) ) {
							jQuery( 'button[name=submit_reset]' ).removeAttr( 'disabled' );
						}
						else {
							jQuery( 'button[name=submit_reset]' ).attr( 'disabled', 'disabled' );
						}
					}
				);
			}
		);
		function icwp_formAddSiteSubmit() {
			var $elemSubmit = jQuery( "button[name=icwp_add_remotely_submit]" );
			$elemSubmit.html( "Please wait, attempting to add site - please do not reload this page." );
			$elemSubmit.attr( "disabled", "disabled" );

			var form = jQuery( "#icwpform-remote-add-site" ).submit();
		}
	</script>
	<style>
		#pluginlogo_32 {
			background: url( "<?php echo $aPluginLabels['icon_url_32x32']; ?>" ) no-repeat 0 3px transparent;
		}
	</style>

	<div class="row">
		<div class="span12">
			<div class="well">
				<?php
				if ( empty( $aHiddenOptions['key'] ) ) {
					echo '<h3>You need to generate your Access Key - reset your key using the red button below.</h3>';
				}
				?>
				<div class="assigned-state">
					<?php if ( $bAssigned ): ?>
						<h3 id="isAssigned"><?php echo sprintf( 'Currently connected to %s.%s', '<u>'.$aPluginLabels['Name'].'</u>', ($bWhitelabelled? '' : " ($sAssignedTo)") ); ?></h3>

					<?php else: ?>
						<h3>The unique <?php echo $aPluginLabels['Name']; ?> Access Key for this site is:
							<div class="the-key"><?php echo $sAuthKey; ?></div>
						</h3>
						<h4 id="isNotAssigned">Currently waiting for connection from a <?php echo $aPluginLabels['Name']; ?> account.
							<br/>[ <a href="<?php echo $aPluginLabels['PluginURI']; ?>" id="signupLinkIcwp" target="_blank">Don't have a <?php echo $aPluginLabels['Name']; ?> account? Get it today!</a> ]</h4>
						<p><strong>Important:</strong> if you don't plan to add this site now, disable this plugin to prevent this site from being added to another <?php echo $aPluginLabels['Name']; ?> account.</p>
					<?php endif; ?>
				</div>

			</div>
		</div>
	</div>
<?php if ( !$bIsLinked ) : ?>
	<div class="row">
		<div class="span12">
			<div class="well">
				<h3>Remotely add site to <?php echo $aPluginLabels['Name']; ?> account</h3>
				<p>You may add your site to your <?php echo $aPluginLabels['Name']; ?> from here, or from within your <?php echo $aPluginLabels['Name']; ?> Dashboard. Both methods are supported and secure.</p>
				<p>Note: If this doesn't work, your web host probably has restrictions on outgoing web connections. Please try adding this site from you <?php echo $aPluginLabels['Name']; ?> dashboard.</p>
				<form method="POST" name="icwpform-remote-add-site" id="form-remote-add-site" class="">
					<?php echo $nonce_field; ?>
					<input type="hidden" name="<?php echo $var_prefix; ?>plugin_form_submit" value="Y" />
					<fieldset>
						<legend style="margin-bottom: 8px;">Remote Add Site</legend>
						<label for="_account_auth_key"><?php echo $aPluginLabels['Name']; ?> Unique Account Authentication Key:
							<input name="account_auth_key" type="text" class="span6" id="_account_auth_key" />
						</label>
						<label for="_account_email_address"><?php echo $aPluginLabels['Name']; ?> Account Email Address:
							<input name="account_email_address" type="text" class="span6" id="_account_email_address"/>
						</label>
					</fieldset>
					<button class="btn" name="<?php echo $var_prefix; ?>remotely_add_site_submit" value="Y" type="submit" onclick="icwp_formAddSiteSubmit()" >Add Site</button>
				</form>
			</div>
		</div>
	</div>
<?php endif; ?>

	<div class="row">
		<div class="span12">
			<div class="well">
				<div class="reset-authentication" name="">
					<h3>Reset <?php echo $aPluginLabels['Name']; ?> Access Key</h3>
					<p>You can break the connection with <?php echo $aPluginLabels['Name']; ?> and regenerate a new access key, using the button below</p>
					<p><strong>Warning:</strong> Clicking this button <em>will disconnect this site if it has been added to a <?php echo $aPluginLabels['Name']; ?> account</em>. <u>Not Recommended</u>.</p>
					<div>
						<form action="<?php echo $form_action; ?>" method="POST" name="form-reset-auth" id="form-reset-auth">
							<?php echo $nonce_field; ?>
							<input type="hidden" name="<?php echo $var_prefix; ?>plugin_form_submit" value="Y" />
							<label>
								<input type="checkbox"
									   name="<?php echo $var_prefix; ?>reset_plugin"
									   class="confirm-plugin-reset"
									   value="Y"
									   style="margin-right:10px;"
									/>I'm sure I want to reset the <?php echo $aPluginLabels['Name']; ?> plugin.
							</label>
							<button class="btn btn-danger" disabled="disabled" name="submit_reset" type="submit">Reset Plugin</button>
						</form>
					</div>
				</div>

			</div>
		</div>
	</div>

<?php if ( false ) : ?>
	<div class="row">
		<div class="span6">
			<div class="well">
				<div class="">
					<h3>Google Analytics</h3>
					<ul>
						<?php if ($options_ga['enabled'] ) : ?>
							<li>Enabled</li>
							<li>Tracking ID: <?php echo $options_ga['tracking_id']; ?></li>
							<li>Ignore Logged-In User Level: <?php echo $options_ga['ignore_logged_in_user']? $options_ga['ignore_from_user_level'] : 'No'; ?>+</li>
						<?php else : ?>
							<li>Disabled</li>
						<?php endif; ?>
					</ul>
				</div>
			</div>
		</div>
		<div class="span6">
			<div class="well">
				<div class="">
					<h3>Automatic Updates</h3>
					<ul>
						<?php if ($options_au['enabled'] ) : ?>
							<li>Enabled</li>
							<?php if ( !empty($options_au['auto_update_plugins']) ) : ?>
								<li>Auto Update Plugins:
									<ul style="list-style: lower-roman outside none;">
										<?php foreach( $options_au['auto_update_plugins'] as $sPlugin ) : ?>
											<li style="margin: 0;"><?php echo $sPlugin; ?></li>
										<?php endforeach; ?>
									</ul>
								</li>
							<?php endif; ?>
						<?php else : ?>
							<li>Disabled</li>
						<?php endif; ?>
					</ul>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>

<?php if ( !$bWhitelabelled ) : ?>

	<div class="row">
		<div class="span12">
			<div class="well">
				<div class="row">
					<div class="span11">
						<h2>About <?php echo $aPluginLabels['Name']; ?></h2>
						<div>
							<p><?php echo $aPluginLabels['Name']; ?> is <strong>completely free</strong> to get started with an unlimited sites 15 day trial -
								<a href="<?php echo $aPluginLabels['PluginURI']; ?>" id="signupLinkIcwp" target="_blank">Sign Up for a <?php echo $aPluginLabels['Name']; ?> account here</a>.</p>
						</div>
						<h3><?php echo $aPluginLabels['Name']; ?> Features [<a href="<?php echo $sUrlServiceHomeFeatures; ?>" target="_blank">full details</a>]</h3>
					</div>
				</div>
				<div class="row">
					<div class="span5">
						<ul>
							<li>Free to get started with with unlimited sites, 15 day trial</li>
							<li>Manage all your WordPress sites in 1 place</li>
							<li>One-click Updates for WordPress.org Plugins, Themes and WordPress Core</li>
							<li>One-Click login to admin each WordPress website</li>
							<li>True pay-as-you-go pricing.</li>
						</ul>
					</div>
					<div class="span6">
						<ul>
							<li>Fully Automated WordPress Installer Tool!</li>
							<li>Complete <?php echo $aPluginLabels['Name']; ?> Dashboard Access - no standard/pro/business tiers</li>
							<li>Access to all future <?php echo $aPluginLabels['Name']; ?> Dashboard updates!</li>
							<li>Smooth scaling based on your needs</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php if ( false ) : ?>
		<div class="row">
			<div class="span12">
				<div class="well">

					<div class="send-debug">
						<h3>Send <?php echo $aPluginLabels['Name']; ?> Site Debug</h3>
						<p>No two WordPress sites are created equal. The sheer variations of configurations are mind-blowing, so writing <?php echo $aPluginLabels['Name']; ?> to work for everyone is not
							trivial.</p>
						<p>So if your site is having issues with <?php echo $aPluginLabels['Name']; ?>, don't fret. You can help us out by sending us some information about your configuration using
							the buttons below.</p>
						<p>We <strong>wont collect sensitive information</strong> about you or any passwords etc. We're only interested in information about the plugins you're
							using, your WordPress version, your PHP and server configuration. Further, you will be able to review what will be sent before you send it.</p>
						<div>
							<form action="<?php echo $form_action; ?>" method="POST" name="form-send-debug" id="form-send-debug">
								<?php wp_nonce_field( $nonce_field ); ?>
								<input type="hidden" name="icwp_admin_form_submit" value="1" />
								<input type="hidden" name="icwp_admin_form_submit_debug" value="1" />
								<button class="btn btn-inverse" name="submit_gather" type="submit" style="margin-right:8px;">Gather Information</button>

								<?php if ( !$debug_file_url ): ?>
									<button class="btn btn-info" name="view_information" type="submit" style="margin-right:8px;" disabled="disabled">View Information</button>
								<?php else: ?>
									<a href="<?php echo $debug_file_url; ?>" class="btn btn-info" name="view_information" type="submit" style="margin-right:8px;" target="_blank">View Information</a>
								<?php endif; ?>

								<button class="btn btn-success" name="submit_information" type="submit" style="margin-right:8px;" <?php if ( !$debug_file_url ): ?>disabled="disabled"<?php endif; ?>>Send Debug Information</button>
							</form>
						</div>
					</div>

				</div>
			</div>
		</div>
	<?php endif; ?>

<?php endif;