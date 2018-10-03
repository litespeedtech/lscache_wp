/**
 * Lazyload init js
 *
 * @author LiteSpeed
 * @since 1.4
 *
 */

var myLazyLoad = new LazyLoad({
  elements_selector: "[data-lazyloaded]"
});

//debug in console
/*(function () {
  function logElementEvent(eventName, element) {
    console.log(Date.now(), eventName, element.getAttribute('data-src'));
  }
  ll = new LazyLoad({
    elements_selector: '[data-lazyloaded]',
    callback_enter: function (element) {
      logElementEvent("ENTERED", element);
    },
    callback_load: function (element) {
      logElementEvent("LOADED", element);
    },
    callback_set: function (element) {
      logElementEvent("SET", element);
    },
    callback_error: function (element) {
      logElementEvent("ERROR", element);
      //element.src = "https://placeholdit.imgix.net/~text?txtsize=21&txt=Fallback%20image&w=220&h=280";
    }
  });
}());*/
