(function ($) {
    'use strict';

    var arTokenProbeMaxRetries = 20;
    var arTokenProbeInterval = 5000;
    var deviceAuthResult;

    $(document).ready(function () {
        $('#ar-button-connect-account').click(aRgetDeviceAuth);
        $('#ar-button-disconnect-account').click(aRdisconnectAccount);
        $('#ar-button-reset-database').click(aRresetDatabase);
        $('#ar-button-verify-account').click(aRdoUserVerification);

        if (arConfig.isConnected && $('div.ar-admin-container').length > 0) {
            aRgetOrganizations();
            aRgetBalance();
            aRgetWorkflows();
            aRsetLinkTarget()
        }
    });

    function aRgetDeviceAuth() {
        $.ajax({
            url: ajaxurl,
            type: 'post',
            data: {
                'action': 'ar_get_device_auth'
            },
            dataType: 'json',
            success: function (result) {
                deviceAuthResult = result;
                $('#ar-connect-code').html("Your verification code: [ " + result.user_code + " ]");
                $('#ar-connect-code-box').css('display', 'flex');
            },
            error: function (data) {
                alert("Something went wrong during verification initialisation. Please try again later or contact us at integrations@autoretouch.com.");
            }
        });
    };

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
                $('#ar-account-info-org-workflows').empty();
                for (var i = 0; i < result.entries.length; i++) {
                    var entry = result.entries[i];
                    var listItem = $('<li />');
                    listItem.append('<span />').text(entry.name);
                    var linkToWorkflow = $('<a class="ar-workflow-link" target="_blank">edit</a>');
                    linkToWorkflow.attr('href', arConfig.appURL + '/workflow/' + entry.id + '/edit?organization=' + arConfig.selectedOrganization);
                    listItem.append(linkToWorkflow);
                    $('#ar-account-info-org-workflows').append(listItem);
                }
            },
            error: function (data) {
                alert("Could not obtain autoRetouch organization info. Please try again later or contact us at integrations@autoretouch.com.");
            }
        });
    };


    function aRdoUserVerification(data) {
        var verificationWindow = window.open(deviceAuthResult.verification_uri, "_blank");
        probeToken(deviceAuthResult, 0);
    }

    function probeToken(data, probeCount) {
        probeCount++;
        $.ajax({
            url: ajaxurl,
            type: 'post',
            data: {
                'action': 'ar_probe_auth_token',
                'device_code': data.device_code
            },
            dataType: 'json',
            success: function (result) {
                if (!result.success && probeCount < arTokenProbeMaxRetries) {
                    setTimeout(function () {
                        probeToken(deviceAuthResult, probeCount)
                    }, arTokenProbeInterval);
                } else if (result.success) {
                    window.location.reload();
                } else {
                    alert("Authentication failed. Please try again later or contact us at integrations@autoretouch.com.");
                }
            },
            error: function (result) {
                console.log(result);
                if (probeCount < arTokenProbeMaxRetries) {
                    setTimeout(function () {
                        probeToken(deviceAuthResult, probeCount)
                    }, arTokenProbeInterval);
                } else {
                    alert("Authentication timed out. Please try again later or contact us at integrations@autoretouch.com.");
                }
            }
        });

    }

    function aRdisconnectAccount() {

        var confirmDisconnect = window.confirm('Do you really want to disconnect your account?');
        if(!confirmDisconnect) {
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'post',
            data: {
                'action': 'ar_disconnect_account'
            },
            success: function (result) {
                window.location.reload();
            },
            error: function (data) {
                alert("Account could not be disconnected. Please try again later or contact us at integrations@autoretouch.com.");
            }
        });
    }

    function aRresetDatabase() {
        var confirmDBReset = window.confirm('Do you really want to reset the database? All executions and job history will be deleted.');
        if(!confirmDBReset) {
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'post',
            data: {
                'action': 'ar_reset_database'
            },
            success: function (result) {
                alert("Database has been successfully reset.");
            },
            error: function (data) {
                alert("Error during database reset. Please try again later or contact us at integrations@autoretouch.com.");
            }
        });
    }

    function aRsetLinkTarget() {
        $('#ar-app-link').attr('href', arConfig.appURL);
    }


})
(jQuery);


