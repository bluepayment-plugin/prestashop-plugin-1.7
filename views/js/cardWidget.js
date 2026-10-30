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
    // TODO: potwierdzić dokładny URL produkcyjny SDK z Autopay przed wdrożeniem
    var CARD_WIDGET_SDK_URL_TEST = 'https://testcards.autopay.eu/widget-new/widget-communication.min.js';
    var CARD_WIDGET_SDK_URL_PROD = 'https://cards.autopay.eu/widget-new/widget-communication.min.js';

    let widget = null,
        widgetIframe = null,
        sdkLoading = null,
        bmSubmitCardWidgetData = {},
        cardWidgetVerifyCheck = null;

    initCardWidget();

    // Checkouts which re-render payment options (ps_onepagecheckout) call it after each refresh
    window.bluepaymentCard = {
        init: initCardWidget,
    };

    function initCardWidget() {
        var iframe = document.getElementById('card-widget-iframe');

        if (!iframe || iframe === widgetIframe) {
            return;
        }

        widgetIframe = iframe;
        initializeCardWidgetSdk(iframe);
    }

    function initializeCardWidgetSdk(iframe) {
        var sdkUrl = bluepayment_env === 'TEST' ? CARD_WIDGET_SDK_URL_TEST : CARD_WIDGET_SDK_URL_PROD;

        if (!sdkLoading) {
            sdkLoading = $.getScript(sdkUrl);
        }

        sdkLoading.done(function () {
            // The checkout may re-render the payment options while the SDK loads; the newer iframe has its own init
            if (iframe === widgetIframe && document.body.contains(iframe)) {
                initializeCardWidget(iframe);
            }
        });
    }

    function initializeCardWidget(iframe) {
        var configElement = $('#card-widget-url');

        var widgetConfig = {
            language: configElement.attr('data-language'),
            amount: parseFloat(configElement.attr('data-amount')),
            currency: configElement.attr('data-currency'),
            serviceId: parseInt(configElement.attr('data-service-id'), 10),
        };

        widget = new WidgetConnection(widgetConfig);

        widget.startConnection(iframe).then(function () {
            var formSuccessEvent = widgetEvents.formSuccess;
            var validityStatusEvent = widgetEvents.validityStatus;

            widget.on(formSuccessEvent, function (message) {
                chargeCardWidget(message);
            });

            widget.on(validityStatusEvent, function (message, eventData) {
                $('#card-widget-pay-button').prop('disabled', !eventData.valid);
            });
        });
    }

    $(document).on('click', '#card-widget-pay-button', function () {
        if (widget === null) {
            return;
        }

        if (!window.bluepaymentCheckout) {
            widget.sendForm();

            return;
        }

        var payButton = $(this);
        payButton.prop('disabled', true);

        beforePayment().then(function (canPay) {
            payButton.prop('disabled', false);

            if (canPay) {
                widget.sendForm();
            }
        });
    });

    /**
     * Checkout integrations (e.g. ps_onepagecheckout) may define
     * window.bluepaymentCheckout.beforePayment() to save and validate the checkout first.
     */
    function beforePayment() {
        var checkout = window.bluepaymentCheckout;

        // A retry pays for the order already created from this cart, so there is no checkout to save
        if (!checkout || typeof checkout.beforePayment !== 'function' || bmSubmitCardWidgetData.postOrderId) {
            return Promise.resolve(true);
        }

        return Promise.resolve(checkout.beforePayment()).catch(function () {
            return false;
        });
    }

    function chargeCardWidget(token) {
        bmShowLoader();

        var responseMessages = $('#responseCardWidgetMessages');
        var chargeCardWidgetAction = $('#card-widget-url').attr('data-charge-address');

        bmSubmitCardWidgetData.token = token;

        $.ajax(chargeCardWidgetAction, {
            method: 'POST',
            data: bmSubmitCardWidgetData,
            success: function (response) {
                response = JSON.parse(response);

                if (response) {
                    if (response.status === 'PENDING') {
                        if (typeof response.redirectUrl !== 'undefined' && response.redirectUrl.length > 10) {
                            window.location.href = response.redirectUrl;
                        }

                        responseMessages.empty().append($('<b>').text(response.message));
                        responseMessages.show();
                        bmSubmitCardWidgetData.postOrderId = response.postOrderId;

                        clearInterval(cardWidgetVerifyCheck);
                        cardWidgetVerifyCheck = setInterval(function () {
                            chargeCardWidget(token);
                        }, 1250);
                    } else if (response.status === 'SUCCESS') {
                        clearInterval(cardWidgetVerifyCheck);

                        window.location.href = response.backUrl;
                    } else {
                        bmSubmitCardWidgetData.postOrderId = response.postOrderId;
                        clearInterval(cardWidgetVerifyCheck);
                        bmHideLoader();
                        responseMessages.parent('div').addClass('has-error');
                        responseMessages.text(response.message);
                        responseMessages.show();
                        $('#card-widget-pay-button').prop('disabled', false);
                    }
                } else {
                    clearInterval(cardWidgetVerifyCheck);
                    bmHideLoader();
                    responseMessages.parent('div').addClass('has-error');
                    responseMessages.text('Transaction ERROR - Empty data.');
                    responseMessages.show();
                    $('#card-widget-pay-button').prop('disabled', false);
                }
            },
            error: function (data) {
                bmHideLoader();
            }
        });
    }

    function bmShowLoader() {
        $('.bluepayment-loader').fadeIn(400)
        $('.bluepayment-loader-bg').fadeIn(300)
    }

    function bmHideLoader() {
        $('.bluepayment-loader').fadeOut(300)
        $('.bluepayment-loader-bg').fadeOut(300)
    }
});
