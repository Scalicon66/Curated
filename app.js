/* ==============================================
   Curated. — UI-only JavaScript
   Cart logic + form handling lives in PHP.
   This file only enhances the UI:
     - Live client-side filtering of the product grid
     - Quantity stepper on the product detail page
     - Toast display for server flash messages
   The site works fully without JavaScript.
   ============================================== */

(function () {
  // -------- Toast helper (reads server-rendered flash messages) --------
  const toastEl = document.getElementById("toast");
  function showToast(msg) {
    if (!toastEl || !msg) return;
    toastEl.textContent = msg;
    toastEl.classList.add("show");
    clearTimeout(showToast._t);
    showToast._t = setTimeout(() => toastEl.classList.remove("show"), 1800);
  }
  const flash = document.getElementById("serverFlash");
  if (flash) showToast(flash.dataset.message);

  // -------- Live product grid filter (no reload) --------
  const searchInput = document.getElementById("searchInput");
  const grid = document.getElementById("grid");
  if (searchInput && grid) {
    const cards = Array.from(grid.querySelectorAll(".card"));
    let pending = null;
    searchInput.addEventListener("input", () => {
      clearTimeout(pending);
      pending = setTimeout(() => {
        const q = searchInput.value.trim().toLowerCase();
        let visible = 0;
        cards.forEach((card) => {
          const name = card.dataset.name || "";
          const desc = card.dataset.desc || "";
          const cat = (card.dataset.cat || "").toLowerCase();
          const match =
            !q || name.includes(q) || desc.includes(q) || cat.includes(q);
          card.style.display = match ? "" : "none";
          if (match) visible++;
        });

        let emptyEl = grid.querySelector(".js-empty");
        if (visible === 0) {
          if (!emptyEl) {
            emptyEl = document.createElement("div");
            emptyEl.className = "empty js-empty";
            emptyEl.style.gridColumn = "1 / -1";
            emptyEl.innerHTML =
              "<h3>Nothing matches that.</h3><p>Try a different search term or category.</p>";
            grid.appendChild(emptyEl);
          }
        } else if (emptyEl) {
          emptyEl.remove();
        }
      }, 80);
    });
  }

  // -------- Quantity stepper on product detail page --------
  document.querySelectorAll("[data-qty]").forEach((wrap) => {
    const display = wrap.querySelector("[data-qty-display]");
    const input = wrap.querySelector("[data-qty-input]");
    const dec = wrap.querySelector("[data-qty-dec]");
    const inc = wrap.querySelector("[data-qty-inc]");
    if (!display || !input) return;

    let qty = Math.max(1, parseInt(input.value, 10) || 1);
    function update() {
      display.textContent = qty;
      input.value = qty;
    }
    if (dec) dec.addEventListener("click", () => { qty = Math.max(1, qty - 1); update(); });
    if (inc) inc.addEventListener("click", () => { qty += 1; update(); });
    update();
  });
})();
