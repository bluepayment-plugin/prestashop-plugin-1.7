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

export let Modal = function (el, options) {
	var self = this;

	this.el = document.querySelector(el);
	this.options = options;

	try {
		var list = document.querySelectorAll('[data-dismiss="bm-modal"]');
		for (var x = 0; x < list.length; x++) {
			list[x].addEventListener('click', function (e) {
				if (e) e.preventDefault();
				self.hide();
			});
		}
	} catch (e) {
		console.log(e);
	}
};
Modal.prototype.show = function () {

	var self = this;
	// adding backdrop (transparent background) behind the modal
	if (self.options.backdrop) {
		var backdrop = document.getElementById('bs.backdrop');
		if (backdrop === null) {
			backdrop = document.createElement('div');
			backdrop.id = "bs.backdrop";
			backdrop.className = "bm-modal-backdrop bm-fade";
			document.body.appendChild(backdrop);
			backdrop.classList.add('bm-in');
		}

	}

	// show modal
	this.el.classList.add('bm-in');
	// this.el.style.display = 'block';
	document.body.style.paddingRight = '13px';
	document.body.classList.add('bm-modal-open');
};
Modal.prototype.hide = function () {
	var self = this;
	// hide modal
	this.el.classList.remove('bm-in');
	// this.el.style.display = 'none';
	document.body.style = '';
	document.body.classList.remove('bm-modal-open');

	// removing backdrop
	if (self.options.backdrop) {
		var backdrop = document.getElementById('bs.backdrop');
		if (backdrop !== null) document.body.removeChild(backdrop);
	}
};


export function openModal(item) {
	let button = document.querySelector('#payment-confirmation button');
	let myModal = new Modal('.bm-modal-' + item, {
		keyboard: false,
		backdrop: true
	});

	myModal.show();

	if(button) {
		setTimeout(function () {
			button.setAttribute("disabled", "disabled");
		}, 60);
	}
}


