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
 * @copyright      Copyright (c) 2015-2022
 * @license        https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
 */
/* eslint-enable */

import {
	getIdElement,
	getPaymentContainer,
	getPaymentContent,
	hideApplePayOtherBrowser
} from './_partials/helpers';

import {
	extendWalletName,
	getAllWalletAdditionalInformation,
	getSelectorWalletSelection,
	hideAllWalletAdditionalInformation,
	setWalletTypeSelected
} from './_partials/wallet';

import {initSlideshows} from './_partials/slideshow';

import {openModal} from './_partials/modal';

import {createMainFrame, createPaymentGroup, getAllPaymentsMethodBM} from "./_partials/frame";

import {getClauses, getTransferClauses} from "./_partials/regulations"

import {AllResetState, ClickResetState, getGatewayState, setGatewayState} from "./_partials/state"

(function () {

	$(document).ready(function () {
		bindPsdCheckboxValidator();
	});


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
		return !(psdAcceptInput.is(':visible') //if visible checkbox to validate
			&& !psdAcceptInput.is(':checked')
			&& $('form#bluepayment-gateway').parent().parent().prev().find('input').prop('checked'));


	}

	function actionValidate() {
		let conditions = $('section.checkout-step #conditions-to-approve input[id="conditions_to_approve[terms-and-conditions]"]');

		if (!validateBmSubmit()) {
			$('div[id=payment-confirmation] button').prop('disabled', true);
		} else {
			conditions.trigger("change");
		}
	}

	function bindPsdCheckboxValidator() {
		$('div.content div.payment-options input, section.checkout-step #conditions-to-approve input[id="conditions_to_approve[terms-and-conditions]"]')
			.on('click', function () {
				setTimeout(function () {
					actionValidate();
				}, 55);
			});

		$('section.checkout-step #conditions-to-approve input[id="conditions_to_approve[terms-and-conditions]"]').on('click', function () {
			actionValidate();
		});
	}



	function BmAHR() {
		function interceptNetworkRequests(ee) {
			const {open} = XMLHttpRequest.prototype;
			const isRegularXHR = open.toString().indexOf('native code') !== -1;

			if (isRegularXHR) {
				XMLHttpRequest.prototype.open = function() {
					ee.onOpen && ee.onOpen(this, arguments);
					if (ee.onLoad) {
						this.addEventListener('load', ee.onLoad.bind(ee, arguments));
					}
					if (ee.onError) {
						this.addEventListener('error', ee.onError.bind(ee));
					}
					return open.apply(this, arguments);
				};
			}
			return ee;
		}

		interceptNetworkRequests({
			onLoad: initBluepayment
		});

		function initBluepayment(arg, aa) {
			initBM();
		}

	}

	BmAHR();


	// initBM();



	function initBM() {
		setTimeout(function () {
			createPaymentGroup();
			getGatewayState();
			radioPayments();

			createMainFrame(getAllPaymentsMethodBM());

			initSlideshows();
			getClauses();
		}, 50);
	}



	function changingClauseBehavior(key, behavior) {
		const content = getPaymentContent(key);
		const clause = document.querySelector('#conditions-to-approve');

		if (behavior === 'move') {
			const formGroup = content.querySelector('.form-group');
			if (formGroup !== null) {
				formGroup.append(clause);
			}
		} else if (behavior === 'back') {
			document.querySelector('.payment-options').after(clause);
		}
	}


	function changingButtonBehavior(key, behavior) {

		if(document.querySelector('#tc-payment-confirmation')) {
			return;
		}

		const content = getPaymentContent(key);
		const btn = document.querySelector('#payment-confirmation');


		let style;

		if (behavior === 'move') {
			style = 'block';
			content.append(btn);
		} else if (behavior === 'back') {
			style = 'block';
			document.querySelector('#conditions-to-approve').after(btn);
		} else if (behavior === 'hide') {
			style = 'none';
		}

		setTimeout(function () {
			btn.style.display = style;
		}, 150);
	}


	function buttonResetPaymentState(key) {
		document.querySelector('.bm-reset').addEventListener('click', e => {
			e.stopPropagation();
			e.preventDefault();

			ClickResetState(key);
		}, false);
	}



	function checkHasModal(key) {
		return (getPaymentContent(key).querySelector("[data-bm-modal]"));
	}

	function modalType(key) {
		return (getPaymentContent(key).querySelector("[data-open-payment]").getAttribute('data-open-payment'));
	}


	function radioPayments() {
		const getAllPaymentOptions = document.querySelectorAll('input[name=payment-option]');
		const listitem = document.getElementsByTagName('input')

		for (const item of getAllPaymentOptions) {
			item.addEventListener('click', (e) => {

				AllResetState();

				const id = getIdElement(item);
				const container = getPaymentContainer(id);
				const content = getPaymentContent(id);
				const paymentElm = content.querySelector('.bm-payment__elm');

				let paymentName = paymentElm.getAttribute('data-open-payment');
				let paymentRedirect = paymentElm.getAttribute('data-payment-redirect');

				item.classList.add('active');
				container.classList.add('active');
				content.classList.add('active');


				if (paymentName === 'blik' && !paymentRedirect) {
					changingClauseBehavior(id, 'move');
					changingButtonBehavior(id, 'hide');

				} else if (paymentName === 'wallet') {

				} else {
					changingClauseBehavior(id, 'back');
					changingButtonBehavior(id, 'back');
				}

				/// Opening modal
				if (checkHasModal(id) && modalType(id)) {
					openModal(modalType(id));
					ModalSelectPayment(id, modalType(id));
				}

			}, false)
		}
	}


	function createSelectedPaymentWrapElement(item) {
		const name = item.querySelector('.bluepayment-gateways__name').innerText;

		let createSpan = document.createElement("span")
		createSpan.innerHTML = name;

		let createPaymentWrap = document.createElement("div")
		createPaymentWrap.className = 'bm-payment-wrap';

		let createPaymentName = document.createElement("div")
		createPaymentName.className = 'bm-payment-name';

		let createReset = document.createElement("a")
		createReset.href = "#";
		createReset.className = "bm-reset";
		createReset.innerHTML = change_payment;

		createPaymentName.append(createSpan);
		createPaymentName.append(createReset);
		createPaymentWrap.append(createPaymentName);

		return createPaymentWrap;
	}


	function createSelectedPaymentImgElement(item) {
		const img = item.querySelector('.bluepayment-gateways__img').src;
		let createImg = document.createElement("img");
		createImg.src = img;

		return createImg;
	}


	function ModalSelectPayment(key, item) {

		/// Hide applepay
		hideApplePayOtherBrowser();

		/// Get all payments gateway modal
		var gateways = document.querySelectorAll('.bm-modal-' + item + ' .bluepayment-gateways__item');

		gateways.forEach(item => {
			item.addEventListener('click', e => {

				const container = getPaymentContainer(key);
				const content = getPaymentContent(key);
				const selectedPayment = container.querySelector('.bm-selected-payment');
				const paymentName = container.getAttribute('data-payment-name');
				let createLabel;

				container.querySelector('label').style.display = 'none';
				container.querySelector('label').classList.add('bm-payment-hide');

				if (!selectedPayment) {
					createLabel = document.createElement("label")
					createLabel.className = "bm-selected-payment";
				} else {
					selectedPayment.innerHTML = '';
					createLabel = selectedPayment;
				}

				createLabel.append(
					createSelectedPaymentImgElement(item)
				);
				createLabel.append(
					createSelectedPaymentWrapElement(item)
				);

				//// Ustawienie wartosci
				if (paymentName === 'transfer' || paymentName === 'wallet') {
					item.querySelector('.bluepayment-gateways__radio').checked = true;
					let val = item.querySelector('.bluepayment-gateways__radio').value;

					setGatewayState(key, val);
					getGatewayState();
				}

				/// Uruchamianie płatności dla GooglePay, ApplePay
				const backWallet = item.getAttribute('data-bm-wallet-name')

				if (backWallet && paymentName === 'wallet') {

					/// Ukrywanie elementów
					hideAllWalletAdditionalInformation(
						getAllWalletAdditionalInformation()
					);

					let walletName = backWallet.replace(/\s+/g, '');
					let showWallet = document.querySelector('.show' + walletName);
					let walletElement = document.querySelector('[data-payment-desc=wallet]');

					extendWalletName(
						setWalletTypeSelected,
						getSelectorWalletSelection(walletElement),
						walletName
					)

					let paymentRedirect = content.querySelector('.bm-payment__elm')
						.getAttribute('data-payment-redirect');
					let paymentType = content.getAttribute('data-payment-wallet-type');


					if (paymentRedirect && (paymentType === 'ApplePay' || paymentType === 'GooglePay')) {
						changingClauseBehavior(key, 'back');
						changingButtonBehavior(key, 'back');
					} else if (!paymentRedirect && paymentType === 'GooglePay') {
						changingButtonBehavior(key, 'hide');
						changingClauseBehavior(key, 'move');
					} else {
						changingClauseBehavior(key, 'back');
						changingButtonBehavior(key, 'back');
					}

					showWallet.style.display = 'block';

				}

				container.appendChild(createLabel);

				document.querySelector('.bm-modal .bm-modal__close').click();
				document.querySelector('.bm-selected-payment').style.display = 'flex';

				/// Set regulations
				if (paymentName === 'transfer') {
					getTransferClauses(item);
				}

				buttonResetPaymentState(key);

				return false;
			}, false);
		});
	}
})();
