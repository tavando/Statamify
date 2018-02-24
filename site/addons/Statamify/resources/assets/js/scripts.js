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
			}, 0)
		}
	}, false);
	return realXHR;
}

var oldXHR = window.XMLHttpRequest;
window.XMLHttpRequest = newXHR;
