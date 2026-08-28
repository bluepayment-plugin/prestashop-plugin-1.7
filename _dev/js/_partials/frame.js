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

import {getIdElement, getPaymentContainer, getPaymentContent} from './helpers';

export const getFirstElementFrame = (data) => data.map((item, index) => index === 0);

export function getAllPaymentsMethodBM(typeClass) {
	const elements = document.querySelectorAll('.js-additional-information');
	const array = Array.from(elements);
	const setType = typeClass ? typeClass : 'bm-payment__elm';

	return array.filter(element => Array.from(element.children)
		.some(item => item.classList.contains(setType)));
}

function isNestedPaymentStructure(index) {
	const container = getPaymentContainer(index);
	const content = getPaymentContent(index);

	return !!(container && content && container !== content && container.contains(content));
}

export function createMainFrame(arr) {
	arr.forEach(( item, i ) => {
		if (0 === i) {
			const content = getPaymentContent(getIdElement(item));
			const elm = content.querySelector(".bm-payment__elm");
			BeginWrapPayments(elm, getIdElement(item))
		} else if (arr.length - 1 === i) {
			const content = getPaymentContent(getIdElement(item));
			const elm = content.querySelector(".bm-payment__elm");
			EndWrapPayments(elm, getIdElement(item))
		}
	});
}


function BeginWrapPayments(elm, index) {

	if (!document.querySelector(".bm-frame-start")) {
		let element;
		const div = document.createElement('div');
		div.className = 'bm-frame-start';

		const nested = !checkIfOpcGroup() && isNestedPaymentStructure(index);

		if (checkIfOpcGroup()) {
			element = document.querySelector('#payment-option-' + index + '-main-title');
		} else if (nested) {
			element = getPaymentContainer(index);
		} else {
			element = elm && elm.parentNode ? elm.parentNode.previousElementSibling : null;
		}

		if (!element || (nested && !element.parentNode)) {
			return;
		}

		const img = document.createElement('img')
		img.src = asset_path + 'img/blue-media.svg';
		img.width = 80;

		const brand = document.createElement('img')
		brand.className = 'bm-safe-brands';
		brand.src = asset_path + 'img/safe-brands.png';
		brand.width = 220;

		div.append(img);
		div.append(brand);


		if (checkIfOpcGroup()) {
			element.parentNode.insertBefore(div, element.previousSibling);
		} else if (nested) {
			element.parentNode.insertBefore(div, element);
		} else {
			element.prepend(div)
		}
	}
}


function EndWrapPayments(elm, index) {
	// const legals = document.querySelector('#bm-end');

	if (!document.querySelector(".bm-frame-end")) {
		const div = document.createElement('div');
		div.id = 'bm-methods-grouped';
		div.className = 'bm-frame-end';
		let element;

		if (checkIfOpcGroup()) {
			element = document.querySelector('#payment-option-' + index + '-main-title');
		} else if (isNestedPaymentStructure(index)) {
			element = getPaymentContainer(index);
		} else {
			element = elm ? elm.parentNode : null;
		}

		if (!element || !element.parentNode) {
			return;
		}

		element.parentNode.insertBefore(div, element.nextSibling);
	}
}

function checkIfOpcGroup() {
	// Hummingbird uses BEM classes (.payment-options__list / .payment__list) instead of .payment-options
	const list = document.querySelector('.payment-options, .payment-options__list, .payment__list');
	if (!list) {
		return false;
	}
	return Array.from(list.children).some(e => e.dataset.paymentModule === 'bluepayment')
}

export function createPaymentGroup() {

	function markPaymentBm(arr) {
		for (const el of arr) {

			const container = getPaymentContainer(getIdElement(el));
			const content = getPaymentContent(getIdElement(el));
			const paymentName = el.children ? el.children[0].getAttribute('data-open-payment') : null;

			if(paymentName) {
				container.setAttribute('data-payment-name', paymentName);
				content.setAttribute('data-payment-desc', paymentName);
			}

			container.setAttribute('data-payment-bm', 'true');
			content.setAttribute('data-payment-bm', 'true');
		}
	}

	markPaymentBm(getAllPaymentsMethodBM());


	getAllPaymentsMethodBM('bm-payment__promo').map((item) => {
		const label = item.querySelector('.bm-promo-label');
		const container = getPaymentContainer(getIdElement(item));
		if(label !== null) {
			container.appendChild(label);
		}

		const desc = item.querySelector('.bm-promo-desc');
		const descTarget = container.querySelector('label span') || container.querySelector('label');
		if(desc !== null && descTarget !== null) {
			descTarget.appendChild(desc);
		}

		return false;
	});

	removeMarginAllAdditionalInformation(
		getAllAdditionalsInformation()
	);

}


export function getAllAdditionalsInformation() {
	return document.querySelectorAll('.js-additional-information');
}

export function removeMarginAllAdditionalInformation(elements) {
	[...elements].map(item => {
		item.parentElement.style.marginBottom = "0";
	});
}
