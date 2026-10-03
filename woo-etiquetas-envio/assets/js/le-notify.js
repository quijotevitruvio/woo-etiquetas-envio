(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.le-notify-form').forEach(function (form) {
			form.addEventListener('submit', function (e) {
				e.preventDefault();
				var msg = form.querySelector('.le-notify-msg');
				var input = form.querySelector('.le-notify-email');
				var pid = form.dataset.productId;
				var email = (input.value || '').trim();

				if (!email || email.indexOf('@') === -1) {
					msg.textContent = LENotify.i18n.invalid;
					msg.className = 'le-notify-msg le-notify-msg--error';
					return;
				}

				var btn = form.querySelector('button');
				btn.disabled = true;

				fetch(LENotify.restUrl, {
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': LENotify.nonce
					},
					body: JSON.stringify({ product_id: pid, email: email })
				})
					.then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
					.then(function () {
						msg.textContent = LENotify.i18n.success;
						msg.className = 'le-notify-msg le-notify-msg--ok';
						input.value = '';
					})
					.catch(function () {
						msg.textContent = LENotify.i18n.error;
						msg.className = 'le-notify-msg le-notify-msg--error';
					})
					.finally(function () { btn.disabled = false; });
			});
		});
	});
})();
