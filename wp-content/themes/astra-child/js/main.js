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

      console.log("over");
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

  // add_fb_div();
  // add_image_disclaimer();
  // add_animate_scroll();
  add_animate_css_listeners();
})(jQuery);
