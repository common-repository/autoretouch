(function ($) {
    'use strict';

    window.aRonMultipleMediaSubmissionHandler = function (...postIds) {
        var workflowVersion = $('#ar-media-handler-form-workflow-select').val();

        var workflowVersionSplit = workflowVersion.split("::");
        var workflowId = workflowVersionSplit[0];
        var workflowVersionId = workflowVersionSplit[1];

        $('#ar-media-handler-container-is-processing').removeClass('ar-media-handler-container-content-invisible');
        $('#ar-media-handler-container-form').addClass('ar-media-handler-container-content-invisible');

        var currentId = 0;
        var numPosts = postIds.length;

        aRcheckNextSubmissible(currentId, numPosts, workflowId, workflowVersionId, postIds);
    }

    function aRcheckNextSubmissible(currentId, numPosts, workflowId, workflowVersionId, postIds) {
        if(postIds.length == 0) {
            $('#ar-media-handler-container-is-done').removeClass('ar-media-handler-container-content-invisible');
            $('#ar-media-handler-container-is-processing').addClass('ar-media-handler-container-content-invisible');
            return;
        }

        $('#ar-media-submission-status-text').text("Submitting " + (++currentId) + " of " + numPosts + " jobs.");

        var postId = postIds.shift();

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
                aRcheckNextSubmissible(currentId, numPosts, workflowId, workflowVersionId, postIds);
            },
            error: function (data) {
                $('#ar-media-handler-container-is-error').removeClass('ar-media-handler-container-content-invisible');
                $('#ar-media-handler-container-is-processing').addClass('ar-media-handler-container-content-invisible');
            }
        });
    }

})
(jQuery);


