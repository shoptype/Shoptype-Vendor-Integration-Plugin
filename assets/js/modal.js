var $ = jQuery;

$(document).ready( function() {
	var $modal = $(".cocart-pro-modal");

	if ( $modal ) {
		new Modal($modal);
	}

	/**
	 * Send response then deactivate plugin.
	 */
	$('#send-deactivation').on('click', function(e) {
		var reason           = $('#cocart-pro-reason').val();
		var details          = $('#cocart-pro-details').val();
		var deactivation_url = $(this).attr("href");

		e.preventDefault();

		$.post( $("#ajax_url").val(), {
			action: 'cocart_pro_feedback_modal',
			fb_reason: reason,
			fb_details: details,
		} ).done( function( response ) {
			if ( ! response ) {
				console.log( 'No response!' );
				return;
			}

			// Continue deactivation.
			window.location.href=deactivation_url;
		} );
	});
});

function Modal(aElem) {
	var refThis = this;

	this.elem          = aElem;
	this.overlay       = $('.cocart-pro-modal-overlay');
	this.radio         = $('input[name=reason]', aElem);
	this.closer        = $('.cocart-pro-modal-close, .cocart-pro-modal-cancel', aElem);
	this.return        = $('.cocart-pro-modal-return', aElem);
	this.opener        = $('.plugins [data-slug="cocart-pro"] .deactivate');
	this.question      = $('.cocart-pro-modal-question', aElem);
	this.button        = $('.button-primary', aElem);
	this.title         = $('.cocart-pro-modal-header h2', aElem);
	this.textFields    = $('input[type=text], textarea',aElem);
	this.hiddenReason  = $('#cocart-pro-reason', aElem);
	this.hiddenDetails = $('#cocart-pro-details', aElem);
	this.titleText     = this.title.text();

	// Open
	this.opener.click( function() {
		refThis.open();

		return false;
	});

	// Close
	this.closer.click( function() {
		refThis.close();

		return false;
	});

	aElem.bind('keyup', function() {
		if ( event.keyCode == 27 ) { // ESC Key
			refThis.close();

			return false;
		}
	});

	// Back
	this.return.click( function() {
		refThis.returnToQuestion();

		return false;
	});

	// Click on radio
	this.radio.change( function() {
		refThis.change( $(this) );
	});

	// Write text
	this.textFields.keyup( function() {
		refThis.hiddenDetails.val( $(this).val() );

		if ( refThis.hiddenDetails.val() != '' ) {
			refThis.button.removeClass('cocart-pro-isDisabled');
			refThis.button.removeAttr("disabled");
		}
		else {
			refThis.button.addClass('cocart-pro-isDisabled');
			refThis.button.attr("disabled", true);
		}
	});
}

/**
 * Change modal state
 */
Modal.prototype.change = function(aElem) {
	var id      = aElem.attr('id');
	var refThis = this;

	// Reset values
	this.hiddenReason.val(aElem.val());
	this.hiddenDetails.val('');
	this.textFields.val('');

	$('.cocart-pro-modal-fieldHidden').removeClass('cocart-pro-isOpen');
	$('.cocart-pro-modal-hidden').removeClass('cocart-pro-isOpen');

	this.button.removeClass('cocart-pro-isDisabled');
	this.button.removeAttr("disabled");

	switch(id) {
		case 'reason-temporary':
			// Nothing to do
		break;

		default:
			var $panel = $('#' + id + '-panel');
			var $field = aElem.siblings('.cocart-pro-modal-fieldHidden');

			// If reason has a pre-defined answer, then show it.
			if ( $panel.length > 0 ) {
				refThis.question.removeClass('cocart-pro-isOpen');
				refThis.return.addClass('cocart-pro-isOpen');
	
				$panel.addClass('cocart-pro-isOpen');
	
				var titleText = $panel.find('h3').text();
				this.title.text(titleText);
			} else {

				// Else, if reason requires user input, show hidden field.
				if ( $field.length > 0 ) {
					$field.addClass('cocart-pro-isOpen');
					$field.find('input, textarea').focus();

					refThis.button.addClass('cocart-pro-isDisabled');
					refThis.button.attr("disabled", true);
				}
			}

		break;
	}
};

/**
 * Return to the question.
 */
Modal.prototype.returnToQuestion = function() {
	$('.cocart-pro-modal-fieldHidden').removeClass('cocart-pro-isOpen');
	$('.cocart-pro-modal-hidden').removeClass('cocart-pro-isOpen');

	this.question.addClass('cocart-pro-isOpen');
	this.return.removeClass('cocart-pro-isOpen');
	this.title.text(this.titleText);

	// Reset values
	this.hiddenReason.val('');
	this.hiddenDetails.val('');

	this.radio.attr('checked', false);
	this.button.addClass('cocart-pro-isDisabled');
	this.button.attr("disabled", true);
};

/**
 * Open modal.
 */
Modal.prototype.open = function() {
	this.elem.css('display','block');
	this.overlay.css('display','block');

	// Reset current tab cocart-pro
	localStorage.setItem('cocart-pro-hash', '');
};

/**
 * Close modal.
 */
Modal.prototype.close = function() {
	this.returnToQuestion();
	this.elem.css('display','none');
	this.overlay.css('display','none');
};
