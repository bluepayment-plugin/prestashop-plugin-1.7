/**
 * BlueMedia_BluePayment extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU Lesser General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-3.0.en.html
 *
 * @category       BlueMedia
 * @package        BlueMedia_BluePayment
 * @copyright      Copyright (c) 2015-2026
 * @license        https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
 */

$(document).ready(function () {
    let gpayConfigUrl = null,
        paymentsClient = null,
        gpayConfig = null,
        bmSubmitGpayData = {},
        conditionsSelector = null,
        gpayLoading = false;

    if ($('#gpay-button').length !== 0) {
        initializeGpay();
    }

    // Checkouts which re-render payment options (ps_onepagecheckout) call it after each refresh
    window.bluepaymentGpay = {
        init: function () {
            if ($('#gpay-button').length === 0) {
                return;
            }

            if (gpayConfig === null) {
                if (!gpayLoading) {
                    initializeGpay();
                }

                return;
            }

            conditionsSelector = $('#conditions-to-approve input[type="checkbox"]');
            addGooglePayButton();

            // The checkout re-renders payment options after cart changes (e.g. carrier), so the amount may differ
            if (!gpayLoading && !bmSubmitGpayData.postOrderId) {
                loadGpayConfig();
            }
        },
    };

    function initializeGpay() {
        conditionsSelector = $('#conditions-to-approve input[type="checkbox"]');
        gpayConfigUrl = $('#gpay-url').attr('data-merchant-info-address');

        loadGpayConfig().done(function (response) {
            gpayLoading = true;
            $.getScript("https://pay.google.com/gp/p/js/pay.js", function () {
                gpayConfig = JSON.parse(response);
                gpayLoading = false;
                addGooglePayButton();
            });
        });
    }

    function loadGpayConfig() {
        gpayLoading = true;

        return $.ajax(gpayConfigUrl, {
            method: 'POST',
            data: {"postOrderId": null},
        }).done(function (response) {
            if (gpayConfig !== null) {
                gpayConfig = JSON.parse(response);
            }
        }).fail(function (response, status, error) {
            console.error(status, error);
        }).always(function () {
            gpayLoading = false;
        });
    }

    function getGooglePaymentsClient() {
        if (paymentsClient === null) {
            paymentsClient = new google.payments.api.PaymentsClient({environment: bluepayment_env});
        }

        return paymentsClient;
    }

    function addGooglePayButton() {
        if ($('#gpay-button').length === 0 || $('#gpay-button').children().length !== 0) {
            return;
        }

        paymentsClient = getGooglePaymentsClient();
        document.getElementById('gpay-button')
            .appendChild(paymentsClient.createButton({onClick: onGooglePaymentButtonClicked}));
    }

    function onGooglePaymentButtonClicked() {
        if (!conditionsSelector.prop("checked")) {
            $('.js-g-pay-terms-of-use').show();
            return;
        } else {
            $('.js-g-pay-terms-of-use').hide();
        }

        // Do not show Google Pay with an amount that is being refreshed
        if (gpayLoading) {
            return;
        }

        paymentsClient = getGooglePaymentsClient();
        paymentsClient.loadPaymentData(gpayConfig)
            .then(function (paymentData) {
                // loadPaymentData() has to run straight from the click, so the checkout is saved afterwards
                return beforePayment().then(function (canPay) {
                    if (canPay) {
                        processPayment(paymentData);
                    }
                });
            })
            .catch(function (err) {
                console.error(err);
            });
    }

    /**
     * Checkout integrations (e.g. ps_onepagecheckout) may define
     * window.bluepaymentCheckout.beforePayment() to save and validate the checkout first.
     */
    function beforePayment() {
        var checkout = window.bluepaymentCheckout;

        // A retry pays for the order already created from this cart, so there is no checkout to save
        if (!checkout || typeof checkout.beforePayment !== 'function' || bmSubmitGpayData.postOrderId) {
            return Promise.resolve(true);
        }

        return Promise.resolve(checkout.beforePayment()).catch(function () {
            return false;
        });
    }

    function processPayment(paymentData) {
        let paymentToken = paymentData.paymentMethodData.tokenizationData.token;

        if (bluepayment_env !== 'TEST') {
            paymentToken = JSON.parse(paymentToken);
        }

        chargeGPay(paymentToken);
    }

    function chargeGPay(paymentToken) {
        bmShowLoader();

        var responseMessages = $('#responseGPayMessages');
        var chargeGpayAction = $('#gpay-url').attr('data-charge-address');

        bmSubmitGpayData.token = paymentToken;

        $.ajax(chargeGpayAction, {
            method: "POST",
            data: bmSubmitGpayData,
            success: function (response) {
                response = JSON.parse(response);
                if (response) {
                    if (response.status === 'PENDING') {
                        if (typeof response.redirectUrl !== "undefined" && response.redirectUrl.length > 10) {
                            window.location.href = response.redirectUrl;
                        }

                        responseMessages.empty().append($('<b>').text(response.message));
                        responseMessages.show();
                        bmSubmitGpayData.postOrderId = response.postOrderId;
                        setTimeout(function () {
                            chargeGPay(paymentToken);
                        }, 1250);
                    } else if (response.status === 'SUCCESS') {
                        window.location.href = response.backUrl
                    } else {
                        bmSubmitGpayData.postOrderId = response.postOrderId;
                        bmHideLoader();
                        responseMessages.parent('div').addClass('has-error');
                        responseMessages.text(response.message);
                        responseMessages.show();
                    }
                } else {
                    bmHideLoader();
                    responseMessages.parent('div').addClass('has-error');
                    responseMessages.text('Transaction ERROR - Empty data.');
                    responseMessages.show();
                }
            },
            error: function(data) {
                bmHideLoader();
            }
        });
    }

    function bmShowLoader () {
        $('.bluepayment-loader').fadeIn(400)
        $('.bluepayment-loader-bg').fadeIn(300)
    }

    function bmHideLoader () {
        $('.bluepayment-loader').fadeOut(300)
        $('.bluepayment-loader-bg').fadeOut(300)
    }
});
