(function ($) {
    'use strict';

    window.aRonMediaSubmissionHandler = function (postId) {
        var workflowVersion = $('#ar-media-handler-form-workflow-select').val();

        var workflowVersionSplit = workflowVersion.split("::");
        var workflowId = workflowVersionSplit[0];
        var workflowVersionId = workflowVersionSplit[1];

        $('#ar-media-handler-form-workflow-select').attr('disabled', true);
        $('#ar-media-handler-form-workflow-submit-button').addClass('ar-media-handler-form-submit-button-disabled');
        $.ajax({
            url: ajaxurl,
            type: 'post',
            data: {
                'action': 'ar_media_submit_to_service',
                'post_id': postId,
                'workflow_id': workflowId,
                'workflow_version': workflowVersionId
            },
            success: function (result) {
                $('#ar-media-handler-form-workflow-select').attr('disabled', false);
                $('#ar-media-handler-form-workflow-submit-button').removeClass('ar-media-handler-form-submit-button-disabled');
                $('#ar-media-handler-container-is-processing').removeClass('ar-media-handler-container-content-invisible');
                $('#ar-media-handler-container-form').addClass('ar-media-handler-container-content-invisible');

            },
            error: function (data) {
                $('#ar-media-handler-form-workflow-select').attr('disabled', false);
                $('#ar-media-handler-form-workflow-submit-button').removeClass('ar-media-handler-form-submit-button-disabled');
                $('#ar-media-handler-container-is-error').removeClass('ar-media-handler-container-content-invisible');
                $('#ar-media-handler-container-form').addClass('ar-media-handler-container-content-invisible');
            }
        });
    }

})
(jQuery);


