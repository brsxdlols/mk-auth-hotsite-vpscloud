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
  let savedMode = panel.dataset.savedMode;
  let submitting = false;
  function refresh() {
    const pointing = select.value === '__vpscloud_pointing__';
    panel.hidden = !pointing || savedMode !== 'external';
    const images = document.getElementById('vpscloud-image-settings');
    if (images) images.hidden = !['layout-vpscloud-sistema', 'layout-vpscloud-whatsapp'].includes(select.value);
  }
  select.addEventListener('change', refresh);
  nativeForm.addEventListener('submit', async function (event) {
    const pointing = select.value === '__vpscloud_pointing__';
    if (!pointing && (savedMode !== 'external' || submitting)) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    if (submitting) return;
    submitting = true;
    notice.textContent = 'Salvando escolha do site…';
    try {
      const mode = pointing ? 'external' : 'local';
      const data = new FormData(pointingForm);
      data.delete('vpscloud_site_save');
      data.set('vpscloud_site_select', '1');
      data.set('site_mode', mode);
      const response = await fetch(pointingForm.action, {method: 'POST', body: data, credentials: 'same-origin', cache: 'no-store'});
      const html = new DOMParser().parseFromString(await response.text(), 'text/html');
      const result = html.getElementById('vpscloud-site-settings');
      if (!response.ok || !result || result.dataset.savedMode !== mode) {
        throw new Error('Não foi possível salvar. Atualize a página e confira sua sessão.');
      }
      savedMode = mode;
      if (pointing) window.location.reload();
      else if (typeof nativeForm.requestSubmit === 'function') nativeForm.requestSubmit();
      else nativeForm.submit();
    } catch (error) {
      submitting = false;
      notice.textContent = error.message;
    }
  }, true);
  refresh();
}());
