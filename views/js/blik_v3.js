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
    setTimeout(function () {
            var bmSubmitBlikData = {};
            var submitter = $('#bluepaymentForm').find('[type="submit"]');
            var conditionsSelector = '#conditions-to-approve';

            function hideBlikErrors() {
                $('[name="bluepayment_input_blikCode"]').parent('div').removeClass('has-error');
                $('#blikAliasError').hide();
                $('#blikCodeError').hide();
                $('#responseMessages').hide();
            }

            function bmShowLoader() {
                $('.bluepayment-loader').fadeIn(400);
                $('.bluepayment-loader-bg').fadeIn(300);
            }

            function bmHideLoader() {
                $('.bluepayment-loader').fadeOut(300);
                $('.bluepayment-loader-bg').fadeOut(300);
            }

            function initBlik() {
                $('#wrongBlikCode').hide();
                $('#bluepaymentForm #bm-termofuse').hide();
                $('#responseMessages').hide();
            }

            initBlik();

            // Checkouts which re-render payment options (ps_onepagecheckout) call it after each refresh
            window.bluepaymentBlik = {
                init: initBlik,
            };

            // Delegated, so the handlers survive payment options re-rendered by the checkout
            $(document).on('submit', '#bluepaymentForm', function () {
                hideBlikErrors();
                var blikAction = $(this).attr('action');
                submitter = $(this).find('[type="submit"]');

                var submitFlag = validateBlikCode();
                var blikCode = $('[name="bluepayment_input_blikCode"]').val();
                if (!submitFlag) {
                    return false
                }

                bmShowLoader();
                submitter.attr('disabled', 'disabled');
                bmSubmitBlikData.blikCode = blikCode;

                beforePayment().then(function (canPay) {
                    if (!canPay) {
                        bmHideLoader();
                        submitter.removeAttr('disabled');

                        return;
                    }

                    sendBlikCode(blikAction);
                });

                return false;
            });

            /**
             * Checkout integrations (e.g. ps_onepagecheckout) may define
             * window.bluepaymentCheckout.beforePayment() to save and validate the checkout first.
             */
            function beforePayment() {
                var checkout = window.bluepaymentCheckout;

                // A retry pays for the order already created from this cart, so there is no checkout to save
                if (!checkout || typeof checkout.beforePayment !== 'function' || bmSubmitBlikData.postOrderId) {
                    return Promise.resolve(true);
                }

                return Promise.resolve(checkout.beforePayment()).catch(function () {
                    return false;
                });
            }

            function sendBlikCode(blikAction) {
                var responseMessages = $('#responseMessages');
                $.ajax(blikAction, {
                    method: 'POST',
                    type: 'POST',
                    data: bmSubmitBlikData,
                    success: function (response) {
                        response = JSON.parse(response);
                        if (response) {
                            if (response.status === 'PENDING') {
                                if (typeof response.redirectUrl !== "undefined" && response.redirectUrl.length > 10) {
                                    window.location.href = response.redirectUrl;
                                }
                                responseMessages.html('<b>' + response.message + '</b>');
                                responseMessages.show();
                                bmSubmitBlikData.postOrderId = response.postOrderId;
                                setTimeout(function () {
                                    sendBlikCode(blikAction);
                                }, 2000);
                            }
                            else if (response.status === 'SUCCESS') {
                                window.location.href = response.backUrl
                            }
                            else if (response.status === 'FAILURE') {
                                let message = typeof response.message !== 'undefined' && response.message.length !== 0
                                    ? response.message
                                    : 'Transaction ERROR - Empty data.';
                                bmSubmitBlikData.postOrderId = response.postOrderId;
                                bmHideLoader()
                                responseMessages.parent('div').addClass('has-error')
                                responseMessages.html('<span class="bm-blik-failure">' + message + '</span>')
                                responseMessages.show()
                                submitter.removeAttr('disabled')
                            } else {
                                bmHideLoader()
                                responseMessages.parent('div').addClass('has-error')
                                responseMessages.html('Transaction ERROR - Empty data.')
                                responseMessages.show()
                                submitter.removeAttr('disabled')
                            }
                        }
                    }
                });
            }

            function validateBlikCode() {
                var show = true;
                var blikCodeInput = $('[name="bluepayment_input_blikCode"]');
                var blikCodeValidation = blikCodeInput.val();
                if (/^[0-9]{6}$/.test(blikCodeValidation)) {
                    blikCodeInput.parent('div').removeClass('has-error');
                    $('#wrongBlikCode').hide();
                    show = true;
                } else {
                    blikCodeInput.parent('div').addClass('has-error');
                    $('#wrongBlikCode').show();
                    show = false;
                }
                $('#bluepaymentForm #bm-termofuse').hide();
                $(conditionsSelector + ' input[type="checkbox"]').each(function (_, checkbox) {
                    if (!checkbox.checked) {
                        blikCodeInput.parent('div').addClass('has-error');
                        $('#bluepaymentForm #bm-termofuse').show();
                        show = false;
                    }
                });

                return show
            }

            function clearValidateBlik() {
                var blikCodeInput = $('[name="bluepayment_input_blikCode"]');

                $('#blikSubmit').removeAttr('disabled').removeClass('disabled');
                $('#wrongBlikCode').hide();
                $('#bluepaymentForm #bm-termofuse').hide();
                $('.bm-blik-failure').hide();
                blikCodeInput.parent('div').removeClass('has-error');

            }

            $(document).on('click', conditionsSelector + ' input[type="checkbox"]', function () {
                clearValidateBlik();
            });

            $(document).on('keyup', '#bluepayment-blikCode', function () {
                clearValidateBlik();
            });
    }, 1000);
});
