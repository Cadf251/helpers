/**
 * Módulo de Overlay Reutilizável
 */
export const Overlay = {
  el: null,

  init(selector = '#main-overlay') {
    this.el = document.querySelector(selector);
    if (!this.el) return;

    // Listener nativo para quando o dialog fechar
    this.el.addEventListener('close', () => {
      document.body.style.overflow = ''; // Permite o scroll
    });

    // Fechar ao clicar no backdrop (opcional)
    this.el.addEventListener('click', (e) => {
      if (e.target === this.el) this.close();
    });

    document.addEventListener('click', (e) => {
      const trigger = e.target.closest('[data-overlay-open]');
      const closer = e.target.closest('[data-overlay-close]');
      const moduler = e.target.closest('[data-overlay-open-module]');

      if (trigger) {
        this.open();
      }

      if (closer || e.target === this.el) {
        this.close();
      }

      if (moduler) {
        this.showModule(moduler.dataset.overlayOpenModule);
      }
    });
  },

  open() {
    document.body.style.overflow = 'hidden'; // Trava o scroll
    this.el.showModal();
  },

  close() {
    this.el.close();
  },

  // Opcional, caso haja módulos
  hideModules() {
    let modules = document.querySelectorAll('[data-overlay-module]')
    modules.forEach((module) => {
      module.classList.remove("is-selected");
    });
  },

  showModule(selector) {
    this.hideModules();
    let module = document.querySelector(selector);
    
    if (module) {
      module.classList.add("is-selected");
      this.open();
    }
  }
};