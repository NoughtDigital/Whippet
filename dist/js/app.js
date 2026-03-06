/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./resources/js/app.js":
/*!*****************************!*\
  !*** ./resources/js/app.js ***!
  \*****************************/
/***/ (() => {

/*jslint browser: true*/
//Revision: 2018.02.13

var whippet = {
  menu: {
    init: function init() {
      "use strict";

      var menuItem = document.getElementById("wp-admin-bar-whippet");
      if (menuItem) {
        menuItem.addEventListener("click", function () {
          var panel = document.getElementById("whippet");
          if (panel) {
            if (panel.style.display === "none") {
              panel.style.display = "block";
            } else {
              panel.style.display = "none";
            }
          }
        });
      }
    }
  },
  UI: {
    init: function init() {
      "use strict";

      var elements;
      elements = document.querySelectorAll("#whippet input[type=checkbox]");
      Array.prototype.forEach.call(elements, function (el) {
        el.addEventListener("change", function () {
          document.whippetChanged = true;
        });
      });
      elements = document.querySelectorAll("#whippet select");
      Array.prototype.forEach.call(elements, function (el) {
        el.addEventListener("change", function () {
          //look for wrappers
          var tr;
          if (this.parentNode.tagName.toLowerCase() === "td") {
            tr = this.parentNode.parentNode;
          } else {
            tr = this.parentNode.parentNode.parentNode; //probably wrapper around select
          }
          var sectionCond = tr.querySelector(".g-cond");
          var sectionExcp = tr.querySelector(".g-excp");
          var sectionRegex = tr.querySelector(".g-regex");
          var checkedRadio = tr.querySelector(".g-cond input[type=radio]:checked");
          if (this.value === "e") {
            /**
             * State = Enable
             */

            //ukrywanie sekcji "Where" i "Exceptions"
            whippet.helper.addClass("g-disabled", sectionCond);
            whippet.helper.addClass("g-disabled", sectionExcp);
            if (sectionRegex) {
              whippet.helper.addClass("g-disabled", sectionRegex);
            }
            if (checkedRadio) {
              //odznaczanie stanu "Where"
              checkedRadio.checked = false;

              //odznaczanie "Exceptions"
              var tr2 = this.parentNode.parentNode.parentNode;
              var section = tr2.querySelector(".g-excp");
              whippet.helper.clearSelected(section);
            }
          } else {
            /**
             * State = Disable
             */

            //pokazywanie sekcji "where"
            whippet.helper.removeClass("g-disabled", sectionCond);

            //jeśli zaznaczono "Everywhere" pokaż tą sekcję
            if (checkedRadio && checkedRadio.value === "everywhere") {
              if (sectionRegex) {
                whippet.helper.addClass("g-disabled", sectionRegex);
              }
              whippet.helper.removeClass("g-disabled", sectionExcp);
            } else if (checkedRadio && checkedRadio.value === "regex") {
              whippet.helper.addClass("g-disabled", sectionExcp);
              if (sectionRegex) {
                whippet.helper.removeClass("g-disabled", sectionRegex);
              }
            }
          }
          document.whippetChanged = true;
        });
      });
      elements = document.querySelectorAll("#whippet .g-cond input[type=radio]");
      Array.prototype.forEach.call(elements, function (el) {
        el.addEventListener("change", function () {
          var tr = this.parentNode.parentNode.parentNode;
          var sectionExcp = tr.querySelector(".g-excp");
          var sectionRegex = tr.querySelector(".g-regex");
          if (this.value === "here") {
            whippet.helper.addClass("g-disabled", sectionExcp);
            if (sectionRegex) {
              whippet.helper.addClass("g-disabled", sectionRegex);
            }
            whippet.helper.clearSelected(sectionExcp);
            if (sectionRegex) {
              whippet.helper.clearSelected(sectionRegex);
            }
          } else if (this.value === "everywhere") {
            if (sectionRegex) {
              whippet.helper.addClass("g-disabled", sectionRegex);
            }
            whippet.helper.removeClass("g-disabled", sectionExcp);
          } else if (this.value === "regex") {
            whippet.helper.addClass("g-disabled", sectionExcp);
            if (sectionRegex) {
              whippet.helper.removeClass("g-disabled", sectionRegex);
            }
          }
          document.whippetChanged = true;
        });
      });
      var submitButton = document.getElementById("submit-whippet");
      if (submitButton) {
        submitButton.addEventListener("click", function () {
          document.whippetChanged = false;
        });
      }
    },
    protection: function protection() {
      "use strict";

      window.addEventListener("beforeunload", function (e) {
        if (document.whippetChanged) {
          var confirmationMessage = "It looks like you have been editing configuration and tried to leave page without saving. Press cancel to stay on page.";
          (e || window.event).returnValue = confirmationMessage;
          return confirmationMessage;
        }
      });
    }
  },
  helper: {
    addClass: function addClass(className, el) {
      "use strict";

      if (el.classList) {
        el.classList.add(className);
      } else {
        el.className += " " + className;
      }
    },
    removeClass: function removeClass(className, el) {
      "use strict";

      if (el.classList) {
        el.classList.remove(className);
      } else {
        el.className = el.className.replace(new RegExp("(^|\\b)" + className.split(" ").join("|") + "(\\b|$)", "gi"), " ");
      }
    },
    clearSelected: function clearSelected(el) {
      "use strict";

      var checkboxes = el.querySelectorAll("input[type=checkbox]");
      Array.prototype.forEach.call(checkboxes, function (input) {
        input.checked = false;
      });
      var textareas = el.querySelectorAll("textarea");
      Array.prototype.forEach.call(textareas, function (input) {
        input.value = "";
      });
    }
  },
  ready: function ready(fn) {
    "use strict";

    if (document.readyState !== "loading") {
      fn();
    } else {
      document.addEventListener("DOMContentLoaded", fn);
    }
  },
  init: function init() {
    "use strict";

    whippet.ready(whippet.menu.init);
    whippet.ready(whippet.UI.init);
    whippet.ready(whippet.UI.protection);
  }
};
setTimeout(function () {
  whippet.init();
}, 100);

/***/ }),

/***/ "./resources/scss/admin.scss":
/*!***********************************!*\
  !*** ./resources/scss/admin.scss ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./resources/scss/style-whippet.scss":
/*!*******************************************!*\
  !*** ./resources/scss/style-whippet.scss ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./resources/scss/style.scss":
/*!***********************************!*\
  !*** ./resources/scss/style.scss ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var [chunkIds, fn, priority] = deferred[i];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"/js/app": 0,
/******/ 			"css/admin": 0,
/******/ 			"css/style": 0,
/******/ 			"css/style-whippet": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var [chunkIds, moreModules, runtime] = data;
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = self["webpackChunk"] = self["webpackChunk"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	__webpack_require__.O(undefined, ["css/admin","css/style","css/style-whippet"], () => (__webpack_require__("./resources/js/app.js")))
/******/ 	__webpack_require__.O(undefined, ["css/admin","css/style","css/style-whippet"], () => (__webpack_require__("./resources/scss/style-whippet.scss")))
/******/ 	__webpack_require__.O(undefined, ["css/admin","css/style","css/style-whippet"], () => (__webpack_require__("./resources/scss/style.scss")))
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["css/admin","css/style","css/style-whippet"], () => (__webpack_require__("./resources/scss/admin.scss")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=app.js.map