(function ($, Drupal, drupalSettings) {

  L.custom = {

    init: function (obj) {

      // Map Setting available in:
      // drupalSettings.fut_content.event_map

      var map = L.map(obj, {
        center: drupalSettings.fut_content.ec_map[$(obj).parent().attr('data-map-id')].center,
        zoom: 15,
        background: "osmec"
      });

      $('.ec-map').each(function () {

        var map_identifier = $(this).attr('data-map-id');

        this.map = map;

        // Add Event marker.
        this.map.markers.add(drupalSettings.fut_content.ec_map[map_identifier].featureCollection, {
          color: "blue"
        });
        $wt.next(obj);

      });

    }

  };
})(jQuery, Drupal, drupalSettings);