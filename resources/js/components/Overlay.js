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
  },

  open() {
    document.body.style.overflow = 'hidden'; // Trava o scroll
    this.el.showModal(); 
  },

  close() {
    this.el.close();
  },

  // Opcional, caso haja módulos
  hideModules(modules) {
    modules.forEach((module) => {
      module.classList.remove("is-selected");
    });
  },

  showModule(selector, idx) {
    let modules = document.querySelectorAll(selector);
    if (!modules) return;

    this.hideModules(modules);
    
    let module = modules[idx];
    if (module) {
      module.classList.add("is-selected");
      this.open();
    }
  }
};