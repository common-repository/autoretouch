<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://autoretouch.com
 * @since      1.0.0
 *
 * @package    Wc_Autoretouch_Integration
 * @subpackage Wc_Autoretouch_Integration/admin
 */
?>

<div class="ar-admin-container">
    <div class="ar-admin-header-bar">
        <div class="ar-admin-header-logo-container">
            <img id="ar-header-logo" src="<?=plugin_dir_url( __FILE__ )?>assets/autoretouch-logo.svg"/>
        </div>
    </div>
    <div class="ar-admin-header-subtitle">
        <span class="ar-breadcrumb">auto</span>Retouch &gt;&gt; Settings
    </div>
    <div class="ar-admin-content">
		<?php if ( get_option( "ARI_isConnectedAccount" ) == false ) : ?>
            <div class="ar-admin-noticebox">
                <h3>No account connected.</h3>
                <p>It seems like you are not yet connected to your autoRetouch-account. This is necessary to use the
                    services provided by autoRetouch.<br/>
                    To use autoRetouch, please:
                </p>
                <ul>
                    <li>Sign up for an account at <a href="https://www.autoretouch.com" target="_blank">autoRetouch</a>
                    </li>
                    <li>Connect your autoRetouch account to your store by clicking "Connect Account" below</li>
                </ul>
                <a class="ar-admin-connect-button" id="ar-button-connect-account">Connect Account</a>
            </div>
            <div class="ar-admin-connect-code-box" id="ar-connect-code-box">
                <h3>Verify connection</h3>
                <p>Clicking on the "Verify"-button will open a new tab. <br/>Please make sure that the displayed
                    verification codes match.</p>
                <p id="ar-connect-code">Your verification code:</p>
                <a class="ar-admin-verify-button" id="ar-button-verify-account">Verify</a>
            </div>
		<?php endif; ?>
		<?php if ( get_option( "ARI_isConnectedAccount" ) == true ) : ?>
            <div class="ar-admin-infobox">
                <h3>Account information</h3>
                <p>These settings mirror your autoRetouch settings at the point in time of wordpress connection.<br />If you wish to use a different
                    organization, please change those settings on the <a id="ar-app-link" target="_blank" href="#">autoRetouch
                        platform</a> and reconnect your wordpress installation.</p>
                <div class="ar-account-info">
                    <div>
                        <div>Organization</div>
                        <div id="ar-account-info-org-name">-</div>
                    </div>
                    <div>
                        <div>Balance</div>
                        <div id="ar-account-info-org-balance">-</div>
                    </div>
                </div>
            </div>
            <div class="ar-admin-infobox">
                <h3>Workflows</h3>
                <p>This is a list of workflows associated with the selected organization. Clicking 'EDIT' will open a new autoRetouch window, where you can edit the workflow. <br />Refresh this page after any changes to see the updates.</p>
                <div class="ar-account-info">
                    <div>
                        <div>Available workflows</div>
                        <div>
                            <ul id="ar-account-info-org-workflows">
                                <li>-</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="ar-admin-infobox ar-danger-zone">
                <h3>Disconnect account</h3>
                <p>By clicking the button below, your wordpress will be disconnected from your autoRetouch account.</p>
                <a class="ar-admin-disconnect-button" id="ar-button-disconnect-account">Disconnect account</a>
            </div>
            <div class="ar-admin-infobox">
                <h3>Reset database</h3>
                <p>By clicking the button below, the integration database will be reset. All processing executions AND the local job history will be DELETED.</p>
                <a class="ar-admin-verify-button" id="ar-button-reset-database">Reset database</a>
            </div>
		<?php endif; ?>
    </div>
</div>