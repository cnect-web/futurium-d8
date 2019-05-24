(function ($, Drupal, drupalSettings) {

  L.custom = {

    init: function (obj) {

      // Map Setting available in:
      // drupalSettings.fut_content.event_map

      var map = L.map(obj, {
        center: drupalSettings.fut_content.event_map[$(obj).parent().attr('data-event-id')].center,
        zoom: 15,
        background: "osmec"
      });

      $('.event-map').each(function () {

        let event_id = $(this).attr('data-event-id');
        // var container = L.DomUtil.get(obj); if(container != null){ container._leaflet_id = null; }

        this.map = map;

        // Add Event marker.
        this.map.markers.add(drupalSettings.fut_content.event_map[event_id].featureCollection, {
          color: "blue"
        });
        $wt.next(obj);

      });

    }

  };
})(jQuery, Drupal, drupalSettings);