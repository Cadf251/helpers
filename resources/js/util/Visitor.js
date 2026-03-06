class Visitor {
  constructor() {
    this.data = {
      pagesViewed: [],
      utm: [],
      duration: 0
    };

    this.init();
  }

  init() {
    this.captureUTMs();
    this.trackPageView();
    this.beforeLeaving();
  }

  captureUTMs() {
    const urlParams = new URLSearchParams(window.location.search);
    const utms = ['utm_source', 'utm_medium', 'utm_campaign'];
    let hasUtm = false;

    utms.forEach(param => {
      if (urlParams.has(param)) {
        this.data.utm[param] = urlParams.get(param);
      }
    });

    if (hasUtm) {
      this.cleanURL();
    }
  }

  cleanURL() {
    const cleanUrl = window.location.origin + window.location.pathname;
    window.history.replaceState({}, document.title, cleanUrl);
  }

  trackPageView() {
    this.data.pagesViewed.push(window.location.pathname);
  }

  beforeLeaving() {
    window.addEventListener("beforeunload", () => {
      this.data.duration = (Date.now() - this.startTime) / 1000; // Em segundos
      console.log("Dados finais da sessão:", this.data);
    });
  }
}