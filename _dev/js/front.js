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
 * @copyright      Copyright (c) 2015-2025
 * @license        https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
 */
/* eslint-enable */

import {
	getIdElement,
	getPaymentContainer,
	getPaymentContent, getPaymentTitle,
	hideApplePayOtherBrowser
} from './_partials/helpers';

import {
	extendWalletName,
	getAllWalletAdditionalInformation,
	getSelectorWalletSelection,
	hideAllWalletAdditionalInformation,
	setWalletTypeSelected
} from './_partials/wallet';

import { initSlideshows } from './_partials/slideshow';

import { Modal, openModal } from './_partials/modal';

import { createMainFrame, createPaymentGroup, getAllPaymentsMethodBM } from "./_partials/frame";

import { getClauses, getTransferClauses } from "./_partials/regulations"

import { AllResetState, removeGatewayState, getGatewayState, setGatewayState, ClickResetState } from "./_partials/state"

(function () {

	$(document).ready(function () {
		bindPsdCheckboxValidator();
		bmModalSimple();
		movePaymentFormForCheckout();
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

	function actionValidate(isBmTransfer = false, isResetButton = false) {
		let conditions = $('section.checkout-step #conditions-to-approve input[id="conditions_to_approve[terms-and-conditions]"]');

		if (!validateBmSubmit()) {
			$('div[id=payment-confirmation] button').prop('disabled', true);
		} else {
			if (isBmTransfer && !conditions.length) {
				$('div[id=payment-confirmation] button').removeAttr('disabled');
			} else if (isResetButton) {
				if (!conditions.length) {
					$('div[id=payment-confirmation] button').prop('disabled', true);
				} else {
					changingClauseBehavior(1, 'back');
					changingButtonBehavior(1, 'back');
					conditions.trigger("change");
				}
			} else {
				conditions.trigger("change");
			}
		}

		return false;
	}

	function bindPsdCheckboxValidator() {
		$('div.content div.payment-options input, section.checkout-step #conditions-to-approve input[id="conditions_to_approve[terms-and-conditions]"]')
			.on('click', function () {
				const triggerName = this.getAttribute('name');
				setTimeout(function () {
					actionValidate(triggerName === "bm-transfer-id");
				}, 55);
			});

		$('section.checkout-step #conditions-to-approve input[id="conditions_to_approve[terms-and-conditions]"]').on('click', function () {
			actionValidate();
		});
	}



	function BmAHR() {
		function interceptNetworkRequests(ee) {
			const { open } = XMLHttpRequest.prototype;
			const isRegularXHR = open.toString().indexOf('native code') !== -1;

			if (isRegularXHR) {
				XMLHttpRequest.prototype.open = function () {
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

		function initBluepayment() {
			initBM();
			refreshBM();
		}

	}

	BmAHR();

	function movePaymentFormForCheckout() {
		//TheCheckout module fix
		const targetBody = document.querySelector('#module-thecheckout-order');
		if (!targetBody) return;

		const paymentForm = document.querySelector('#payment-form');
		const confirmButton = document.querySelector('#confirm_order');

		if (paymentForm && confirmButton) {
			confirmButton.parentNode.insertBefore(paymentForm, confirmButton);
		}
	}

	initBM();

	getClauses();



	function initBM() {
		createPaymentGroup();
		createMainFrame(getAllPaymentsMethodBM());
		radioPayments();
		initSlideshows();
		refreshBM();
	}

	function changingBehavior(key, content) {
		const paymentRedirect = content.querySelector('.bm-payment__elm').getAttribute('data-payment-redirect');
		const paymentType = content.getAttribute('data-payment-wallet-type');

		if (paymentRedirect) {
			changingClauseBehavior(key, 'back');
			changingButtonBehavior(key, 'back');
		} else if (!paymentRedirect && paymentType === 'GooglePay') {
			changingButtonBehavior(key, 'hideByClass');
			changingClauseBehavior(key, 'move');
		} else {
			changingClauseBehavior(key, 'back');
			changingButtonBehavior(key, 'back');
		}
	}

	function changingClauseBehavior(key, behavior) {
		const content = getPaymentContent(key);
		const clause = document.querySelector('#conditions-to-approve');

		if (behavior === 'move' && clause !== null) {
			const formGroup = content.querySelector('.form-group');
			if (formGroup !== null) {
				formGroup.append(clause);
			}
		} else if (behavior === 'back' && clause !== null) {
			document.querySelector('.payment-options').after(clause);
		}
	}


	function changingButtonBehavior(key, behavior) {

		if (document.querySelector('#tc-payment-confirmation')) {
			return;
		}

		const content = getPaymentContent(key);
		const btn = document.querySelector('#payment-confirmation');

		let style;
		let className = 'bm-d-none';

		if (behavior === 'move') {
			style = 'block';
			content.append(btn);
		} else if (behavior === 'back') {
			style = 'block';
			if (document.querySelector('#conditions-to-approve') !== null) {
				document.querySelector('#conditions-to-approve').after(btn);
			} else {
				document.querySelector('.payment-options').after(btn);
			}
		} else if (behavior === 'hide') {
			style = 'none';
		}

		setTimeout(function () {
			if (behavior === 'hideByClass') {
				btn.classList.add(className);
			} else {
				btn.style.display = style;
				btn.classList.remove(className);
			}
		}, 150);
	}

	function checkHasModal(key) {
		return (getPaymentContent(key).querySelector("[data-bm-modal]"));
	}

	function modalType(key) {
		return (getPaymentContent(key).querySelector("[data-open-payment]").getAttribute('data-open-payment'));
	}


	function radioPayments() {
		const getAllPaymentOptions = document.querySelectorAll('input[name=payment-option]');

		for (const item of getAllPaymentOptions) {
			item.addEventListener('click', (e) => {

				AllResetState();
				removeGatewayState();

				if (item.id === e.target.id) {
					const id = getIdElement(item);
					const container = getPaymentContainer(id);
					const content = getPaymentContent(id);
					const title = getPaymentTitle(id);

					const paymentElm = content.querySelector('.bm-payment__elm');

					if (paymentElm) {
						const paymentName = paymentElm.getAttribute('data-open-payment');
						const paymentRedirect = paymentElm.getAttribute('data-payment-redirect');

						if (paymentName === 'blik' && !paymentRedirect) {
							changingClauseBehavior(id, 'move');
							changingButtonBehavior(id, 'hide');

						} else if (paymentName === 'wallet') {

						} else {
							changingClauseBehavior(id, 'back');
							changingButtonBehavior(id, 'back');
						}
					} else {
						changingClauseBehavior(id, 'back');
						changingButtonBehavior(id, 'back');
					}
					if (title != null) {
						title.classList.add('active');
					}
				

					/// Opening modal
					if (checkHasModal(id) && modalType(id)) {
						openModal(modalType(id));
						ModalSelectPayment(id, modalType(id));
					} else {
						item.classList.add('active');
						container.classList.add('active');
						content.classList.add('active');
					}
				}
			}, false)
		}
	}




	function bmModalSimple() {
		const getAllModalSimpleHandlers = document.querySelectorAll('[data-bm-modal-siimple]');

		for (const item of getAllModalSimpleHandlers) {
			item.addEventListener('click', (e) => {
				e.preventDefault();

				document.querySelector('[data-payment-desc="' + item.dataset.openModalId + '"]').style.display = "block";

				let myModal = new Modal('#' + item.dataset.openModalId, {
					keyboard: false,
					backdrop: true
				});

				myModal.show();

			}, false)
		}
	}



	function openDescription() {
		document.querySelectorAll('[data-payment-bm="true"][data-toggle=modal]').forEach((element) => {
			element.addEventListener('click', () => {

				AllResetState();

				const elm = element.getAttribute('data-name');

				const paymentName = document.querySelector('[data-payment-name=' + elm + ']');
				const paymentDesc = document.querySelector('[data-payment-desc=' + elm + ']');

				document.querySelectorAll('.js-additional-information').forEach((element1) => {
					element1.classList.remove('active');
					element1.style.display = 'none';
				});
				paymentName.classList.add('active');
				paymentDesc.classList.add('active');
				paymentDesc.style.display = 'block';

			});
		});

		document.querySelectorAll('[data-dismiss=modal]').forEach((element) => {
			element.addEventListener('click', () => {
				const elm = element.getAttribute('data-name');
				document.querySelector('[data-payment-name=' + elm + ']').classList.remove('active');
				document.querySelector('[data-payment-desc=' + elm + ']').classList.remove('active');
				document.querySelector('[data-payment-desc=' + elm + ']').style.display = 'none';
			});
		});

	}

	function createSelectedPaymentWrapElement(item) {
		const name = item.querySelector('.bluepayment-gateways__name').innerText;

		let createSpan = document.createElement("span")
		createSpan.className = 'h6';
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

	function buttonResetPaymentState(key) {
		document.querySelector('.bm-reset').addEventListener('click', e => {
			e.stopPropagation();
			e.preventDefault();

			ClickResetState(key);
			actionValidate(false, true);
		}, false);
	}

	function refreshBM() {
		if ("undefined" != typeof checkoutPaymentParser) {
			checkoutPaymentParser.bluepayment = {
				container: function (a) {
					setTimeout(function () {
						var paymentName;
						var element;
						var n = localStorage.getItem("bm-form-id");
						var r = getPaymentContainer(n);
						var o = getPaymentContent(n);
						if (r && r.id === a[0].id) {
							if (null != (n = getPaymentTitle(n))) {
								n.classList.add("active");
							}
							n = r.querySelector(".bm-selected-payment");
							paymentName = r.getAttribute("data-payment-name");
							r.querySelector("label").style.display = "none";
							r.querySelector("label").classList.add("bm-payment-hide");
							if (n) {
								n.innerHTML = "";
								element = n;
							} else {
								(element = document.createElement("label")).className = "bm-selected-payment";
							}
							n = document.querySelector('[data-bm-gateway-id="' + localStorage.getItem("bm-gateway") + '"]');
							o.style.display = "flex";
							element.append(createSelectedPaymentImgElement(n));
							element.append(createSelectedPaymentWrapElement(n));
							r.appendChild(element);
							if (n) {
								n.style.display = "flex";
							}
							document.querySelector('[data-show-bm-gateway-id="' + localStorage.getItem("bm-gateway") + '"]').style.display = "block";
							if ("transfer" === paymentName) {
								getTransferClauses(n);
							}
							getGatewayState();
							buttonResetPaymentState(5);
						}
					}, 40);
				}
			};
		}
	}

	function ModalSelectPayment(key, item) {

		/// Hide applepay
		hideApplePayOtherBrowser();

		/// Get all payments gateway modal
		var gateways = document.querySelectorAll('.bm-modal-' + item + ' .bluepayment-gateways__item');

		gateways.forEach(item => {
			item.addEventListener('click', () => {

				const container = getPaymentContainer(key);
				const content = getPaymentContent(key);
				const selectedPayment = container.querySelector('.bm-selected-payment');
				const paymentName = container.getAttribute('data-payment-name');
				let createLabel;

				container.querySelector('label').style.display = 'none';
				container.querySelector('label').classList.add('bm-payment-hide');

				if (!selectedPayment) {
					createLabel = document.createElement("label");
					createLabel.className = "bm-selected-payment";
					container.classList.add("bm-selected-payment-test");
					container.classList.add("active");
					content.classList.add("active");
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
					const val = item.querySelector('.bluepayment-gateways__radio').value;
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

					const walletName = backWallet.replace(/\s+/g, '');
					const showWallet = document.querySelector('.show' + walletName);
					const walletElement = document.querySelector('[data-payment-desc=wallet]');

					extendWalletName(
						setWalletTypeSelected,
						getSelectorWalletSelection(walletElement),
						walletName
					)

					changingBehavior(key, content)

					showWallet.style.display = 'block';

				}

				container.appendChild(createLabel);

				document.querySelector('.bm-modal .bm-modal__close').click();
				if (selectedPayment) {
					selectedPayment.style.display = 'flex';
				}

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
