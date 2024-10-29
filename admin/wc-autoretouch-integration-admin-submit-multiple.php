<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://autoretouch.com
 * @since      1.0.1
 *
 * @package    Wc_Autoretouch_Integration
 * @subpackage Wc_Autoretouch_Integration/admin
 */

$post_ids_query_param = urldecode_deep( trim( sanitize_text_field( $_GET['post_ids'] ) ) );

if ( $post_ids_query_param == '' ) {
	wp_die( "Invalid parameters." );
}
$post_ids = explode( ",", $post_ids_query_param );

$num_posts = count( $post_ids );

$submissions = [];

for ( $i = 0; $i < $num_posts; $i ++ ) {
	if ( ! is_numeric( $post_ids[ $i ] ) ) {
		wp_die( "Invalid post id." );
	}

	$post_info = array(
		'meta' => get_post( $post_ids[ $i ] ),
		'url'  => wp_get_attachment_thumb_url( $post_ids[ $i ] )
	);

	if (
		$post_info['meta']->post_mime_type == 'image/png' ||
		$post_info['meta']->post_mime_type == 'image/jpg' ||
		$post_info['meta']->post_mime_type == 'image/jpeg'
	) {
		array_push( $submissions, $post_info );
	}
}

$ar_api = Wc_Autoretouch_Integration_API::get_instance();

$workflows = $ar_api->get_workflows();

$last_selected_workflow = get_option("ARI_lastSelectedWorkflowId");

?>

<div class="ar-admin-container">
    <div class="ar-admin-header-bar">
        <div class="ar-admin-header-logo-container">
            <img id="ar-header-logo" src="<?= plugin_dir_url( __FILE__ ) ?>assets/autoretouch-logo.svg"/>
        </div>
    </div>
    <div class="ar-admin-header-subtitle">
        <span class="ar-breadcrumb">auto</span>Retouch &gt;&gt; Submit multiple images
    </div>
    <div class="ar-admin-content">
        <div class="ar-admin-infobox">
            <h3>Submit multiple images</h3>
            <p>Please review your selection of images to be submitted:</p>
            <div class="ar-submit-multiple-thumbnails-container">
				<?php
				foreach ( $submissions as $submission ) {
					?><img src="<?= $submission['url'] ?>" alt="<?= $submission['meta']->post_title ?>" title="<?= $submission['meta']->post_title ?>"/><?php
				}
				?>
            </div>
        </div>
        <div class='ar-media-handler-container'>
            <div class='ar-media-handler-title-row'>
                <img id='ar-mh-title-icon' src='<?= plugin_dir_url( __FILE__ ) ?>/assets/menu-icon.svg'/>
                <span id='ar-mh-title-label'>auto</span><span>Retouch Integration</span>
            </div>
            <div id='ar-media-handler-container-form' class='ar-media-handler-container-content".$form_class."'>
                <div class='ar-media-handler-form-notice-row'>
                    <p>Please select the workflow you would like to be used for this batch and click
                        &quot;Submit&quot;.
                    </p>
                </div>
                <div class='ar-media-handler-form-label-row'>select workflow:</div>
                <div class='ar-media-handler-form-select-row'>
                    <select id='ar-media-handler-form-workflow-select' class='ar-media-handler-form-select'>
                        <?php
                        foreach($workflows->entries as $workflow) {
                        ?><option value="<?=$workflow->id?>::<?=$workflow->version?>" <?=$last_selected_workflow == $workflow->id?"selected":""?>><?=$workflow->name?></option><?php
                        }
                        ?>
                    </select>
                </div>
                <div class='ar-media-handler-form-submit-row'>
                    <a id='ar-media-handler-form-workflow-submit-button' class='ar-media-handler-form-submit-button'
                       onclick='aRonMultipleMediaSubmissionHandler(<?=$post_ids_query_param?>);'>submit images</a>
                </div>
            </div>
            <div id='ar-media-handler-container-is-error'
                 class='ar-media-handler-container-content ar-media-handler-container-content-invisible'>
                <div class='ar-media-handler-form-notice-row'>
                    <p>There has been an error with the upload.<br/>Please try again later.<br/></p>
                </div>
            </div>
            <div id='ar-media-handler-container-is-processing'
                 class='ar-media-handler-container-content ar-media-handler-container-content-invisible'>
                <div class='ar-media-handler-form-notice-row'>
                    <p>Please wait while jobs are being submitted to autoRetouch.<br />
                    <span id="ar-media-submission-status-text"></span><br/><br/></p>
                </div>
            </div>
            <div id='ar-media-handler-container-is-done'
                 class='ar-media-handler-container-content ar-media-handler-container-content-invisible'>
                <div class='ar-media-handler-form-notice-row'>
                    <p>Your images have been submitted to autoRetouch<br/>You can follow the process <a
                                href='<?= admin_url() ?>admin.php?page=wc-autoretouch-integration-sub-history'>here</a>.<br/><br/>
                    </p>
                </div>
            </div>

            <div class='ar-media-handler-title-row'>
            </div>
        </div>
    </div>
</div>