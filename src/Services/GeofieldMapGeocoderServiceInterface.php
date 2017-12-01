<?php

namespace Drupal\geofield_map\Services;

/**
 * Interface GeofieldMapGeocoderServiceInterface.
 */
interface GeofieldMapGeocoderServiceInterface {

  /**
   * Performs a Geocode with Geocoder Module Services functionalities.
   *
   * @param string $address
   *   The string to geocode.
   * @param string[] $plugins
   *   A list of plugin identifiers to use.
   * @param array $plugin_options
   *   (optional) An associative array with plugin options, keyed plugin by the
   *   plugin id. Defaults to an empty array.
   *
   * @return array
   *   Return Results Array.
   */
  public function geocoderGeocode($address, array $plugins, array $plugin_options = []);

  /**
   * Performs a Geocode with Google Maps Service functionalities.
   *
   * @param string $address
   *   The string to geocode.
   * @param string $apiKey
   *   The Google Maps Api Key.
   * @param array $options
   *   Additional Google Maps Geocoder Options.
   *
   * @return array
   *   Return Results Array.
   */
  public function googleMapsGeocode($address, $apiKey, array $options);

}
