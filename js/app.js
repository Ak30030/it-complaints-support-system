/* ==========================================================
   IT Complaints Support System — shared interactivity
   ========================================================== */

document.addEventListener("DOMContentLoaded", function () {
  /* ------------------------------------------------------
       0. Move every modal to be a direct child of <body>.
          (Lesson learned on the CMS project: a CSS `transform`
          on any ancestor breaks `position: fixed` for modals.
          Our fade-in wrapper here only uses opacity for exactly
          this reason, but this stays as a permanent safety net.)
       ------------------------------------------------------ */
  document.querySelectorAll(".modal").forEach(function (modalEl) {
    if (modalEl.parentElement !== document.body) {
      document.body.appendChild(modalEl);
    }
  });

  /* ------------------------------------------------------
       1. Turn server-rendered .alert banners into toasts.
       ------------------------------------------------------ */
  document.querySelectorAll(".alert").forEach(function (alertEl, i) {
    alertEl.classList.add("itcs-toast");
    alertEl.style.animationDelay = i * 0.08 + "s";

    if (!alertEl.querySelector(".btn-close")) {
      var closeBtn = document.createElement("button");
      closeBtn.className = "btn-close float-end";
      closeBtn.setAttribute("aria-label", "Close");
      closeBtn.addEventListener("click", function () {
        dismiss(alertEl);
      });
      alertEl.appendChild(closeBtn);
    }

    setTimeout(function () {
      dismiss(alertEl);
    }, 4500);
  });

  function dismiss(el) {
    el.classList.add("itcs-toast--out");
    setTimeout(function () {
      el.remove();
    }, 300);
  }

  /* ------------------------------------------------------
       2. Count-up animation for dashboard stat numbers.
       ------------------------------------------------------ */
  document.querySelectorAll(".count-up").forEach(function (el) {
    var target = parseInt(el.getAttribute("data-target"), 10) || 0;
    var duration = 700;
    var startTime = null;

    function step(timestamp) {
      if (!startTime) startTime = timestamp;
      var progress = Math.min((timestamp - startTime) / duration, 1);
      var eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.round(eased * target);
      if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  });

  /* ------------------------------------------------------
       3. Live client-side table search.
       ------------------------------------------------------ */
  document.querySelectorAll(".live-search").forEach(function (input) {
    var table = document.querySelector(input.getAttribute("data-target"));
    if (!table) return;
    var rows = table.querySelectorAll("tbody tr");
    var emptyMsg = null;

    input.addEventListener("input", function () {
      var query = input.value.trim().toLowerCase();
      var visibleCount = 0;

      rows.forEach(function (row) {
        var match = row.textContent.toLowerCase().indexOf(query) !== -1;
        row.style.display = match ? "" : "none";
        if (match) visibleCount++;
      });

      if (visibleCount === 0 && query !== "") {
        if (!emptyMsg) {
          emptyMsg = document.createElement("tr");
          emptyMsg.className = "live-search-empty";
          var td = document.createElement("td");
          td.colSpan = table.querySelectorAll("thead th").length || 5;
          td.className = "text-muted text-center py-3";
          td.textContent = "No matching results.";
          emptyMsg.appendChild(td);
          table.querySelector("tbody").appendChild(emptyMsg);
        }
        emptyMsg.style.display = "";
      } else if (emptyMsg) {
        emptyMsg.style.display = "none";
      }
    });
  });

  /* ------------------------------------------------------
       4. Fade-in the main content area on page load.
       ------------------------------------------------------ */
  var main = document.querySelector(".itcs-fade-in");
  if (main) {
    requestAnimationFrame(function () {
      main.classList.add("is-visible");
    });
  }
});

/* ------------------------------------------------------
   5. Safety net: force-clean any stuck modal backdrop.
   ------------------------------------------------------ */
document.addEventListener("hidden.bs.modal", function () {
  if (!document.querySelector(".modal.show")) {
    document.body.classList.remove("modal-open");
    document.body.style.removeProperty("padding-right");
    document.body.style.removeProperty("overflow");
    document.querySelectorAll(".modal-backdrop").forEach(function (el) {
      el.remove();
    });
  }
});
