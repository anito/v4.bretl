(function ($) {
	var add_show_pwd = function () {
		$(".wp-pwd button").on("click", function () {
			const parent = $(this).parent();
			$(".dashicons", parent).toggleClass([
				"dashicons-visibility",
				"dashicons-hidden",
			]);
			$(".dashicons", parent).hasClass("dashicons-visibility")
				? $("input", parent).attr("type", "password")
				: $("input", parent).attr("type", "text");
		});
	};

	add_show_pwd();
})(jQuery);
