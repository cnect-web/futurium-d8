(function ($, Drupal) {
    $('#edit-country').change(function (event) {
        $("input[name='city']").val('');
    });
})(jQuery, Drupal);