/**
 * Processador central e agnóstico de payloads gerados pelo OperationResult.
 * * @param {Object} response - O JSON vindo do backend (AJAX ou Session)
 * @param {HTMLElement|null} triggerEl - O elemento/botão que iniciou a requisição (opcional)
 */
export function processResponse(response, triggerEl = null) {
  // Aborta se a resposta for inválida ou vazia
  if (!response) return;

  const { success, message, ui, data } = response;

  // 1. Processa alterações estruturais do DOM automáticas (Updates, Appends, Removes)
  if (ui?.updates && Array.isArray(ui.updates)) {
    ui.updates.forEach(update => {
      const el = document.querySelector(update.target);
      if (!el) return;

      switch (update.type) {
        case 'update':
          el.innerHTML = update.html;
          break;
        case 'append':
          el.insertAdjacentHTML('beforeend', update.html);
          break;
        case 'remove':
          el.remove();
          break;
      }
    });
  }

  // 2. Trata Redirecionamento de Infraestrutura
  if (ui?.redirect) {
    window.location.href = ui.redirect;
    return;
  }

  // 3. O SEU GOL DE PLACA: Notifica o site local passando o controle total do feedback visual!
  window.dispatchEvent(new CustomEvent('backend:post-response', {
    detail: {
      success,
      message, // Contém { text, status, display_mode }
      ui,
      data,
      triggerEl
    }
  }));
}