(function ($) {
	'use strict';

	$(document).on('click', '.le-isbn-lookup', function () {
		var btn = $(this);
		var status = btn.siblings('.le-isbn-status');
		var input = $(btn.data('target'));
		var isbn = (input.val() || '').replace(/[^\dxX]/g, '');

		if (!isbn || isbn.length < 10) {
			status.text('Ingresa un ISBN válido (10 o 13 dígitos).').css('color', '#d63638');
			return;
		}

		status.text(LEIsbn.i18n.searching).css('color', '#646970');
		btn.prop('disabled', true);

		$.ajax({
			url: LEIsbn.restUrl,
			method: 'GET',
			data: { isbn: isbn },
			headers: { 'X-WP-Nonce': LEIsbn.nonce }
		})
			.done(function (data) {
				// Título → post_title
				if (data.title) {
					var fullTitle = data.title + (data.subtitle ? ': ' + data.subtitle : '');
					$('#title').val(fullTitle).trigger('input');
				}

				// Descripción → post_content (TinyMCE o textarea)
				if (data.description) {
					var html = '<p>' + data.description.replace(/\n/g, '</p><p>') + '</p>';
					if (data.authors && data.authors.length) {
						html += '<p><strong>Autor(es):</strong> ' + data.authors.join(', ') + '</p>';
					}
					if (data.publisher) {
						html += '<p><strong>Editorial:</strong> ' + data.publisher + (data.publish_date ? ' · ' + data.publish_date : '') + '</p>';
					}
					if (data.pages) {
						html += '<p><strong>Páginas:</strong> ' + data.pages + '</p>';
					}
					if (window.tinyMCE && window.tinyMCE.get('content')) {
						window.tinyMCE.get('content').setContent(html);
					} else {
						$('#content').val(html);
					}
				}

				// Excerpt corto
				if (data.subtitle || (data.authors && data.authors.length)) {
					var excerpt = (data.subtitle || '') + (data.authors && data.authors.length ? ' — ' + data.authors.join(', ') : '');
					$('#excerpt').val(excerpt.trim());
				}

				// Cover URL → mostrar enlace
				if (data.cover) {
					status.html(LEIsbn.i18n.filled + ' <a href="' + data.cover + '" target="_blank">Ver portada</a>').css('color', '#00a32a');
				} else {
					status.text(LEIsbn.i18n.filled).css('color', '#00a32a');
				}
			})
			.fail(function () {
				status.text(LEIsbn.i18n.notfound).css('color', '#d63638');
			})
			.always(function () {
				btn.prop('disabled', false);
			});
	});
})(jQuery);
