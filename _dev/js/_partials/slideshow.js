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

export function createSlideShow() {

	const slideshows = document.querySelectorAll('.bm-slideshow');

	for (const el of slideshows) {

		const parent = el.dataset.slideshow;
		const elmm = document.querySelector('[data-payment-name='+parent+']');
		const slideShowClass = '.bm-' + parent + '-slideshow';
		const slideShow = document.querySelector(slideShowClass);


		if(!elmm.querySelector('label > .bm-slideshow')) {

			const img = elmm.querySelector('label > img');
			if (img) {
				img.remove();
			}

			const label = elmm.querySelector('label');
			label.append(slideShow);
			slideShow.classList.remove('bm-hide');

			const slider = new Slideshow(slideShowClass);

		}
	}

}

export function initSlideshows() {
	createSlideShow();
}

function Slideshow( element ) {
	this.el = document.querySelector( element );
	this.init();
}

Slideshow.prototype = {
	init: function() {
		this.wrapper = this.el.querySelector( ".bm-slideshow" );
		this.slides = this.el.querySelectorAll( ".slide" );
		this.previous = this.el.querySelector( ".slider-previous" );
		this.next = this.el.querySelector( ".slider-next" );
		this.index = 0;
		this.total = this.slides.length;
		this.timer = null;

		this.action();
	},
	_slideTo: function( slide ) {
		var currentSlide = this.slides[slide];
		currentSlide.style.opacity = 1;

		for( var i = 0; i < this.slides.length; i++ ) {
			var slide = this.slides[i];
			if( slide !== currentSlide ) {
				slide.style.opacity = 0;
			}
		}
	},
	action: function() {
		var self = this;
		self.timer = setInterval(function() {
			self.index++;
			if( self.index == self.slides.length ) {
				self.index = 0;
			}
			self._slideTo( self.index );

		}, 3000);
	},
};


