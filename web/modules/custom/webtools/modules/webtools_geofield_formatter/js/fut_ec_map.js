(function ($, Drupal, drupalSettings) {

  L.custom = {

    init: function (obj) {

      // Map Setting available in:
      // drupalSettings.fut_content.event_map

      var map = L.map(obj, {
        center: drupalSettings.webtools.ec_map[$(obj).parent().attr('data-map-id')].center,
        minZoom: drupalSettings.webtools.ec_map[$(obj).parent().attr('data-map-id')].zoom.min_zoom,
        maxZoom: drupalSettings.webtools.ec_map[$(obj).parent().attr('data-map-id')].zoom.max_zoom,
        zoom: drupalSettings.webtools.ec_map[$(obj).parent().attr('data-map-id')].zoom.initial_zoom,
        height: drupalSettings.webtools.ec_map[$(obj).parent().attr('data-map-id')].height,
        background: drupalSettings.webtools.ec_map[$(obj).parent().attr('data-map-id')].tile,
      });

      $('.ec-map').each(function () {

        var map_identifier = $(this).attr('data-map-id');

        this.map = map;

        // Add Event marker.
        this.map.markers.add(drupalSettings.webtools.ec_map[map_identifier].featureCollection, {
          color: "blue"
        });
        $wt.next(obj);

      });

    }

  };
})(jQuery, Drupal, drupalSettings);