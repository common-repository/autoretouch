(function ($) {
    'use strict';

    var arOrganizations = null;
    var arWorkflows = null;
    var arUpdateInterval = null;

    $(document).ready(function () {

        if (arConfig.isConnected) {
            aRgetBalance();
            aRgetOrganizations();
            aRgetWorkflows();
        }
    });


    function getOrgForId(organizationId) {
        for(var i = 0; i < arOrganizations.length; i++) {
            if(arOrganizations[i].id == organizationId) {
                return arOrganizations[i];
            }
        }
        return null;
    }

    function getWorkflowForId(workflowId) {
        for(var i = 0; i < arWorkflows.length; i++) {
            if(arWorkflows[i].id == workflowId) {
                return arWorkflows[i];
            }
        }
        return null;
    }

    function getNameAndLinkFor(entry) {
        if(entry.result_post_id !== null && entry.result_post_id > 0) {
            return $('<a href="/wp-admin/upload.php?item='+ entry.result_post_id +'" />').text(entry.input_file_name);
        } else {
            return $('<span />').text(entry.input_file_name);
        }
    }

    function getStatusIconFor(job_status) {
        var icon = $('<span class="ar-execution-status" />');

        var iconClass = "";
        var iconMsg = "";

        switch(job_status) {
            case "CREATED":
                iconClass = "ar-blue";
                iconMsg = "Submitted";
                break;
            case "ACTIVE":
                iconClass = "ar-blue";
                iconMsg = "Processing";
                break;
            case "COMPLETED":
                iconClass = "ar-green";
                iconMsg = "Ready";
                break;
            case "FAILED":
                iconClass = "ar-red";
                iconMsg = "Failed";
                break;
            case "PAYMENT_REQUIRED":
                iconClass = "ar-red";
                iconMsg = "Insufficent Balance";
                break;
            case "WORDPRESS_ERROR":
                iconClass = "ar-red";
                iconMsg = "Wordpress Error";
                break;
        }

        icon.addClass(iconClass);
        icon.text(iconMsg);
        return icon;
    }


    function aRgetOrganizations() {
        $.ajax({
            url: ajaxurl,
            type: 'post',
            data: {
                'action': 'ar_get_organizations'
            },
            dataType: 'json',
            success: function (result) {
                for (var i = 0; i < result.entries.length; i++) {

                    var entry = result.entries[i];
                    if (entry.id == arConfig.selectedOrganization) {
                        $('#ar-account-info-org-name').text(entry.name);
                        break;
                    }
                }
                arOrganizations = result.entries;
                aRcheckStartUpdateLoop();
            },
            error: function (data) {
                alert("Could not obtain autoRetouch organization info. Please try again later or contact us at integrations@autoretouch.com.");
            }
        });
    };

    function aRgetBalance() {
        $.ajax({
            url: ajaxurl,
            type: 'post',
            data: {
                'action': 'ar_get_balance'
            },
            dataType: 'json',
            success: function (result) {
                var formattedBalance = "€" + result.balance.substring(0, result.balance.length - 2) + "." + result.balance.substr(result.balance.length - 2, 2);
                $('#ar-account-info-org-balance').text(formattedBalance);
            },
            error: function (data) {
                alert("Could not obtain autoRetouch organization balance. Please try again later or contact us at integrations@autoretouch.com.");
            }
        });
    };

    function aRgetWorkflows() {
        $.ajax({
            url: ajaxurl,
            type: 'post',
            data: {
                'action': 'ar_get_workflows'
            },
            dataType: 'json',
            success: function (result) {
                arWorkflows = result.entries;
                aRcheckStartUpdateLoop();
            },
            error: function (data) {
                alert("Could not obtain autoRetouch organization info. Please try again later or contact us at integrations@autoretouch.com.");
            }
        });
    };

    function aRcheckStartUpdateLoop() {
        if(arOrganizations !== null && arWorkflows !== null) {
            aRonRequestStatusUpdate();
        }
    }


    function aRonRequestStatusUpdate() {
        $.ajax({
            url: ajaxurl,
            type: 'post',
            data: {
                'action': 'ar_get_executions'
            },
            dataType: 'json',
            success: function (result) {
                $('#ar-execution-list-content').empty();

                for(var i = 0; i < result.length; i++) {
                    var entry = result[i];
                    var row = $('<tr />');


                    var workflow = getWorkflowForId(entry.workflow_id);
                    var organization = getOrgForId(entry.organization_id);

                    var workflowName = workflow ? workflow.name : "-";
                    var orgName = organization ? organization.name : "-";
                    var statusIcon = getStatusIconFor(entry.job_status);

                    var nameAndLink = getNameAndLinkFor(entry);

                    row.append($('<td />').append(nameAndLink));
                    row.append($('<td />').text(workflowName));
                    row.append($('<td />').text(orgName));
                    row.append($('<td />').text(entry.started_at));
                    row.append($('<td />').append(statusIcon));

                    $('#ar-execution-list-content').append(row);
                }
                setTimeout(aRonRequestStatusUpdate, 2500);
            },
            error: function (data) {
                alert("Could not obtain executions' status. Please try again later or contact us at integrations@autoretouch.com.");
            }
        });
    }

})
(jQuery);


