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

export function getClauses() {
	if (typeof regulations_get_url === 'undefined') {
		return;
	}

	/// Reset all regulations
	localStorage.removeItem('bm-regulations');

	const url = get_regulations_url.replace(/&amp;/g, '&');

	/// Request get regulations
	let request = new XMLHttpRequest();
	request.open('POST', url, true);
	request.onload = function () {
		if (this.status >= 200 && this.status < 400) {
			let resp = this.response;

			localStorage.setItem('bm-regulations', resp);
			// resp = JSON.parse(resp);

			// if (resp.length === 0 || typeof resp == 'undefined') {
			// 	console.log('no')
			// }

		} else {
			console.log('error');
		}
	};
	request.send();
}


export function truncateClause() {
	const textHolder = document.querySelector('.bm-small-info .bm-pis');
	const fullText = textHolder.innerHTML;
	let btn = document.querySelector('.bm-read-more');
	btn.style.display = 'block';
	let textStatus = 'full';

	function showMoreLegalContent(textHolder, limit) {
		let txt = textHolder.innerHTML;
		if (txt.length > limit) {
			textHolder.innerHTML = txt.substring(0, limit) + ' ...';
			textStatus = 'truncated';
		}
	}

	showMoreLegalContent(textHolder, 314);

	function toggleLegal(e) {
		e.preventDefault();

		if (textStatus === 'truncated') {
			textHolder.innerHTML = fullText;
			textStatus = 'full';
			btn.style.display = 'none';
		} else {
			showMoreLegalContent(textHolder, 120);
		}
	}

	btn.addEventListener('click', toggleLegal);
}

/// Show payment regulation
export function showPsdClause(regulation) {
	if (regulation) {
		const clause = document.querySelector('.bm-clause-ajax');
		clause.querySelector('.bm-pis').innerHTML = regulation.inputLabel;
		clause.style.display = 'block';

		truncateClause();
	}
}

export function getTransferClauses(elm) {

	resetTransferClauses();

	let regulations = localStorage.getItem('bm-regulations');
	let selected = elm.querySelector('input[name="bm-transfer-id"]').value;

	if (regulations) {
		regulations = JSON.parse(regulations);

		const regulation = regulations.filter(
			item => item.gatewayID === selected && item.language === 'PL'
		);

		if(regulation) {
			return showPsdClause(regulation[0]);
		}

		return false;

	}
}

export function resetTransferClauses() {
	const clause = document.querySelector('.bm-clause-ajax');
	clause.querySelector('.text').innerHTML = '';
	clause.style.display = 'none';
}
