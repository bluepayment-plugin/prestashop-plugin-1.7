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

import {getPaymentContainer, getPaymentContent, getPaymentForm,} from './helpers';

export function ClickResetState(id) {
	const title = getPaymentContainer(id);
	const content = getPaymentContent(id);

	title.classList.remove('active');
	content.classList.remove('active');
	content.style.display = 'none';

	AllResetState();
}

export function AllResetState() {
	const state = Array.from(document.querySelectorAll('.payment-option'));
	for (const el of state) {
		const removeElm = el.querySelector('.bm-selected-payment');
		const paymentLabel = el.querySelector('.bm-payment-hide')

		el.classList.remove('active');

		/// Remove element
		if (removeElm) {
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
	if(transfer) {
		const id = transfer.id.match(/\d+/g)[0];
		let content = getPaymentForm(id);

		if(content !== null) {
			content.querySelector('input[name="bluepayment_gateway"]').value = 0;
		}
	}

}

export function setGatewayState(id, value) {
	/// Reset all gateways
	localStorage.removeItem('bm-gateway');
	localStorage.removeItem('bm-form-id');

	localStorage.setItem('bm-gateway', value);
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
