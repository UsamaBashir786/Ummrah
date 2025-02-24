/*! ------------------------------------------------
 * ------------------------------------------------
 * Table of Contents
 * ------------------------------------------------
 *  1. Template Backgrounds
 *  2. Fonts
 *  3. Base CSS Styles
 *  4. Loading Animation
 *  5. Typography
 *  6. Buttons & Controls
 *  7. Forms and Forms Reply Groups
 *  8. Animated Backgrounds
 *  9. Header
 *  10. Main Section
 *  11. Menu
 *  12. Socials
 *  13. Countdown
 *  14. Swiper Slider
 *  15. Popup Dialogs
 *  16. Inner Sections
 *  17. Skillbars
 *  18. Features
 *  19. Partners
 *  20. Inner Video
 *  21. Contact Data
 *  22. Footer
 *  23. Gallery
 * ------------------------------------------------
 * Table of Contents End
 * ------------------------------------------------ */
/* ------------------------------------------------*/
/* Template Backgrounds Start */
/* ------------------------------------------------*/
.media-image-1 {
  background-image: url("../img/backgrounds/1440x900-bg-main-1.webp");
}

.media-image-2 {
  background-image: url("../img/backgrounds/1440x900-bg-main-2.webp");
}

.media-image-split-1 {
  background-image: url("../img/backgrounds/1200x1400-bg-main-1.webp");
}

.media-image-split-2 {
  background-image: url("../img/backgrounds/1200x1400-bg-main-2.webp");
}

.media-image-fullscreen-1 {
  background-image: url("../img/backgrounds/1920x1280-bg-main-1.webp");
}

.media-image-fullscreen-2 {
  background-image: url("../img/backgrounds/1920x1280-bg-main-2.webp");
}

.swiper-slide-image-1 {
  background-image: url("../img/backgrounds/1440x900-main-slide-1.webp");
}

.swiper-slide-image-2 {
  background-image: url("../img/backgrounds/1440x900-main-slide-2.webp");
}

.swiper-slide-image-half-1 {
  background-image: url("../img/backgrounds/1200x1400-main-slide-1.webp");
}

.swiper-slide-image-half-2 {
  background-image: url("../img/backgrounds/1200x1400-main-slide-2.webp");
}

.media-services {
  background-image: url("../img/backgrounds/1920x1280-bg-services.webp");
}

.inner-video {
  background-image: url("../img/backgrounds/1920x1280-bg-services.webp");
}

.menu-image-1 {
  background-image: url("../img/backgrounds/1200x1500-bg-menu-2.webp");
}

/* ------------------------------------------------*/
/* Template Backgrounds End */
/* ------------------------------------------------*/

/* ------------------------------------------------*/
/* Fonts Start */
/* ------------------------------------------------*/
@font-face {
  font-family: "OpenSans";
  font-style: normal;
  font-weight: 300;
  src: url("../fonts/OpenSans-Light/OpenSans-Lightd41d.eot?#iefix") format("embedded-opentype"), url("../fonts/OpenSans-Light/OpenSans-Light.woff") format("woff"), url("../fonts/OpenSans-Light/OpenSans-Light.ttf") format("truetype"), url("../fonts/OpenSans-Light/OpenSans-Light.svg#OpenSans") format("svg");
}
@font-face {
  font-family: "OpenSans";
  font-style: italic;
  font-weight: 300;
  src: url("../fonts/OpenSans-LightItalic/OpenSans-LightItalicd41d.eot?#iefix") format("embedded-opentype"), url("../fonts/OpenSans-LightItalic/OpenSans-LightItalic.woff") format("woff"), url("../fonts/OpenSans-LightItalic/OpenSans-LightItalic.ttf") format("truetype"), url("../fonts/OpenSans-LightItalic/OpenSans-LightItalic.svg#OpenSans") format("svg");
}
@font-face {
  font-family: "OpenSans";
  font-style: normal;
  font-weight: 400;
  src: url("../fonts/OpenSans-Regular/OpenSans-Regulard41d.eot?#iefix") format("embedded-opentype"), url("../fonts/OpenSans-Regular/OpenSans-Regular.woff") format("woff"), url("../fonts/OpenSans-Regular/OpenSans-Regular.ttf") format("truetype"), url("../fonts/OpenSans-Regular/OpenSans-Regular.svg#OpenSans") format("svg");
}
@font-face {
  font-family: "OpenSans";
  font-style: italic;
  font-weight: 400;
  src: url("../fonts/OpenSans-Italic/OpenSans-Italicd41d.eot?#iefix") format("embedded-opentype"), url("../fonts/OpenSans-Italic/OpenSans-Italic.woff") format("woff"), url("../fonts/OpenSans-Italic/OpenSans-Italic.ttf") format("truetype"), url("../fonts/OpenSans-Italic/OpenSans-Italic.svg#OpenSans") format("svg");
}
@font-face {
  font-family: "OpenSans";
  font-style: normal;
  font-weight: 600;
  src: url("../fonts/OpenSans-SemiBold/OpenSans-SemiBoldd41d.eot?#iefix") format("embedded-opentype"), url("../fonts/OpenSans-SemiBold/OpenSans-SemiBold.woff") format("woff"), url("../fonts/OpenSans-SemiBold/OpenSans-SemiBold.ttf") format("truetype"), url("../fonts/OpenSans-SemiBold/OpenSans-SemiBold.svg#OpenSans") format("svg");
}
@font-face {
  font-family: "OldStandardTT";
  font-style: normal;
  font-weight: 400;
  src: url("../fonts/OldStandardTT-Regular/OldStandardTT-Regulard41d.eot?#iefix") format("embedded-opentype"), url("../fonts/OldStandardTT-Regular/OldStandardTT-Regular.woff") format("woff"), url("../fonts/OldStandardTT-Regular/OldStandardTT-Regular.ttf") format("truetype"), url("../fonts/OldStandardTT-Regular/OldStandardTT-Regular.svg#OldStandardTT") format("svg");
}
/* ------------------------------------------------*/
/* Fonts End */
/* ------------------------------------------------*/

/* ------------------------------------------------*/
/* Base CSS Styles Start */
/* ------------------------------------------------*/
*, *::before, *::after {
  -webkit-box-sizing: border-box;
     -moz-box-sizing: border-box;
          box-sizing: border-box;
}

button:active, button:focus {
  outline: none !important;
}

button::-moz-focus-inner {
  border: 0 !important;
}

input::-moz-focus-inner {
  border: 0 !important;
}

::-moz-selection {
  background-color: #292929;
  color: #ffffff;
  text-shadow: none;
}

::selection {
  background-color: #292929;
  color: #ffffff;
  text-shadow: none;
}

::-webkit-scrollbar {
  display: none;
  width: 5px;
  background: #000000;
}
@media only screen and (min-width: 768px) {
  ::-webkit-scrollbar {
    display: block;
  }
}

::-webkit-scrollbar-track {
  border-radius: 20px;
  background-color: #000000;
}

::-webkit-scrollbar-thumb {
  background-color: #1f1f1f;
  border-radius: 10px;
}

html {
  font-family: sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

html,
body {
  width: 100%;
  height: 100%;
}

body {
  position: relative;
  min-width: 320px;
  overflow-x: hidden !important;
  overflow-y: auto;
  font: normal 400 1.6rem/1.7 "OpenSans", sans-serif;
  /* color: #444444; */
  /* background-color: #111111; */
}

section {
  position: relative;
  min-width: 320px;
  outline: none;
  -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
}

a {
  text-decoration: none;
  outline: none;
  -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
  -webkit-transition: all 0.5s ease-in-out;
  -o-transition: all 0.5s ease-in-out;
  -moz-transition: all 0.5s ease-in-out;
  transition: all 0.5s ease-in-out;
}

img {
  display: block;
  width: 100%;
  height: auto;
}

/* ------------------------------------------------*/
/* Base CSS Styles End */
/* ------------------------------------------------*/