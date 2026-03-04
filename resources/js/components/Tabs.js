export class Tabs {
  constructor(items, toggles, options = {}) {
    this.items = items;
    this.toggles = toggles;
    this.current = 0;
    this.timer = null;
    this.interval = options.interval || 5000;
    this.activeClass = options.activeClass || 'is-active';
  }

  init() {
    if (!this.items.length) return;
    this._listeners();
    this.show(0);
    this.startTimer();
    return this; // Permite encadeamento
  }

  _listeners() {
    this.toggles.forEach((t, i) => {
      t.addEventListener('click', () => this.show(i));
    });
  }

  show(index) {
    this.current = index;
    this.items.forEach((item, i) => item.classList.toggle(this.activeClass, i === index));
    this.toggles.forEach((t, i) => t.classList.toggle(this.activeClass, i === index));
    this.resetTimer();
  }

  next() {
    let nextIdx = (this.current + 1) % this.items.length;
    this.show(nextIdx);
  }

  startTimer() {
    if (this.interval > 0) {
      this.timer = setInterval(() => this.next(), this.interval);
    }
  }

  resetTimer() {
    clearInterval(this.timer);
    this.startTimer();
  }
}