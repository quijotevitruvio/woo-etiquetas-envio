(function () {
	'use strict';

	var DEFAULTS = {
		products: [
			'.product',
			'.type-product',
			'.wc-block-grid__product',
			'.wc-block-components-product-card',
			// WP Carousel (Shapedplugin)
			'.wpcp-single-item',
			'.sp-wpcp-product',
			'.wpcp-product',
			'.wpcarousel-item',
			'.sp-wpcp-item',
			'.wpcp-item',
			'.shapedplugin-carousel .swiper-slide',
			'.swiper-slide.product',
			'.slick-slide.product',
			'.owl-item .product'
		].join(','),

		imageWrappers: [
			// La galería del producto individual va PRIMERO: si el wrapper de
			// Pinterest (inline, sin altura) gana aquí, el badge desaparece.
			'.woocommerce-product-gallery__image',
			'.box-image',
			'.product-image',
			'.product-element-top',
			'.image-fade_in_back',
			'.pinterest-for-woocommerce-image-wrapper',
			'.lmc-card-media',
			'.wc-block-components-product-image',
			'.wc-block-grid__product-image',
			// WP Carousel
			'.wpcp-slide-image',
			'.sp-wpcp-product-image',
			'.wpcp-product-image',
			'.wpcp-image',
			'.sp-wpcp-image',
			'.wpcp-thumbnail',
			'.wpcarousel-item-image',
			'.wpcp-item-image'
		].join(','),

		// Contenedores que NO disparan hooks WC y necesitan inyección por URL
		carouselItems: [
			'.wpcp-single-item',
			'.wpcarousel-item',
			'.sp-wpcp-item'
		].join(',')
	};

	var SELECTORS = (window.LEBadgeSelectors && typeof window.LEBadgeSelectors === 'object')
		? Object.assign({}, DEFAULTS, window.LEBadgeSelectors)
		: DEFAULTS;

	// Cache URL → HTML para no re-pedir slides duplicados de Swiper.
	var urlCache = {};
	var inflightUrls = {};

	/**
	 * Ajusta el contenedor al rectángulo REAL de la imagen (las carátulas con
	 * object-fit:contain son más angostas que su caja: anclar al wrapper deja
	 * el badge "fuera del libro"). Las clases pos--* alinean dentro vía flex.
	 */
	/**
	 * Imagen real del producto dentro de un nodo: descarta los <img> de los
	 * emojis del propio badge (wp-emoji) y cualquier imagen diminuta/oculta.
	 */
	function realImg(scope) {
		var imgs = scope.querySelectorAll('img');
		for (var i = 0; i < imgs.length; i++) {
			var im = imgs[i];
			if (im.closest('.le-badges-container')) continue;
			if (im.classList.contains('emoji')) continue;
			var r = im.getBoundingClientRect();
			if (r.width > 40 && r.height > 40) return im;
		}
		return null;
	}

	function fitToImage(container) {
		var wrapper = container.parentElement;
		var img = wrapper && realImg(wrapper);
		if (!img) return;
		var ir = img.getBoundingClientRect();
		if (ir.width < 40 || ir.height < 40) return; // lazy: aún sin cargar
		var wr = wrapper.getBoundingClientRect();
		container.classList.add('le-fit-img');
		container.style.left = (ir.left - wr.left) + 'px';
		container.style.top = (ir.top - wr.top) + 'px';
		container.style.width = ir.width + 'px';
		container.style.height = ir.height + 'px';
		container.style.right = 'auto';
		container.style.bottom = 'auto';
		container.style.transform = 'none';
	}

	function refitAll() {
		document.querySelectorAll('.le-badges-container.le-moved').forEach(fitToImage);
	}

	function moveBadges() {
		var containers = document.querySelectorAll('.le-badges-container:not(.le-moved)');
		containers.forEach(function (container) {
			container.classList.add('le-moved');
			var product = container.closest(SELECTORS.products);
			if (!product) return;

			// Por PRIORIDAD de la lista (no por orden del documento, que es lo
			// que hace querySelector con lista combinada) y solo wrappers
			// visibles: los clones ocultos de la galería/zoom se descartan.
			var imgWrapper = null;
			var wrapperSelectors = SELECTORS.imageWrappers.split(',');
			outer:
			for (var wi = 0; wi < wrapperSelectors.length; wi++) {
				var cands = product.querySelectorAll(wrapperSelectors[wi].trim());
				for (var wj = 0; wj < cands.length; wj++) {
					var wr2 = cands[wj].getBoundingClientRect();
					if (wr2.width > 40 && wr2.height > 40 && getComputedStyle(cands[wj]).visibility !== 'hidden') {
						imgWrapper = cands[wj];
						break outer;
					}
				}
			}
			if (!imgWrapper) {
				var img = realImg(product);
				imgWrapper = img && img.parentElement;
			}
			if (imgWrapper) {
				imgWrapper.style.position = 'relative';
				// Un wrapper inline (p. ej. el de Pinterest) no tiene caja para
				// anclar absolutos: el badge quedaría invisible.
				if (getComputedStyle(imgWrapper).display === 'inline') {
					imgWrapper.style.display = 'block';
				}
				imgWrapper.appendChild(container);
				fitToImage(container);
			}
		});
	}

	// Re-medir cuando carguen imágenes (lazy/cambio de variación) o al
	// redimensionar la ventana.
	document.addEventListener('load', function (e) {
		if (e.target && e.target.tagName === 'IMG') {
			refitAll();
		}
	}, true);
	var leRz;
	window.addEventListener('resize', function () {
		clearTimeout(leRz);
		leRz = setTimeout(refitAll, 150);
	});

	/**
	 * Para carruseles que NO disparan hooks WC: extrae URLs de productos en slides
	 * vacíos, las consulta en lote y mete el HTML del badge.
	 */
	var DEBUG = (window.LECarousel && window.LECarousel.debug) || /\bledebug=1\b/.test(window.location.search);
	function dbg() { if (DEBUG) console.log.apply(console, ['[LE]'].concat([].slice.call(arguments))); }

	function injectCarouselBadges() {
		if (!window.LECarousel || !window.LECarousel.restUrl) {
			dbg('NO LECarousel localized — el script no recibió la URL del REST. Revisa que enqueue_assets corra en frontend.');
			return;
		}

		var items = document.querySelectorAll(SELECTORS.carouselItems);
		if (!items.length) { dbg('No se encontraron items con selectores:', SELECTORS.carouselItems); return; }
		dbg('Items detectados:', items.length, 'con selectores:', SELECTORS.carouselItems);

		var pendingUrls = [];
		var itemsByUrl = {};

		items.forEach(function (item) {
			if (item.classList.contains('le-carousel-checked')) return;
			if (item.querySelector('.le-badges-container')) return;

			var link = item.querySelector('a[href]');
			if (!link) { dbg('Item sin <a href>, salta:', item); return; }
			var url = link.href.split('#')[0].split('?')[0];
			if (!url) return;

			if (!itemsByUrl[url]) itemsByUrl[url] = [];
			itemsByUrl[url].push(item);
			item.classList.add('le-carousel-checked');

			if (urlCache[url] !== undefined) {
				injectIntoItem(item, urlCache[url]);
				return;
			}
			if (inflightUrls[url]) return;

			pendingUrls.push(url);
			inflightUrls[url] = true;
		});

		if (!pendingUrls.length) return;
		dbg('Pidiendo badges para', pendingUrls.length, 'URLs:', pendingUrls);

		var fd = new FormData();
		pendingUrls.forEach(function (u) { fd.append('urls[]', u); });
		fd.append('context', 'catalog');

		fetch(LECarousel.restUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: fd
		})
			.then(function (r) {
				if (!r.ok) { dbg('REST respondió', r.status, r.statusText); return Promise.reject(r); }
				return r.json();
			})
			.then(function (data) {
				dbg('REST OK. Respuesta:', data);
				if (data && data.debug) {
					Object.keys(data.debug).forEach(function (u) {
						var status = data.debug[u];
						if (status !== 'product_' && status.indexOf('_ok') === -1) {
							dbg('⚠️', u, '→', status);
						}
					});
				}
				if (!data || !data.badges) return;
				Object.keys(data.badges).forEach(function (url) {
					urlCache[url] = data.badges[url] || '';
					delete inflightUrls[url];
					if (urlCache[url] === '') {
						dbg('Sin HTML para', url, '— el producto no califica para ningún badge habilitado, o no se resolvió. Status:', (data.debug || {})[url]);
					}
					(itemsByUrl[url] || []).forEach(function (item) {
						injectIntoItem(item, urlCache[url]);
					});
				});
				moveBadges();
			})
			.catch(function (e) {
				pendingUrls.forEach(function (u) { delete inflightUrls[u]; });
				console.error('[LE] Error fetching carousel badges:', e);
			});
	}

	function injectIntoItem(item, html) {
		if (!html) return;
		if (item.querySelector('.le-badges-container')) return;
		var imgWrapper = item.querySelector(SELECTORS.imageWrappers);
		var target = imgWrapper || item;
		target.style.position = 'relative';
		target.insertAdjacentHTML('beforeend', html);
	}

	function init() {
		moveBadges();
		injectCarouselBadges();

		var events = 'updated_wc_div wc_fragments_refreshed updated_shipping_method';
		if (window.jQuery) {
			window.jQuery(document.body).on(events, function () {
				moveBadges();
				injectCarouselBadges();
			});
			window.jQuery(document).on('wpcp_carousel_init wpcp_after_init swiper-init', function () {
				moveBadges();
				injectCarouselBadges();
			});
		}

		if (typeof MutationObserver !== 'undefined') {
			var debounce;
			var observer = new MutationObserver(function () {
				clearTimeout(debounce);
				debounce = setTimeout(function () {
					moveBadges();
					injectCarouselBadges();
				}, 150);
			});
			observer.observe(document.body, { childList: true, subtree: true });
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
