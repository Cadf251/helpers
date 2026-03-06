export function initSmoothLinks(selector = ".js--nav-links") {
  const navMenu = document.querySelector(selector);

  if(!navMenu) return;

  navMenu.querySelectorAll("a").forEach(link => {
    link.addEventListener("click", e => {
      e.preventDefault();
      const target = document.querySelector(link.getAttribute("href"));
      target.scrollIntoView({ behavior: "smooth" });
    });
  });
}