(function () {
	'use strict';

	function loadDeliveryBox(box) {
		var pid = box.dataset.lePid;
		if (!pid || !window.LEData || !LEData.restUrl) return;

		fetch(LEData.restUrl + encodeURIComponent(pid), {
			headers: { 'X-WP-Nonce': LEData.nonce || '' },
			credentials: 'same-origin'
		})
			.then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
			.then(function (d) { if (d && d.html) box.outerHTML = d.html; })
			.catch(function (e) { console.error('woo-etiquetas-envio lazy:', e); });
	}

	// El script va en el footer: el placeholder ya existe en el DOM, así que
	// disparamos el fetch DE INMEDIATO (no esperamos a DOMContentLoaded) para
	// que la caja aparezca cuanto antes.
	document.querySelectorAll('#le-delivery-box-placeholder').forEach(loadDeliveryBox);

	// El refresco al cambiar de variación sí necesita jQuery y el form listos.
	function bindVariations() {
		var form = document.querySelector('form.variations_form');
		if (!form || !window.jQuery) return;
		window.jQuery(form).on('found_variation', function (event, variation) {
			if (!variation || !variation.variation_id) return;
			var box = document.querySelector('.le-delivery-box, #le-delivery-box-placeholder');
			if (!box) return;
			var placeholder = document.createElement('div');
			placeholder.id = 'le-delivery-box-placeholder';
			placeholder.dataset.lePid = variation.variation_id;
			box.replaceWith(placeholder);
			loadDeliveryBox(placeholder);
		});
	}

	if (document.readyState !== 'loading') {
		bindVariations();
	} else {
		document.addEventListener('DOMContentLoaded', bindVariations);
	}
})();
