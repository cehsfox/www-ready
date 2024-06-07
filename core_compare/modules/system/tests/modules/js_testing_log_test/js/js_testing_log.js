/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function (Drupal) {
  if (typeof console !== 'undefined' && console.warn) {
    var originalWarnFunction = console.warn;

    console.warn = function (warning) {
      var warnings = JSON.parse(sessionStorage.getItem('js_testing_log_test.warnings') || JSON.stringify([]));
      warnings.push(warning);
      sessionStorage.setItem('js_testing_log_test.warnings', JSON.stringify(warnings));
      originalWarnFunction(warning);
    };

    var originalThrowFunction = Drupal.throwError;

    Drupal.throwError = function (error) {
      var errors = JSON.parse(sessionStorage.getItem('js_testing_log_test.errors') || JSON.stringify([]));
      errors.push(error.stack);
      sessionStorage.setItem('js_testing_log_test.errors', JSON.stringify(errors));
      originalThrowFunction(error);
    };
  }
})(Drupal);