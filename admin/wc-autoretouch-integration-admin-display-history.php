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
        <span class="ar-breadcrumb">auto</span>Retouch &gt;&gt; Job History
    </div>
    <div class="ar-admin-content">
		<?php if ( get_option( "ARI_isConnectedAccount" ) == false ) : ?>
            <div class="ar-admin-noticebox">
                <h3>No account connected.</h3>
                <p>Please connect an autoRetouch account on the "Settings"-page.</p>
            </div>

		<?php endif; ?>

		<?php if ( get_option( "ARI_isConnectedAccount" ) == true ) : ?>
            <div class="ar-admin-infobox">
                <h3>List of executions</h3>
                <p>Here you can see a list of all autoRetouch executions you submitted. In case of a failed process due to error or insufficent funds, please check <a href="https://www.autoretouch.com" target="_blank">autoRetouch</a>.</p>
                <table class="ar-history-results-table">
                    <thead>
                        <tr>
                            <th>input file</th>
                            <th>workflow</th>
                            <th>organization</th>
                            <th>started</th>
                            <th>status</th>
                        </tr>
                    </thead>
                    <tbody id="ar-execution-list-content" />
                </table>
            </div>
		<?php endif; ?>
    </div>
</div>