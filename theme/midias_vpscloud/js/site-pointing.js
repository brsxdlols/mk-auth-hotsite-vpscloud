(function () {
  'use strict';
  const select = document.querySelector('[data-vpscloud-theme]');
  const panel = document.getElementById('vpscloud-site-settings');
  if (!select || !panel || !select.form) return;
  const nativeForm = select.form;
  const pointingForm = panel.querySelector('form');
  const notice = document.createElement('p');
  notice.setAttribute('role', 'status');
  nativeForm.appendChild(notice);
  let active = panel.dataset.activeTarget === '1';
  let submitting = false;
  function refresh() {
    const pointing = select.value === '__vpscloud_pointing__';
    panel.hidden = !pointing;
    for (const button of nativeForm.querySelectorAll('button[type="submit"],button:not([type]),input[type="submit"]')) {
      button.hidden = pointing;
    }
    const images = document.getElementById('vpscloud-image-settings');
    if (images) images.hidden = !['layout-vpscloud-sistema', 'layout-vpscloud-whatsapp'].includes(select.value);
  }
  select.addEventListener('change', refresh);
  nativeForm.addEventListener('submit', async function (event) {
    if (select.value === '__vpscloud_pointing__') {
      event.preventDefault();
      panel.querySelector('[name="site_url"]').focus();
      return;
    }
    if (!active || submitting) return;
    event.preventDefault();
    notice.textContent = 'Desativando apontamento para salvar o tema escolhido…';
    try {
      const data = new FormData(pointingForm);
      data.set('site_mode', 'local');
      const response = await fetch(pointingForm.action, {method: 'POST', body: data, credentials: 'same-origin', cache: 'no-store'});
      const html = new DOMParser().parseFromString(await response.text(), 'text/html');
      const result = html.getElementById('vpscloud-site-settings');
      if (!response.ok || !result || result.dataset.activeTarget !== '0') {
        throw new Error('Não foi possível desativar o apontamento. Atualize a página e confira sua sessão.');
      }
      active = false;
      submitting = true;
      if (typeof nativeForm.requestSubmit === 'function') nativeForm.requestSubmit();
      else nativeForm.submit();
    } catch (error) {
      notice.textContent = error.message;
    }
  });
  refresh();
}());
