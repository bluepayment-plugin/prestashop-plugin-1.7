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
 * @copyright      Copyright (c) 2015-2021
 * @license        https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
 */

$(document).ready(function () {
    localStorage.removeItem('bluepayment-regulationsGet');
    regulationsGet();

    $('#bluepayment-gateway #blue_payway input[name ="bluepayment-gateway-gateway-id"]').change(function() {

        $('input[name="bluepayment-hidden-psd2-regulation-id"]').val('');

        var regulations = localStorage.getItem('bluepayment-regulationsGet');
        regulations = JSON.parse(regulations);

        var selectdGatewayId = this.value;
        var length = regulations.length;

        for (var i = 0; i < length; i++) {
            if (regulations[i].gatewayID == selectdGatewayId) {
                showPSD2Clause(selectdGatewayId, regulations[i]);
                return;
            }
        }

        showNoGatewayInGetRegulations();
    });

    $('input:radio[name=bluepayment-gateway-gateway-id]').change(function () {
        var gatewayId = this.value;
        var button = $('div[id=payment-confirmation]').find("button");

        $('input[name=bluepayment_gateway]').val(gatewayId);

        if (gatewayId >= 1800) {
            button.text(start_payment_translation);
        } else {
            button.text(order_subject_to_payment_obligation_translation);
        }
    });

    $('input:radio[name=payment-option]').change(function () {
        var base_element = $(this).parent().parent().parent().next();
        var blueMediaGateways = base_element.find('#bluepayment-gateway').length;
        var bluemedia_payment_id = base_element.find('input[name=bluepayment_gateway_id]').val();

            if (typeof bluemedia_payment_id !== 'undefined') {
                $('input[name=bluepayment_gateway]').val(bluemedia_payment_id);
            }

            if (blueMediaGateways) {
                $('.bluepayment-pis').show()
            } else {
                $('.bluepayment-pis').hide()
            }
        }
    );

    bindPsdCheckboxValidator();
});

/**
 * pokazujemy odpowiednia klauzule o ile zostala znaleziona w regulationsGet
 *
 * @param gatewayId
 * @param regulation
 */
function showPSD2Clause(gatewayId, regulation) {
    //brak URL (regulaminu) oznacza ze jest to model merchanta i nie wymaga dodatkowego checkboxa

    //sprawdzamy czy jest to model merchanta
    if (typeof regulation.url == 'undefined' || regulation.url === '') {
        clearForMerchantState();
        $('.ajax-psd2-clause .text').html(regulation.inputLabel);
        $('.ajax-psd2-clause').show();
        //ajax-psd2-clause-merchant
        // input hidden merchant model approved checked
    } else {//model platnika
        clearForPayerState();
        $('.ajax-psd2-clause .text').html(regulation.inputLabel);
        $('.ajax-psd2-clause').show();
    }

    $('input[name="bluepayment-hidden-psd2-regulation-id"]').val(regulation.regulationID);

    // if (
    //     regulation.inputLabel
    //     && regulation.url == '' //to jest to
    //     // && regulation.inputLabel.indexOf('<a href="https://www.knf.gov.pl') !== -1
    // ) {
    //     $('.ajax-psd2-clause .text').html(regulation.inputLabel);
    //     $('.ajax-psd2-clause').show();
    //     // $('#bluepayment-gateway .bluepayment-agent-info').hide();
    // }
}

function regulationsGet() {
    if (typeof regulations_get_url === 'undefined') {
        return;
    }

    var url = regulations_get_url.replace(/&amp;/g, '&');

    $.ajax(url, {
        method: "POST",
        type: "POST",
        data: {},
        success: function (data) {
            localStorage.setItem('bluepayment-regulationsGet', data);

            data = JSON.parse(data);

            //this clause is not related with other clauses, shows only if we have at least one PSD2 label from regulationsGet
            if (data.length === 0 || typeof data == 'undefined') {
                $('#bluepayment-gateway .bluepayment-agent-info').hide()
            }
        }
    });
}

function hideAllBottomClauses() {
    $('.ajax-psd2-clause .text').html('');
    $('.ajax-psd2-clause').hide();
    $('.ajax-psd2-clause-merchant .text').html('');
    $('.ajax-psd2-clause-merchant').hide();
}

function clearForMerchantState() {
    hideAllBottomClauses();
    $('.bluepayment-agent-info-bottom input[name ="bluepayment-psd2-accepted-behalf-user"]').prop('checked', true);
    $('input #bluepayment-psd2-accepted').prop('checked', false);
}

function clearForPayerState() {
    hideAllBottomClauses();
    $('.bluepayment-agent-info-bottom input[name ="bluepayment-psd2-accepted-behalf-user"]').prop('checked', false);
}

function showNoGatewayInGetRegulations() {
    $('.ajax-psd2-clause .text').html('');
    $('.ajax-psd2-clause').hide();
    $('#bluepayment-gateway .bluepayment-agent-info').show()
}

/**
 * check if we need to block submit button
 * @returns {boolean}
 */
function validateBmSubmit() {
    var psdAcceptInput = $('#bluepayment-psd2-accepted');

    if (!psdAcceptInput.is(':visible')) {
        return true;
    }

    //if visible checkbox to validate
    if (
        psdAcceptInput.is(':visible') //if visible checkbox to validate
        && !psdAcceptInput.is(':checked')
        && $('form#bluepayment-gateway').parent().parent().prev().find('input').prop('checked')  //if selected bluemedia payment
    ) {
        return false;
    }

    return true;
}

function actionValidate() {
    var termsAndConditionsCheckbox = $('section.checkout-step #conditions-to-approve input[id="conditions_to_approve[terms-and-conditions]"]');

    if (!validateBmSubmit()) {
        $('div[id=payment-confirmation] button').prop('disabled', true);
    } else {
        termsAndConditionsCheckbox.trigger("change");
    }
}

function bindPsdCheckboxValidator() {
    $('div.content div.payment-options input, section.checkout-step #conditions-to-approve input[id="conditions_to_approve[terms-and-conditions]"]').on('click', function() {
        setTimeout(function() {
            actionValidate();
        },55);
    });

    $('section.checkout-step #conditions-to-approve input[id="conditions_to_approve[terms-and-conditions]"]').on('click', function() {
        actionValidate();
    });
}
