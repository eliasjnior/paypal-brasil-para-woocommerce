/******/ (function(modules) { // webpackBootstrap
/******/ 	// install a JSONP callback for chunk loading
/******/ 	function webpackJsonpCallback(data) {
/******/ 		var chunkIds = data[0];
/******/ 		var moreModules = data[1];
/******/ 		var executeModules = data[2];
/******/
/******/ 		// add "moreModules" to the modules object,
/******/ 		// then flag all "chunkIds" as loaded and fire callback
/******/ 		var moduleId, chunkId, i = 0, resolves = [];
/******/ 		for(;i < chunkIds.length; i++) {
/******/ 			chunkId = chunkIds[i];
/******/ 			if(installedChunks[chunkId]) {
/******/ 				resolves.push(installedChunks[chunkId][0]);
/******/ 			}
/******/ 			installedChunks[chunkId] = 0;
/******/ 		}
/******/ 		for(moduleId in moreModules) {
/******/ 			if(Object.prototype.hasOwnProperty.call(moreModules, moduleId)) {
/******/ 				modules[moduleId] = moreModules[moduleId];
/******/ 			}
/******/ 		}
/******/ 		if(parentJsonpFunction) parentJsonpFunction(data);
/******/
/******/ 		while(resolves.length) {
/******/ 			resolves.shift()();
/******/ 		}
/******/
/******/ 		// add entry modules from loaded chunk to deferred list
/******/ 		deferredModules.push.apply(deferredModules, executeModules || []);
/******/
/******/ 		// run deferred modules when all chunks ready
/******/ 		return checkDeferredModules();
/******/ 	};
/******/ 	function checkDeferredModules() {
/******/ 		var result;
/******/ 		for(var i = 0; i < deferredModules.length; i++) {
/******/ 			var deferredModule = deferredModules[i];
/******/ 			var fulfilled = true;
/******/ 			for(var j = 1; j < deferredModule.length; j++) {
/******/ 				var depId = deferredModule[j];
/******/ 				if(installedChunks[depId] !== 0) fulfilled = false;
/******/ 			}
/******/ 			if(fulfilled) {
/******/ 				deferredModules.splice(i--, 1);
/******/ 				result = __webpack_require__(__webpack_require__.s = deferredModule[0]);
/******/ 			}
/******/ 		}
/******/
/******/ 		return result;
/******/ 	}
/******/
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// object to store loaded and loading chunks
/******/ 	// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 	// Promise = chunk loading, 0 = chunk loaded
/******/ 	var installedChunks = {
/******/ 		"main": 0
/******/ 	};
/******/
/******/ 	var deferredModules = [];
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "/dist/";
/******/
/******/ 	var jsonpArray = window["webpackJsonp"] = window["webpackJsonp"] || [];
/******/ 	var oldJsonpFunction = jsonpArray.push.bind(jsonpArray);
/******/ 	jsonpArray.push = webpackJsonpCallback;
/******/ 	jsonpArray = jsonpArray.slice();
/******/ 	for(var i = 0; i < jsonpArray.length; i++) webpackJsonpCallback(jsonpArray[i]);
/******/ 	var parentJsonpFunction = oldJsonpFunction;
/******/
/******/
/******/ 	// add entry module to deferred list
/******/ 	deferredModules.push([0,"vendors~main"]);
/******/ 	// run deferred modules when ready
/******/ 	return checkDeferredModules();
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/src/admin-options/admin-options-spb/admin-options-spb.scss":
/*!***************************************************************************!*\
  !*** ./assets/src/admin-options/admin-options-spb/admin-options-spb.scss ***!
  \***************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

eval("module.exports = __webpack_require__.p + \"css/admin-options-spb.css\";\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9hc3NldHMvc3JjL2FkbWluLW9wdGlvbnMvYWRtaW4tb3B0aW9ucy1zcGIvYWRtaW4tb3B0aW9ucy1zcGIuc2Nzcz80YzBhIl0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiJBQUFBLGlCQUFpQixxQkFBdUIiLCJmaWxlIjoiLi9hc3NldHMvc3JjL2FkbWluLW9wdGlvbnMvYWRtaW4tb3B0aW9ucy1zcGIvYWRtaW4tb3B0aW9ucy1zcGIuc2Nzcy5qcyIsInNvdXJjZXNDb250ZW50IjpbIm1vZHVsZS5leHBvcnRzID0gX193ZWJwYWNrX3B1YmxpY19wYXRoX18gKyBcImNzcy9hZG1pbi1vcHRpb25zLXNwYi5jc3NcIjsiXSwic291cmNlUm9vdCI6IiJ9\n//# sourceURL=webpack-internal:///./assets/src/admin-options/admin-options-spb/admin-options-spb.scss\n");

/***/ }),

/***/ "./assets/src/admin-options/admin-options-spb/admin-options-spb.ts":
/*!*************************************************************************!*\
  !*** ./assets/src/admin-options/admin-options-spb/admin-options-spb.ts ***!
  \*************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ \"./node_modules/vue/dist/vue.esm.js\");\n/* harmony import */ var vue_class_component__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue-class-component */ \"./node_modules/vue-class-component/dist/vue-class-component.esm.js\");\nvar __decorate = (undefined && undefined.__decorate) || function (decorators, target, key, desc) {\n    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;\n    if (typeof Reflect === \"object\" && typeof Reflect.decorate === \"function\") r = Reflect.decorate(decorators, target, key, desc);\n    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;\n    return c > 3 && r && Object.defineProperty(target, key, r), r;\n};\n\n\nlet AdminOptionsSpb = class AdminOptionsSpb extends vue__WEBPACK_IMPORTED_MODULE_0__[\"default\"] {\n    constructor() {\n        super();\n        this.enabled = '';\n        this.title = '';\n        this.mode = '';\n        this.client = { live: '', sandbox: '' };\n        this.secret = { live: '', sandbox: '' };\n        this.button = { format: '', color: '' };\n        this.shortcutEnabled = '';\n        this.referenceEnabled = '';\n        this.invoiceIdPrefix = '';\n        this.debugMode = '';\n        this.updateSettingsState = {\n            executed: false,\n            loading: false,\n            success: false,\n        };\n        this.$options.el = '#admin-options-spb';\n        this.imagesPath = paypal_payments_admin_options_plus.images_path;\n        // Remove default message.\n        // jQuery('#message.updated.inline').remove();\n    }\n    beforeMount() {\n        // @ts-ignore\n        const options = JSON.parse(this.$el.getAttribute('data-options'));\n        this.enabled = options.enabled || '';\n        this.title = options.title || '';\n        this.mode = options.mode || 'live';\n        this.client = {\n            live: options.client.live || '',\n            sandbox: options.client.sandbox || '',\n        };\n        this.secret = {\n            live: options.secret.live || '',\n            sandbox: options.secret.sandbox || '',\n        };\n        this.button = {\n            format: options.button.format || 'rect',\n            color: options.button.color || 'blue',\n        };\n        this.shortcutEnabled = options.shortcut_enabled || '';\n        this.referenceEnabled = options.reference_enabled || '';\n        this.invoiceIdPrefix = options.invoice_id_prefix || '';\n        this.debugMode = options.debug || '';\n    }\n    isLive() {\n        return this.mode === 'live';\n    }\n    isEnabled() {\n        return this.enabled === '1';\n    }\n    updateSettings() {\n        this.updateSettingsState.executed = true;\n        this.updateSettingsState.loading = true;\n        return new Promise((resolve, reject) => {\n            jQuery.post(ajaxurl, {\n                'action': 'paypal_payments_wc_settings',\n                'enable': 'yes',\n            }).done((response) => {\n                this.updateSettingsState.success = true;\n                jQuery('#message-reference-transaction-settings').remove();\n                setTimeout(() => {\n                    resolve(response);\n                }, 1000);\n            }).fail(() => {\n                this.updateSettingsState.success = false;\n                reject();\n            }).always(() => {\n                this.updateSettingsState.loading = false;\n            });\n        });\n    }\n};\nAdminOptionsSpb = __decorate([\n    Object(vue_class_component__WEBPACK_IMPORTED_MODULE_1__[\"default\"])({\n        template: paypal_payments_admin_options_plus.template,\n    })\n], AdminOptionsSpb);\n/* harmony default export */ __webpack_exports__[\"default\"] = (AdminOptionsSpb);\nnew AdminOptionsSpb();\n\n//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9hc3NldHMvc3JjL2FkbWluLW9wdGlvbnMvYWRtaW4tb3B0aW9ucy1zcGIvYWRtaW4tb3B0aW9ucy1zcGIudHM/ZjU5ZCJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiQUFBQTtBQUFBO0FBQUE7QUFBQSxrQkFBa0IsU0FBSSxJQUFJLFNBQUk7QUFDOUI7QUFDQTtBQUNBLDRDQUE0QyxRQUFRO0FBQ3BEO0FBQ0E7QUFDc0I7QUFDc0I7QUFDNUMsb0RBQW9ELDJDQUFHO0FBQ3ZEO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSx1QkFBdUI7QUFDdkIsdUJBQXVCO0FBQ3ZCLHVCQUF1QjtBQUN2QjtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsYUFBYTtBQUNiO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsaUJBQWlCO0FBQ2pCLGFBQWE7QUFDYjtBQUNBO0FBQ0EsYUFBYTtBQUNiO0FBQ0EsYUFBYTtBQUNiLFNBQVM7QUFDVDtBQUNBO0FBQ0E7QUFDQSxJQUFJLG1FQUFTO0FBQ2I7QUFDQSxLQUFLO0FBQ0w7QUFDZSw4RUFBZSxFQUFDO0FBQy9CIiwiZmlsZSI6Ii4vYXNzZXRzL3NyYy9hZG1pbi1vcHRpb25zL2FkbWluLW9wdGlvbnMtc3BiL2FkbWluLW9wdGlvbnMtc3BiLnRzLmpzIiwic291cmNlc0NvbnRlbnQiOlsidmFyIF9fZGVjb3JhdGUgPSAodGhpcyAmJiB0aGlzLl9fZGVjb3JhdGUpIHx8IGZ1bmN0aW9uIChkZWNvcmF0b3JzLCB0YXJnZXQsIGtleSwgZGVzYykge1xuICAgIHZhciBjID0gYXJndW1lbnRzLmxlbmd0aCwgciA9IGMgPCAzID8gdGFyZ2V0IDogZGVzYyA9PT0gbnVsbCA/IGRlc2MgPSBPYmplY3QuZ2V0T3duUHJvcGVydHlEZXNjcmlwdG9yKHRhcmdldCwga2V5KSA6IGRlc2MsIGQ7XG4gICAgaWYgKHR5cGVvZiBSZWZsZWN0ID09PSBcIm9iamVjdFwiICYmIHR5cGVvZiBSZWZsZWN0LmRlY29yYXRlID09PSBcImZ1bmN0aW9uXCIpIHIgPSBSZWZsZWN0LmRlY29yYXRlKGRlY29yYXRvcnMsIHRhcmdldCwga2V5LCBkZXNjKTtcbiAgICBlbHNlIGZvciAodmFyIGkgPSBkZWNvcmF0b3JzLmxlbmd0aCAtIDE7IGkgPj0gMDsgaS0tKSBpZiAoZCA9IGRlY29yYXRvcnNbaV0pIHIgPSAoYyA8IDMgPyBkKHIpIDogYyA+IDMgPyBkKHRhcmdldCwga2V5LCByKSA6IGQodGFyZ2V0LCBrZXkpKSB8fCByO1xuICAgIHJldHVybiBjID4gMyAmJiByICYmIE9iamVjdC5kZWZpbmVQcm9wZXJ0eSh0YXJnZXQsIGtleSwgciksIHI7XG59O1xuaW1wb3J0IFZ1ZSBmcm9tICd2dWUnO1xuaW1wb3J0IENvbXBvbmVudCBmcm9tIFwidnVlLWNsYXNzLWNvbXBvbmVudFwiO1xubGV0IEFkbWluT3B0aW9uc1NwYiA9IGNsYXNzIEFkbWluT3B0aW9uc1NwYiBleHRlbmRzIFZ1ZSB7XG4gICAgY29uc3RydWN0b3IoKSB7XG4gICAgICAgIHN1cGVyKCk7XG4gICAgICAgIHRoaXMuZW5hYmxlZCA9ICcnO1xuICAgICAgICB0aGlzLnRpdGxlID0gJyc7XG4gICAgICAgIHRoaXMubW9kZSA9ICcnO1xuICAgICAgICB0aGlzLmNsaWVudCA9IHsgbGl2ZTogJycsIHNhbmRib3g6ICcnIH07XG4gICAgICAgIHRoaXMuc2VjcmV0ID0geyBsaXZlOiAnJywgc2FuZGJveDogJycgfTtcbiAgICAgICAgdGhpcy5idXR0b24gPSB7IGZvcm1hdDogJycsIGNvbG9yOiAnJyB9O1xuICAgICAgICB0aGlzLnNob3J0Y3V0RW5hYmxlZCA9ICcnO1xuICAgICAgICB0aGlzLnJlZmVyZW5jZUVuYWJsZWQgPSAnJztcbiAgICAgICAgdGhpcy5pbnZvaWNlSWRQcmVmaXggPSAnJztcbiAgICAgICAgdGhpcy5kZWJ1Z01vZGUgPSAnJztcbiAgICAgICAgdGhpcy51cGRhdGVTZXR0aW5nc1N0YXRlID0ge1xuICAgICAgICAgICAgZXhlY3V0ZWQ6IGZhbHNlLFxuICAgICAgICAgICAgbG9hZGluZzogZmFsc2UsXG4gICAgICAgICAgICBzdWNjZXNzOiBmYWxzZSxcbiAgICAgICAgfTtcbiAgICAgICAgdGhpcy4kb3B0aW9ucy5lbCA9ICcjYWRtaW4tb3B0aW9ucy1zcGInO1xuICAgICAgICB0aGlzLmltYWdlc1BhdGggPSBwYXlwYWxfcGF5bWVudHNfYWRtaW5fb3B0aW9uc19wbHVzLmltYWdlc19wYXRoO1xuICAgICAgICAvLyBSZW1vdmUgZGVmYXVsdCBtZXNzYWdlLlxuICAgICAgICAvLyBqUXVlcnkoJyNtZXNzYWdlLnVwZGF0ZWQuaW5saW5lJykucmVtb3ZlKCk7XG4gICAgfVxuICAgIGJlZm9yZU1vdW50KCkge1xuICAgICAgICAvLyBAdHMtaWdub3JlXG4gICAgICAgIGNvbnN0IG9wdGlvbnMgPSBKU09OLnBhcnNlKHRoaXMuJGVsLmdldEF0dHJpYnV0ZSgnZGF0YS1vcHRpb25zJykpO1xuICAgICAgICB0aGlzLmVuYWJsZWQgPSBvcHRpb25zLmVuYWJsZWQgfHwgJyc7XG4gICAgICAgIHRoaXMudGl0bGUgPSBvcHRpb25zLnRpdGxlIHx8ICcnO1xuICAgICAgICB0aGlzLm1vZGUgPSBvcHRpb25zLm1vZGUgfHwgJ2xpdmUnO1xuICAgICAgICB0aGlzLmNsaWVudCA9IHtcbiAgICAgICAgICAgIGxpdmU6IG9wdGlvbnMuY2xpZW50LmxpdmUgfHwgJycsXG4gICAgICAgICAgICBzYW5kYm94OiBvcHRpb25zLmNsaWVudC5zYW5kYm94IHx8ICcnLFxuICAgICAgICB9O1xuICAgICAgICB0aGlzLnNlY3JldCA9IHtcbiAgICAgICAgICAgIGxpdmU6IG9wdGlvbnMuc2VjcmV0LmxpdmUgfHwgJycsXG4gICAgICAgICAgICBzYW5kYm94OiBvcHRpb25zLnNlY3JldC5zYW5kYm94IHx8ICcnLFxuICAgICAgICB9O1xuICAgICAgICB0aGlzLmJ1dHRvbiA9IHtcbiAgICAgICAgICAgIGZvcm1hdDogb3B0aW9ucy5idXR0b24uZm9ybWF0IHx8ICdyZWN0JyxcbiAgICAgICAgICAgIGNvbG9yOiBvcHRpb25zLmJ1dHRvbi5jb2xvciB8fCAnYmx1ZScsXG4gICAgICAgIH07XG4gICAgICAgIHRoaXMuc2hvcnRjdXRFbmFibGVkID0gb3B0aW9ucy5zaG9ydGN1dF9lbmFibGVkIHx8ICcnO1xuICAgICAgICB0aGlzLnJlZmVyZW5jZUVuYWJsZWQgPSBvcHRpb25zLnJlZmVyZW5jZV9lbmFibGVkIHx8ICcnO1xuICAgICAgICB0aGlzLmludm9pY2VJZFByZWZpeCA9IG9wdGlvbnMuaW52b2ljZV9pZF9wcmVmaXggfHwgJyc7XG4gICAgICAgIHRoaXMuZGVidWdNb2RlID0gb3B0aW9ucy5kZWJ1ZyB8fCAnJztcbiAgICB9XG4gICAgaXNMaXZlKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5tb2RlID09PSAnbGl2ZSc7XG4gICAgfVxuICAgIGlzRW5hYmxlZCgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZW5hYmxlZCA9PT0gJzEnO1xuICAgIH1cbiAgICB1cGRhdGVTZXR0aW5ncygpIHtcbiAgICAgICAgdGhpcy51cGRhdGVTZXR0aW5nc1N0YXRlLmV4ZWN1dGVkID0gdHJ1ZTtcbiAgICAgICAgdGhpcy51cGRhdGVTZXR0aW5nc1N0YXRlLmxvYWRpbmcgPSB0cnVlO1xuICAgICAgICByZXR1cm4gbmV3IFByb21pc2UoKHJlc29sdmUsIHJlamVjdCkgPT4ge1xuICAgICAgICAgICAgalF1ZXJ5LnBvc3QoYWpheHVybCwge1xuICAgICAgICAgICAgICAgICdhY3Rpb24nOiAncGF5cGFsX3BheW1lbnRzX3djX3NldHRpbmdzJyxcbiAgICAgICAgICAgICAgICAnZW5hYmxlJzogJ3llcycsXG4gICAgICAgICAgICB9KS5kb25lKChyZXNwb25zZSkgPT4ge1xuICAgICAgICAgICAgICAgIHRoaXMudXBkYXRlU2V0dGluZ3NTdGF0ZS5zdWNjZXNzID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICBqUXVlcnkoJyNtZXNzYWdlLXJlZmVyZW5jZS10cmFuc2FjdGlvbi1zZXR0aW5ncycpLnJlbW92ZSgpO1xuICAgICAgICAgICAgICAgIHNldFRpbWVvdXQoKCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICByZXNvbHZlKHJlc3BvbnNlKTtcbiAgICAgICAgICAgICAgICB9LCAxMDAwKTtcbiAgICAgICAgICAgIH0pLmZhaWwoKCkgPT4ge1xuICAgICAgICAgICAgICAgIHRoaXMudXBkYXRlU2V0dGluZ3NTdGF0ZS5zdWNjZXNzID0gZmFsc2U7XG4gICAgICAgICAgICAgICAgcmVqZWN0KCk7XG4gICAgICAgICAgICB9KS5hbHdheXMoKCkgPT4ge1xuICAgICAgICAgICAgICAgIHRoaXMudXBkYXRlU2V0dGluZ3NTdGF0ZS5sb2FkaW5nID0gZmFsc2U7XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfSk7XG4gICAgfVxufTtcbkFkbWluT3B0aW9uc1NwYiA9IF9fZGVjb3JhdGUoW1xuICAgIENvbXBvbmVudCh7XG4gICAgICAgIHRlbXBsYXRlOiBwYXlwYWxfcGF5bWVudHNfYWRtaW5fb3B0aW9uc19wbHVzLnRlbXBsYXRlLFxuICAgIH0pXG5dLCBBZG1pbk9wdGlvbnNTcGIpO1xuZXhwb3J0IGRlZmF1bHQgQWRtaW5PcHRpb25zU3BiO1xubmV3IEFkbWluT3B0aW9uc1NwYigpO1xuIl0sInNvdXJjZVJvb3QiOiIifQ==\n//# sourceURL=webpack-internal:///./assets/src/admin-options/admin-options-spb/admin-options-spb.ts\n");

/***/ }),

/***/ 0:
/*!***************************************************************************************************************************************************!*\
  !*** multi ./assets/src/admin-options/admin-options-spb/admin-options-spb.ts ./assets/src/admin-options/admin-options-spb/admin-options-spb.scss ***!
  \***************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

__webpack_require__(/*! ./assets/src/admin-options/admin-options-spb/admin-options-spb.ts */"./assets/src/admin-options/admin-options-spb/admin-options-spb.ts");
module.exports = __webpack_require__(/*! ./assets/src/admin-options/admin-options-spb/admin-options-spb.scss */"./assets/src/admin-options/admin-options-spb/admin-options-spb.scss");


/***/ })

/******/ });