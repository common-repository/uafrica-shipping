// check if something needs to be done?

import './uafrica-shipping.scss';

const uAfricaShipping = class {
	/**
	 * @param {Element} containerEle
	 */
	constructor( containerEle ) {
		this.containerEle = containerEle;
		this.formEle = this.containerEle.querySelector( 'form' );
		this.submitBttn = this.formEle.querySelector( 'button' );
		this.validationEle = this.containerEle.querySelector( '.validation' );
		this.stepFormEle = this.containerEle.querySelector(
			'.shipping-step-form'
		);
		this.stepStatusEle = this.containerEle.querySelector(
			'.shipping-step-status'
		);

		this.abortController = new AbortController();

		this.getOrderNumberQueryParam();
		this.handleFormSubmit();

		// do we need to process the order on load?
		if ( '' !== this.orderNR && null !== this.orderNR ) {
			this.fetchOrderData(); // if not empty, try on page load.
		}
	}

	handleFormSubmit() {
		this.formEle.addEventListener( 'submit', ( event ) => {
			event.preventDefault();
			this.orderNR = this.formEle.querySelector(
				'input[name=order-number]'
			).value;

			if ( history.pushState ) {
				const newUrl =
					window.location.protocol +
					'//' +
					window.location.host +
					window.location.pathname +
					'?order-number=' +
					this.orderNR;
				window.history.pushState( { path: newUrl }, '', newUrl );
			}

			this.fetchOrderData();
		} );
	}

	getOrderNumberQueryParam() {
		const urlParams = new URLSearchParams( window.location.search );
		const orderNR = urlParams.get( 'order-number' );
		this.orderNR = orderNR;

		return orderNR;
	}

	fetchOrderData() {
		// Prevent double runs.
		if ( this.fetchRunning ) {
			this.abortController.abort();
			this.abortController = new AbortController();
		} else {
			this.fetchRunning = true;
		}

		this.submitBttn.disabled = true;
		this.validationEle.classList.add( 'hidden' ); // always hide the error when fetching new data.
		const url = uafrica_shipping_l10n.v3_api_url
			.replace('NUMBER', this.orderNR)
			.replace('DOMAIN', uafrica_shipping_l10n.domain);

		fetch(url, {signal: this.abortController.signal})
			.then((response) => response.json())
			.then((data) => {
				if ('undefined' === typeof data[0]) {
					this.displayError();
				} else {
					this.shipping_details = data[0];
					this.displayOrderData();
				}
			})
			.catch((err) => {
				if (20 !== err.code) {
					// code 20 is an abort, which is not a problem.
					console.log(err);
					this.displayError();
				}
			})
			.finally(() => {
				this.fetchRunning = false;
				this.submitBttn.disabled = false;
			});
	}

	displayOrderData() {
		this.stepFormEle.classList.add( 'hidden' );
		this.stepStatusEle.classList.remove( 'hidden' );

		const classThis = this; // allow this class to be called in other functions.

		// set flat data.
		this._setValue(
			this.stepStatusEle,
			'status',
			this.shipping_details.status_friendly
		);
		this._setValue(
			this.stepStatusEle,
			'custom_order_name',
			this.shipping_details.custom_order_name
		);
		this._setValue(
			this.stepStatusEle,
			'courier_name',
			this.shipping_details.courier_name
		);
		this._setValue(
			this.stepStatusEle,
			'courier_phone',
			this.shipping_details.courier_phone
		);
		this._setValue(
			this.stepStatusEle,
			'id',
			this.shipping_details.id
		);

		if(!this.shipping_details.show_branding)
		{
			document.getElementById('show_branding').style.display = 'none'
		}

		if (this.shipping_details.status === "pending-collection")
		{

			document.getElementById('table_checkpoints').style.display = 'none'
			this._setValue(
				this.stepStatusEle,
				'delivery_heading',
				'Tracking details'
			);
			this._setValue(
				this.stepStatusEle,
				'delivery_message',
				'Your shipment will be collected soon. Please check back later for more information.'
			);
		}
		else if(this.shipping_details.status === 'cancelled-by-courier')
		{
			this._setValue(
				this.stepStatusEle,
				'delivery_heading',
				'Tracking details'
			);
			this._setValue(
				this.stepStatusEle,
				'delivery_message',
				'The shipment has been cancelled.'
			);
			document.getElementById('table_checkpoints').style.display = 'none'
		}
		else if(this.shipping_details.status === 'cancelled')
		{
			this._setValue(
				this.stepStatusEle,
				'delivery_heading',
				'Tracking details'
			);
			this._setValue(
				this.stepStatusEle,
				'delivery_message',
				'The shipment has been cancelled.'
			);
			document.getElementById('table_checkpoints').style.display = 'none'
		}
	else
		{
			if (this.shipping_details.checkpoints.length <= 0) {
				document.getElementById('table_checkpoints').style.display = 'none'
				this._setValue(
					this.stepStatusEle,
					'delivery_heading',
					'Tracking details'
				);
				this._setValue(
					this.stepStatusEle,
					'delivery_message',
					'Tracking information is not yet available. Please check back later for more information.'
				);
				return
			}

			let fulldate = new Date(this.shipping_details.checkpoints
				[0].time)
			 let builtDate = fulldate.toLocaleString('default',{weekday: 'long'}) + ', ' +
				 fulldate.getDate() + ' ' +  fulldate.toLocaleString('default', {month: 'short'}) + ' ' + fulldate.getFullYear();
			this._setValue(
				this.stepStatusEle,
				'time',
				builtDate
			)
			document.getElementById('table_else').style.display = 'none'

			this.shipping_details.checkpoints.forEach( function( checkpoint) {
				// Create a new row
				let tableRow = document.getElementById('delivery_steps').appendChild(document.createElement('tr'));

				// Date and time
				let	time_cell = document.createElement('td');
				let timeDate = new Date(checkpoint.time);
				time_cell.style.width = '25%';
				let hours = timeDate.getHours() < 10 ? '0'+timeDate.getHours() : timeDate.getHours();
				let minutes = timeDate.getMinutes() < 10 ? '0'+timeDate.getMinutes() : timeDate.getMinutes();
				time_cell.innerHTML = timeDate.getFullYear() + ' ' + timeDate.toLocaleString('default', {month: 'short'}) + ' ' +  timeDate.getDate() +', ' + hours + ':' + minutes;

				// Friendly status
				let	status_cell = document.createElement('td');
				status_cell.innerHTML = checkpoint.status_friendly;
				status_cell.style.fontWeight = 'bold';
				status_cell.style.width = '25%';

				// Message
				let	message_cell = document.createElement('td');
				message_cell.innerHTML = checkpoint.message;

				// Add the data to the row
				tableRow.appendChild(time_cell);
				tableRow.appendChild(status_cell);
				tableRow.appendChild(message_cell);
			})
		}


		const checkpointTemplate = this.stepStatusEle.querySelector(
			'[data-uafrica=checkpoints-template] .step'
		);
		const checkpointsEle = this.stepStatusEle.querySelector(
			'.checkpoints'
		);
	}

	displayError() {
		this.validationEle.classList.remove( 'hidden' );
		this.validationEle.textContent = uafrica_shipping_l10n.not_found.replace(
			'%s',
			this.orderNR
		);
	}

	/**
	 *
	 * @param {Element} parentEle
	 * @param {string }dataAttribute
	 * @param {string} value
	 * @private
	 */
	_setValue( parentEle, dataAttribute, value ) {
		const targetEleS = parentEle.querySelectorAll(
			'[data-uafrica=' + dataAttribute + ']'
		);
		if ( 0 === targetEleS.length ) {
			return; // empty list, no need to loop.
		}

		targetEleS.forEach( function ( targetEle ) {
			targetEle.textContent = value;
		} );
	}
};
// document ready.
document.addEventListener( 'DOMContentLoaded', () => {
	const shippingContainers = document.querySelectorAll(
		'.uafrica-shipping-container'
	);
	if ( null === shippingContainers ) {
		return; // didn't find uAfrica shipping container, no continue;
	}

	shippingContainers.forEach( function ( shippingContainer ) {
		new uAfricaShipping( shippingContainer );
	} );

	// Focus the input box if we're on the tracking page.
	const input = document.querySelectorAll('.order-nr-input');
	if ( null !== input && input.length > 0 ) {
		input[0].focus();
	}
} );
