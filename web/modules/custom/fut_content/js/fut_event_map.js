(function ($, Drupal, drupalSettings) {

  L.custom = {

    init: function (obj) {

      // Map Setting available in:
      // drupalSettings.fut_content.event_map

      window.map = L.map(obj, {
        center: drupalSettings.fut_content.event_map.center,
        zoom: 15,
        background: "osmec"
      });

      // Add Event marker.
      map.markers.add(drupalSettings.fut_content.event_map.featureCollection, {
        color: "blue"
      });

      $wt.next(obj);

    }

  };
})(jQuery, Drupal, drupalSettings);