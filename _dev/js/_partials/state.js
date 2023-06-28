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

import {getPaymentContainer, getPaymentTitle, getPaymentContent, getPaymentForm,} from './helpers';

export function ClickResetState(id) {
	const container = getPaymentContainer(id);
	const content = getPaymentContent(id);
	const title = getPaymentTitle(id);

  container.classList.remove('active');
	content.classList.remove('active');

  if (title != null) {
    title.classList.remove('active');
  }

	AllResetState();
  removeGatewayState();
}

export function AllResetState() {
	const state = Array.from(document.querySelectorAll('.payment-option'));

  // eslint-disable-next-line max-len
  0 < document.querySelectorAll(".tc-main-title").length && Array.from(document.querySelectorAll(".tc-main-title")).forEach(function(e) {
    return e.classList.remove("active")
  }), Array.from(document.querySelectorAll(".payment-option, .js-additional-information")).forEach(function(e) {
    return e.classList.remove("active")
  });


	for (const el of state) {
		const removeElm = el.querySelector('.bm-selected-payment');
		const paymentLabel = el.querySelector('.bm-payment-hide')

		/// Remove element
		if (removeElm && removeElm.contains(removeElm)) {
			removeElm.parentNode.removeChild(removeElm);
		}

		/// Use default label
		if (paymentLabel) {
			paymentLabel.classList.remove('bm-payment-hide');
			paymentLabel.style.display = 'flex';
		}
	}

	/// Reset values
	const transfer = document.querySelector('[data-payment-desc=transfer]');
	const wallet = document.querySelector(".bm-wallet-content");
	if(transfer) {
		const id = transfer.id.match(/\d+/g)[0];
		let content = getPaymentForm(id);

		if(content !== null) {
			content.querySelector('input[name="bluepayment_gateway"]').value = 0;
		}
	}

  if (wallet !== void 0 && wallet != null) {
    wallet.style.display = "none";
  }

}

export function removeGatewayState() {
  localStorage.removeItem('bm-form-id');
  localStorage.removeItem('bm-gateway');
}

export function setGatewayState(id, val) {
  removeGatewayState();
	localStorage.setItem('bm-gateway', val);
	localStorage.setItem('bm-form-id', id);
}

export function getGatewayState() {
	const id = localStorage.getItem('bm-form-id');
	const value = localStorage.getItem('bm-gateway');

	if(id !== null && getPaymentForm(id) !== null) {
		const form = getPaymentForm(id);
		let formValue = form.querySelector('input[name=bluepayment_gateway]');
		formValue.value = value;
	}
}
