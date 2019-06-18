(function ($, Drupal) {

  Drupal.AjaxCommands.prototype.render = function (ajax, response, status) {

    $wt.render(response.selector, (JSON.parse(response.content)) );

  }
})(jQuery, Drupal);