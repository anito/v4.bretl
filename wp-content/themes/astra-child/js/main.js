const observe = (function () {
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
  var disclaimer =
    "Abbildung kann ähnlich sein. Änderungen und Irrtümer vorbehalten. Mögliches Zubehör auf Bildern ist nicht Teil des Angebots.";
  var add_image_disclaimer = function () {
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
      observe(storeEl, observerCallback);
    }
  };

  // add_fb_div();
  // add_image_disclaimer();
  add_jet_engine_wishlist_hook(".wishlist-target [class*=title]", "wishlist");
})(jQuery);
