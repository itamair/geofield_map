<?php

namespace Drupal\geofield_map\Services;

/**
 * Interface GeocoderServiceInterface.
 */
interface GeocoderServiceInterface {

  /**
   * Geocode with Google Maps or Geocoder Module functionalities.
   *
   * @param string $address
   *   The string to geocode.
   * @param string[] $plugins
   *   A list of Geocoders plugins identifiers to use.
   * @param array $plugin_options
   *   (optional) An associative array with plugin options, keyed plugin by the
   *   plugin id. Defaults to an empty array.
   *
   * @return array
   *   Return Results Array.
   */
  public function geocode($address, array $plugins, array $plugin_options = []);

  /**
   * Reverse Geocode with Google Maps or Geocoder Module functionalities.
   *
   * @param string $lat
   *   The Latitude in decimal format string to reverse geocode.
   * @param string $lng
   *   The Longitude in decimal format string to reverse geocode.
   * @param string[] $plugins
   *   A list of Geocoders plugins identifiers to use.
   * @param array $plugin_options
   *   (optional) An associative array with plugin options, keyed plugin by the
   *   plugin id. Defaults to an empty array.
   *
   * @return array
   *   Return Results Array.
   */
  public function reverseGeocode($lat, $lng, array $plugins, array $plugin_options = []);

  /**
   * Get the Selected Geofield Map Formatter.
   *
   * @return string
   *   The Geofield Map Formatter machine name.
   */
  public function getGeofieldMapFormatter();

  /**
   * Output a Geocoder Setup Message in the Geofield Map Widget Setup.
   *
   * @return array
   *   Return Output Message Array.
   */
  public function widgetSetupDebugMessage();

  /**
   * Output a Geocoder Setup Message in the Geofield Map Formatter Setup.
   *
   * @return array
   *   Return Output Message Array.
   */
  public function formatterSetupDebugMessage();

  /**
   * Output a Geocoder Setup Message in the Geofield Map Widget Element.
   *
   * @return array
   *   Return Output Message Array.
   */
  public function widgetElementDebugMessage();

  /**
   * Outputs the Description for the Geofield Map Widget Element.
   *
   * @return string
   *   The output description string.
   */
  public function widgetElementDescription();

  /**
   * Define if the Geocode Address / Functionality is able to work.
   *
   * @return bool
   *   The output description string.
   */
  public function geocodeAddressElementCanWork();

  /**
   * Output the Description message to accompany the Gmap API key field/element.
   *
   * @return bool
   *   The output description string.
   */
  public function gmapApiKeyElementDescription();

}
