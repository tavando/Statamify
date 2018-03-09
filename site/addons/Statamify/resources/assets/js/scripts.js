Statamify = {

	init: function() {

		this.removeNavCollections()

	},

	removeNavCollections: function() {

		if ($('[class*="statamify"].active').length) {
			$('.nav-collections').removeClass('visible active')
		}

		$.each($('[class*="statamify"].active'), function() {
			if ($(this).parents('li').length) {
				$(this).parents('li').addClass('visible active')
			}
		})

	},

	convertListingCells: function() {

		$.each($('[class^="column-listing_"]'), function() {
			$(this).attr('data-title', $(this).text().replace('Listing ', '').trim())
		})

		$.each($('[class^="cell-listing_"]'), function() {
			$(this).html($(this).text())
		})
		
	},

	changeSectionsToTabs: function() {

		if ($(".section-fieldtype").length && !$('#publish-fields').is('.naked')) {

			$('#publish-fields').addClass('naked')

			// Code from "Section" by Rudy Affandi ### https://github.com/lesaff/statamic-sections
			$(".form-group.section-fieldtype").addClass("section-header");

			$(".section-header").each(function() {
					$(this).nextUntil(".section-header").wrapAll('<div class="tab-pane fade" />')
			});

			$(".tab-pane").each(function() {
					$(this).children('.form-group').wrapAll('<div class="tab-flex" />')
			});

			$(".section-header").wrapAll('<ul id="section-tabs" class="nav nav-tabs" role="tablist" />')

			$(".section-header").each(function() {
					$(this).replaceWith($('<li role="presentation">' + this.innerHTML + '</li>'));
			});

			$(".nav-tabs li > div").each(function(index, element) {
					$(this).replaceWith($('<a href="#panel' + index + '" role="tab" data-toggle="tab">' + this.innerHTML + '</a>'));
			});

			$(".nav-tabs li").first().addClass("first active");

			$(".tab-pane").each(function(index, element){
					$(this).attr({
							'id': 'panel' + index,
							'role': 'tabpanel',
					});
					$(this).addClass('tab-' + $('[href="#panel' + index + '"] label').text().trim().toLowerCase())
			});

			var tabContainer = $('<div>', {'class': 'tab-content'});
			var panes = $(".tab-pane");

			panes.sort(function(a,b){
					var an = a.getAttribute('id'),
							bn = b.getAttribute('id');

					if(an > bn) {
							return 1;
					}

					if(an < bn) {
							return -1;
					}

					return 0;

			});

			$(".publish-fields").append(tabContainer);
			panes.detach().appendTo(tabContainer);

			tabContainer.parent().addClass('tabs-wrapper')

			$(".tab-content .tab-pane").first().addClass("first active").removeClass('fade');

			$(".nav-tabs li a").each(function(index, element){
					$(this).find('small').detach().prependTo('#panel' + index);
			});

		}

	},

	orderPreviewCustomerDetails: function() {

		$dataGroups = $('.tab-customer .grid-fieldtype')
		details = []

		$.each($dataGroups, function(key, group) {
			
			if (key == 0 || (key == 1 && $('.tab-customer .toggle-container').is('.on'))) {

				text = ''
				fields = $(group).find('.col-md-6 input')

				line1 = '<strong>Name:</strong> ' + $(fields[0]).val() + ' ' + $(fields[1]).val() + ($(fields[2]).val() != '' ? '<br/><strong>Company: </strong>' + $(fields[2]).val() : '')
				text += line1.trim() + '<br/>'
				line2 = '<strong>Address:</strong> ' + $(fields[4]).val()  + ($(fields[5]).val() != '' ? ', ' + $(fields[5]).val() : '')
				text += line2.trim() + '<br/>'

				last = $(group).find('.col-md-12 .col-md-6')
				region = $(last[1]).find('input').length ? $(last[1]).find('input').val() : $(last[1]).find('.select').attr('data-content') + ' (' + $(last[1]).find('select').val() + ')'
				country = $(last[0]).find('.select').attr('data-content') ? $(last[0]).find('.select').attr('data-content') + ' (' + $(last[0]).find('select').val() + ')' : ''

				line3 = '<strong>City:</strong> ' + $(fields[7]).val()  + ' ' + $(fields[6]).val() + (region != '' ? ', ' + region : '')
				text += line3.trim() + '<br/><strong>Country:</strong> ' + country.trim()

				details.push(text)

			}

		})

		customer = $('[name="listing_customer"]').val()
		email = $('[name="listing_email"]').val()

		html = `
			<div class="card" id="order-preview-details">
				<div class="publish-fields pb-1">
					<div class="form-group inline">
						<div class="form-group" style="margin-bottom:0">
							<label class="block">Customer</label>
							<div class="customer-phone small-text"><strong>Name:</strong> <span>` + customer + `</span></div>
							<div class="customer-email small-text"><strong>Email:</strong> <span><a href="mailto:` + email + `">` + email + `</a></span></div>
						</div>
						<div class="form-group" style="margin-bottom:0" id="shipping-textarea">
							<label class="block">Shipping address</label>
							<div class="small-text">` + details[0] + `</div>
						</div>
						<div class="form-group" style="margin-bottom:0" id="billing-textarea">
							<label class="block">Billing address</label>
							<div class="small-text">` + (details[1] ? `<textarea class="form-control mono" readonly>` + details[1] + `</textarea>` : `Same as Shipping`) + `</div>
						</div>
					</div>
				</div>
			</div>
		`;

		if (!$('#order-preview-details').length) {

			if ($('.tab-order').length) {

				$('#publish-meta').append(html)

			}

		} else {

			$('#shipping-textarea > div').html(details[0])

			if (details[1]) {

				$('#billing-textarea > div').html(details[1])

			} else {

				$('#billing-textarea > div').text('Same as Shipping')

			}

		}

	}

}

$(document).ready(function() { Statamify.init() })

// Listen to AJAX calls
function newXHR() {
	var realXHR = new oldXHR();
	realXHR.addEventListener("readystatechange", function() {
		if(realXHR.readyState==4){
			setTimeout(function(){
				Statamify.changeSectionsToTabs()
				Statamify.convertListingCells()
				Statamify.orderPreviewCustomerDetails()
			}, 0)
		}
	}, false);
	return realXHR;
}

var oldXHR = window.XMLHttpRequest;
window.XMLHttpRequest = newXHR;