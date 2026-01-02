/**
 * Tailwind CSS V4 configuration for the Whippet plugin.
 * 
 * Theme customization is done via @theme directive in CSS files.
 * This file handles content paths and plugin-specific configuration.
 *
 * @type {import('tailwindcss').Config}
 */
module.exports = {
  content: [
    "./resources/**/*.{php,js,jsx,ts,tsx,vue,scss,css}",
    "./inc/**/*.php",
    "./whippet.php",
  ],
  // Important selector to scope styles to plugin
  important: "#whippet",
  corePlugins: {
    preflight: false,
  },
};
