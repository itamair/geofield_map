<?php

namespace Drupal\geofield_map\Services;

use Drupal\Component\Serialization\Json;

/**
 * Class GeocoderServiceGoogleMaps.
 */
class GeocoderServiceGoogleMaps extends GeocoderServiceAbstract {

  /**
   * Add additional url options to the request.
   *
   * @param string $url
   *   The url to alter/integrate.
   * @param array $plugin_options
   *   The Plugins options.
   */
  private function addUrlParameters(&$url, array $plugin_options) {
    foreach ($plugin_options['googlemaps'] as $k => $option) {
      if ($k != 'apiKey') {
        $url .= '&' . rawurlencode($k) . '=' . rawurlencode($option);
      }
      if ($k == 'locale') {
        $url .= '&language=' . rawurlencode($option);
      }
    }
  }

  /**
   * Get the list of Geocoders Plugins.
   *
   * @return string
   *   The Geocoders Plugin List in a string format.
   */
  protected function getGeocodersPlugins() {
    return !empty($this->config->get('geofield_map.settings')->get('geocoder.plugins')) && $this->config->get('geofield_map.settings')->get('geocoder.plugins') == ['googlemaps'] ? 'Google Maps' : $this->t('No Geocoder enabled');
  }

  /**
   * Output a Message for the Empty Gmap API Key.
   *
   * @param string $context
   *   The context identifier.
   *
   * @return array
   *   The output render array.
   */
  protected function notEmptyGmapApiKeyMessage($context = 'any') {

    $gmap_api_key_link = $this->currentUser->hasPermission('configure geofield_map')
      ? $this->link->generate($this->gmapApiKey, $this->geofieldMapSettingsPageUrl)
      : $this->gmapApiKey;

    $output_message = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $context !== 'debug' ? $this->t('<strong>Gmap Api Key:</strong> @gmap_api_key_link', [
        '@gmap_api_key_link' => $gmap_api_key_link,
      ]) : 'Gmap Api Key: ' . $this->gmapApiKey,
      '#weight' => -5,
    ];

    if ($context !== 'debug') {
      $output_message['description'] = $this->gmapApiKeyElementDescription();
      $output_message['description']['#weight'] = -4;
    }

    return $output_message;

  }

  /**
   * {@inheritdoc}
   */
  public function geocode($address, array $plugins, array $plugin_options = []) {

    // Use Http Secure as default, if not forcibly disabled.
    // Nowadays Google Geocoding Api will work only in https protocol.
    $web_protocol = empty($plugin_options['googlemaps']['useSsl']) ? 'http:' : 'https:';

    // Build the Google Maps Geocoding request, with the apiKey.
    $url = $web_protocol . '//maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($address) . '&key=' . $this->gmapApiKey;

    // Add additional url options to the request (besides the apiKey already in)
    $this->addUrlParameters($url, $plugin_options);

    /* @var \GuzzleHttp\Client $client */
    $client = $this->httpClient;
    return Json::decode($client->get($url)->getBody()->getContents());
  }

  /**
   * {@inheritdoc}
   */
  public function reverseGeocode($lat, $lng, array $plugins, array $plugin_options = []) {

    $lat_lng = $lat . ',' . $lng;

    // Use Http Secure as default, if not forcibly disabled.
    // Nowadays Google Geocoding Api will work only in https protocol.
    $web_protocol = empty($plugin_options['googlemaps']['useSsl']) ? 'http:' : 'https:';

    // Build the Google Maps Reverse Geocoding request, with the apiKey.
    $url = $web_protocol . '//maps.googleapis.com/maps/api/geocode/json?latlng=' . urlencode($lat_lng) . '&key=' . $this->gmapApiKey;

    // Add additional options to the request (besides the apiKey already in)
    $this->addUrlParameters($url, $plugin_options);

    /* @var \GuzzleHttp\Client $client */
    $client = $this->httpClient;
    return Json::decode($client->get($url)->getBody()->getContents());
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSetupDebugMessage() {

    // If a Gmap Api key has been defined.
    if (!empty($this->gmapApiKey)) {
      $output_message = $this->notEmptyGmapApiKeyMessage();
    }
    // Else the Gmap Api key is missing.
    else {
      $output_message = $this->emptyGmapApiKeyMessage('widget');
    }

    return $output_message;
  }

  /**
   * {@inheritdoc}
   */
  public function formatterSetupDebugMessage() {

    // If a Gmap Api key has been defined.
    if (!empty($this->gmapApiKey)) {
      $output_message = $this->notEmptyGmapApiKeyMessage();
    }
    // Else the Gmap Api key is missing.
    else {
      $output_message = $this->emptyGmapApiKeyMessage('formatter');
    }

    return $output_message;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetElementDebugMessage() {

    $output_message = [];

    if ($this->currentUser->hasPermission('configure geofield_map') && $this->geocoderDebugMessageFlag) {

      $output_message = [
        '#type' => 'details',
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#title' => $this->t('Debug - Geocoder Module NOT enabled'),
        'geocoder-debug-message' => $this->geocoderDebugMessage,
        '#attributes' => [
          'class' => ['geocoder-debug-message'],
        ],
      ];

      // If a Gmap Api key has been defined.
      // Output a further message regarding the Gmap Api Key.
      if (!empty($this->gmapApiKey)) {
        $output_message['gmap_api_key'] = $this->notEmptyGmapApiKeyMessage('debug');
      }
      else {
        $output_message = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          'value' => $this->emptyGmapApiKeyMessage(),
          '#attributes' => [
            'class' => ['geocoder-debug-message'],
          ],
        ];
      }

    }

    return $output_message;
  }

  /**
   * {@inheritdoc}
   */
  public function geocodeAddressElementCanWork() {
    return !empty($this->gmapApiKey);
  }

}
