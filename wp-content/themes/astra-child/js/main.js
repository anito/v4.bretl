const wbp_observe = (function () {
  const MutationObserver =
    window.MutationObserver || window.WebKitMutationObserver;

  return function (obj, callback) {
    if (obj?.nodeType !== 1) return;

    if (MutationObserver) {
      // define a new observer
      var mutationObserver = new MutationObserver(callback);

      // have the observer observe for changes in children
      mutationObserver.observe(obj, { childList: true, subtree: true });
      return mutationObserver;
    }

    // browser support fallback
    else if (window.addEventListener) {
      obj.addEventListener("DOMNodeInserted", callback, false);
      obj.addEventListener("DOMNodeRemoved", callback, false);
    }
  };
})();

jQuery.noConflict();
(function ($) {
  var add_fb_div = function () {
    $("body").prepend('<div id="fb-root"></div>');
  };

  //add disclaimer to gallery image
  const disclaimer =
    "Abbildung kann ähnlich sein. Änderungen und Irrtümer vorbehalten. Mögliches Zubehör auf Bildern ist nicht Teil des Angebots.";
  const add_image_disclaimer = function () {
    $(".woocommerce-product-gallery").before(
      '<div class="product-gallery-disclaimer">' + disclaimer + "</div>"
    );
  };

  // animate scroll
  var add_animate_scroll = function () {
    const headerEl = document.querySelector("#header"),
      headerHeight = headerEl.offsetHeight;

    $('.animate-scroll a[href^="#"]').on("click", function (e) {
      var href = $(this).attr("href");
      $("html, body").animate(
        {
          scrollTop: $(href).offset().top - headerHeight,
        },
        "slow"
      );

      e.preventDefault();
    });
  };

  // Copy and observe an elements wishlist count
  var add_jet_engine_wishlist_hook = (targetSelector, storeName) => {
    function copyToTarget(count) {
      const targetEls = document.querySelectorAll(targetSelector);
      targetEls.forEach((el) => {
        const ankerEl = el.querySelector("a");
        if (ankerEl) ankerEl.style.paddingLeft = "40px";
        let targetEl = el.querySelector(".wishlist-widget");
        if (!targetEl) {
          targetEl = document.createElement("span");
          targetEl.classList.add("wishlist-widget");
          el.append(targetEl);
        }
        if (count === "0") {
          location = location.href;
        }
        targetEl.innerHTML = storeEl.innerHTML;
      });
    }

    function observerCallback(mutationList) {
      let count;
      for (const mutation of mutationList) {
        if (mutation.type === "childList") {
          if (mutation.addedNodes.length) {
            count = mutation.addedNodes[0]?.wholeText;
            console.log("A node has been added.", mutation.addedNodes[0]);
          }
          if (mutation.removedNodes.length) {
            console.log("A node has been removed.", mutation.removedNodes[0]);
          }
        } else if (mutation.type === "attributes") {
          console.log(`The ${mutation.attributeName} attribute was modified.`);
        }
      }
      copyToTarget(count);
    }

    const el = document.querySelector(".jet-engine-data-store-count");
    const storeEl = el?.dataset.store === storeName ? el : null;
    if (storeEl) {
      copyToTarget();
      wbp_observe(storeEl, observerCallback);
    }
  };

  function add_intersection_observer(data) {
    const observe = function (el, src, threshold = 0) {
      if (undefined !== IntersectionObserver) {
        const onenter = (o) =>
          o.forEach((entry) => {
            if (entry.isIntersecting) entry.target.src = src;
            else entry.target.src = "";
          });
        const observer = new IntersectionObserver(onenter, { threshold });
        observer.observe(el, onenter);
      } else {
        el.src = src;
      }
    };

    const { id, src, threshold } = data;
    const el = document.getElementById(id);

    if (el) observe(el, src, threshold);
  }

  function add_toggle_sidebar() {
    const toggleEl = document.getElementById("sidebar-toggle");

    if (!toggleEl) return;

    const svgEl = toggleEl.querySelector("svg");
    const polygonEl = svgEl.querySelector("polygon");
    const root = document.querySelector("body");

    const points = {
      arrow: "8.39 0 7.61 0 7.61 7.64 0 7.64 0 8.4 8.39 8.4 8.39 0",
      plus: "8.39 7.64 8.39 0 7.61 0 7.61 7.64 0 7.64 0 8.4 7.61 8.4 7.61 16 8.39 16 8.39 8.4 16 8.4 16 7.64 8.39 7.64",
    };

    const opened = () => {
      polygonEl.setAttribute("points", points.plus);
      toggleEl.style.display = "unset";
    };

    const closed = () => {
      polygonEl.setAttribute("points", points.arrow);
      toggleEl.style.display = "unset";
    };

    const clickHandler = () => {
      root.classList.toggle("sidebar-open") ? opened() : closed();
    };

    const autoclose = () => {
      if (root.classList.contains("sidebar-open")) {
        clickHandler();
      }
    };

    toggleEl.addEventListener("click", clickHandler);

    clickHandler();
    setTimeout(autoclose, 8000);
  }

  function add_show_quote_request() {
    $(".description_tab a").on("click", function (e) {
      e.preventDefault();
      $("#quote-request-form").addClass("hidden");
    });

    $("#show-quote-request").on("click", function (e) {
      e.preventDefault();
      $("#quote-request-form").toggleClass("hidden");
    });
  }

  function add_iubenda_script() {
    (function (w, d) {
      var loader = function () {
        var s = d.createElement("script"),
          tag = d.getElementsByTagName("script")[0];
        s.src = "https://cdn.iubenda.com/iubenda.js";
        tag.parentNode.insertBefore(s, tag);
      };
      if (w.addEventListener) {
        w.addEventListener("load", loader, false);
      } else if (w.attachEvent) {
        w.attachEvent("onload", loader);
      } else {
        w.onload = loader;
      }
    })(window, document);
  }

  // add_fb_div();
  // add_image_disclaimer();
  add_iubenda_script();
  add_jet_engine_wishlist_hook("header .wishlist-target", "wishlist");
  add_toggle_sidebar();
  add_show_quote_request();
  add_intersection_observer({
    id: "solis-iframe",
    src: "https://solis-traktor.de/",
    threshold: 0.1,
  });
})(jQuery);
