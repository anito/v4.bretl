const mix = require("laravel-mix");

mix
  .postCss("resources/css/theme.css", "css", [
    require("tailwindcss"),
    require("postcss-nested"),
  ])
  .options({
    processCssUrls: false,
  });