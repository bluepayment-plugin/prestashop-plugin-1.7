/* eslint-disable */
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
/* eslint-enable */

/**
 * Adapter for the native PrestaShop One Page Checkout (ps_onepagecheckout, PrestaShop 9.2+).
 *
 * OPC renders payment options via AJAX and replaces them after address / country / carrier
 * changes, so the Autopay scripts are re-initialised on its events. Inline methods (BLIK, card
 * widget, Google Pay) save and validate the checkout through OPC before charging.
 */
import { getIdElement, getPaymentContainer, getPaymentContent, getPaymentTitle } from '../_partials/helpers';

const MODULE_NAME = 'bluepayment';
const READY_TIMEOUT = 10000;
const SELECTORS = {
	paymentMethods: '#opc-payment-methods',
	checkedPaymentOption: '#opc-payment-methods input[name="payment-option"]:checked',
	payButton: '#opc-pay-button',
	requiredConditions: 'input[name^="conditions_to_approve"][required]',
};
const OPC_EVENTS = {
	formValidated: 'opcFormValidated',
	paymentMethodsUpdated: 'opcPaymentMethodsUpdated',
	paymentMethodsRefreshed: 'opcPaymentMethodsRefreshed',
};

function isOnePageCheckout() {
	return typeof prestashop !== 'undefined'
		&& typeof prestashop.on === 'function'
		&& document.querySelector(SELECTORS.paymentMethods) !== null;
}

function initPaymentScripts() {
	['bluepaymentFront', 'bluepaymentBlik', 'bluepaymentCard', 'bluepaymentGpay'].forEach((name) => {
		if (window[name] && typeof window[name].init === 'function') {
			window[name].init();
		}
	});
}

/**
 * After re-rendering, OPC restores the checked radio but the Autopay classes / selected gateway
 * label are gone; restore them without re-opening the gateway modal.
 */
function restoreSelectedOption() {
	const radio = document.querySelector(SELECTORS.checkedPaymentOption);

	if (!radio || radio.dataset.moduleName !== MODULE_NAME) {
		return;
	}

	const id = getIdElement(radio);

	if (localStorage.getItem('bm-form-id') === id && window.bluepaymentFront) {
		window.bluepaymentFront.restoreState();

		return;
	}

	[radio, getPaymentContainer(id), getPaymentContent(id), getPaymentTitle(id)].forEach((element) => {
		if (element) {
			element.classList.add('active');
		}
	});
}

function refresh() {
	initPaymentScripts();
	restoreSelectedOption();
}

function removeOpcListener(event, handler) {
	if (typeof prestashop.removeListener === 'function') {
		prestashop.removeListener(event, handler);
	}
}

/**
 * OPC disables its pay button while it refreshes addresses, carriers or payment methods and while
 * it submits; submitBeforePayment() then returns without saving anything. Unchecked required
 * conditions disable it too, but those are reported by the submit itself.
 */
function isOpcBusy() {
	const payButton = document.querySelector(SELECTORS.payButton);
	const conditionsAccepted = Array.from(document.querySelectorAll(SELECTORS.requiredConditions))
		.every((checkbox) => checkbox.checked);

	return payButton instanceof HTMLButtonElement && payButton.disabled && conditionsAccepted;
}

function waitUntilOpcReady() {
	if (!isOpcBusy()) {
		return Promise.resolve(true);
	}

	return new Promise((resolve) => {
		let timeout = null;
		const finish = (ready) => {
			clearTimeout(timeout);
			removeOpcListener(OPC_EVENTS.formValidated, onValidated);
			resolve(ready);
		};
		function onValidated() {
			if (!isOpcBusy()) {
				finish(true);
			}
		}

		timeout = setTimeout(() => finish(false), READY_TIMEOUT);
		prestashop.on(OPC_EVENTS.formValidated, onValidated);
	});
}

/**
 * submitBeforePayment() resolves the same way whether the checkout was saved, rejected, reloaded
 * or never submitted, so the opcSubmit response is read from the request OPC sends.
 */
function captureSubmitResponse(submitUrl) {
	const originalFetch = window.fetch;
	let response = Promise.resolve(null);

	window.fetch = function (input, ...args) {
		const request = originalFetch.call(this, input, ...args);

		if (submitUrl && String(input instanceof Request ? input.url : input) === submitUrl) {
			response = request.then((raw) => raw.clone().json()).catch(() => null);
		}

		return request;
	};

	return {
		response: () => response,
		restore: () => {
			window.fetch = originalFetch;
		},
	};
}

function beforePayment() {
	const opc = window.ps_onepagecheckout;

	if (!opc || typeof opc.submitBeforePayment !== 'function') {
		return Promise.resolve(true);
	}

	return waitUntilOpcReady().then((ready) => {
		if (!ready) {
			return false;
		}

		const submit = captureSubmitResponse(opc.urls && opc.urls.opcSubmit);

		return Promise.resolve()
			.then(() => opc.submitBeforePayment())
			.catch(() => null)
			.then(() => submit.response())
			.then((response) => Boolean(response && response.success === true && !response.reload))
			.finally(submit.restore);
	});
}

(function () {
	if (!isOnePageCheckout()) {
		return;
	}

	window.bluepaymentCheckout = {
		name: 'ps_onepagecheckout',
		beforePayment,
	};

	prestashop.on(OPC_EVENTS.paymentMethodsUpdated, refresh);
	prestashop.on(OPC_EVENTS.paymentMethodsRefreshed, refresh);

	// Payment options may already be rendered when this script runs
	if (document.querySelector(`${SELECTORS.paymentMethods} input[name="payment-option"]`)) {
		refresh();
	}
})();
