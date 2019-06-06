(function ($, Drupal, drupalSettings) {

  L.custom = {

    init: function (obj) {

      // Map Setting available in:
      // drupalSettings.fut_content.event_map

      var map = L.map(obj, {
        center: drupalSettings.webtools.ec_map[$(obj).parent().attr('data-map-id')].center,
        zoom: 15,
        background: "osmec"
      });

      $('.ec-map').each(function () {


        var map_identifier = $(this).attr('data-map-id');
        var markers = L.wt.markers(drupalSettings.webtools.ec_map[map_identifier].featureCollection, {color:"blue"} ).addTo(map);



        markers.fitBounds();
      });


      // $('.ec-map').each(function () {

        // this.map = map;


        // drupalSettings.fut_content.ec_map.forEach(function (marker) {
        // Object.keys(drupalSettings.fut_content.ec_map).forEach(function(key) {
        //   // map.markers.add(drupalSettings.fut_content.ec_map[key].featureCollection, {
        //   //   color: "blue"
        //   // });
        //
        //  markers( drupalSettings.fut_content.ec_map[key].featureCollection , {
        //     color: "blue"
        //   }).addTo(map);
        //
        // });

        // markers.fitBounds();



        // Add Event marker.
        // this.map.markers.add(drupalSettings.fut_content.ec_map[map_identifier].featureCollection, {
        //   color: "blue"
        // });
        $wt.next(obj);

      // });

    }

  };
})(jQuery, Drupal, drupalSettings);