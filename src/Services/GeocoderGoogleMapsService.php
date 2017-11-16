<?php

namespace Drupal\geofield_map\Services;

/**
 * Class GeocoderGoogleMapsService.
 */
class GeocoderGoogleMapsService extends GeocoderGoogleMaps {

  /**
   * {@inheritdoc}
   */
  public function geocoderGeocode($address, array $plugins, array $plugin_options = []) {
    $results = [];
    return $results;
  }

}
