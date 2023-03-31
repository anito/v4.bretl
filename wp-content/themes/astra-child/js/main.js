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

  // animation css listeners
  var add_animate_css_listeners = () => {
    let icon = ".get-quotes-icon";
    let trigger = ".get-quotes";

    $(".get-quotes").on("mouseover", (e) => {
      e.stopPropagation();
      if (!$(icon).hasClass("over")) {
        $(icon).addClass("over");
        animateCSS(
          icon,
          "rotateIn",
          {
            repeat: "1",
            duration: 3,
          },
          () => {
            $(icon).css({ opacity: 1 });
          }
        );
      }
      return false;
    });
    $(trigger).on("mouseleave", (e) => {
      e.stopPropagation();

      console.log("out");
      $(icon).removeClass("over");
      animateCSS(
        icon,
        "rotateOut",
        {
          repeat: "1",
          duration: 1,
        },
        () => $(icon).css({ opacity: 0 })
      );
      return false;
    });
  };

  // Copy and observe an elements wishlist count
  var add_wishlist_hook = (targetSelector, storeName) => {
    const className = ".jet-engine-data-store-count";
    function getElement(name) {
      const el = document.querySelector(name);
      return el?.dataset.store === storeName ? el : null;
    }

    function callback(mutationList) {
      for (const mutation of mutationList) {
        if (mutation.type === "childList") {
          if (mutation.addedNodes.length) {
            console.log("A node has been added.", mutation.addedNodes[0]);
          }
          if (mutation.removedNodes.length) {
            console.log("A node has been removed.", mutation.removedNodes[0]);
          }
        } else if (mutation.type === "attributes") {
          console.log(`The ${mutation.attributeName} attribute was modified.`);
        }
      }
      copy();
    }

    function copy() {
      const targetEls = document.querySelectorAll(targetSelector);
      targetEls.forEach((el) => {
        let spanEl = el.querySelector(".wishlist-widget");
        if (!spanEl) {
          spanEl = document.createElement("span");
          spanEl.classList.add("wishlist-widget");
          el.append(spanEl);
        }
        if (storeEl.innerHTML !== "0") {
          spanEl.innerHTML = storeEl.innerHTML;
        } else {
          spanEl.remove();
        }
      });
    }

    const storeEl = getElement(className);
    if (storeEl) {
      copy();
      observe(storeEl, callback);
    }
  };

  // add_fb_div();
  // add_image_disclaimer();
  // add_animate_scroll();
  add_animate_css_listeners();
  add_wishlist_hook(".wishlist-target [class*=title]", "wishlist");
})(jQuery);
