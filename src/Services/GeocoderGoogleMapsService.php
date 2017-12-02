<?php

namespace Drupal\geofield_map\Services;

use Drupal\Component\Serialization\Json;

/**
 * Class GeocoderGoogleMapsService.
 */
class GeocoderGoogleMapsService extends GeocoderGoogleMaps {

  /**
   * {@inheritdoc}
   */
  public function googleMapsGeocode($address, $apiKey, array $options) {

    // Build the Google Maps Geocoding request, with the apiKey.
    $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($address) . '&key=' . $apiKey;

    // Add additional options to the request (besides the apiKey already in)
    foreach ($options['googlemaps'] as $k => $option) {
      if ($k != 'apiKey') {
        $url .= '&' . rawurlencode($k) . '=' . rawurlencode($option);
      }
      if ($k == 'locale') {
        $url .= '&language=' . rawurlencode($option);
      }
    }

    /* @var \GuzzleHttp\Client $client */
    $client = $this->httpClient;
    $data = Json::decode($client->get($url)->getBody()->getContents());
    return $data['results'];
  }

  /**
   * {@inheritdoc}
   */
  public function widgetDebugMessage() {

    // If a Gmap Api key has been defined.
    if (!empty($this->gmapApiKey)) {

      $gmap_api_key_link = $this->currentUser->hasPermission('configure geofield_map')
        ? $this->link->generate($this->gmapApiKey, $this->geofieldMapSettingsPageUrl)
        : $this->gmapApiKey;

      $map_google_api_key_value = $this->t('<strong>Gmap Api Key:</strong> @gmap_api_key_link<br>A valid Gmap Api Key enables the Geocode and ReverseGeocode functionalities (provided by the Google Map Geocoder)', [
        '@gmap_api_key_link' => $gmap_api_key_link,
      ]);
    }
    // Else the Gmap Api key is missing.
    else {

      $geofield_map_settings_page_link = $this->currentUser->hasPermission('configure geofield_map')
        ? $this->link->generate(t('Set it in the Geofield Map Configuration Page'), $this->geofieldMapSettingsPageUrl)
        : t('You need proper permissions for the Geofield Map Configuration Page');

      $map_google_api_key_value = $this->t("<span class='geofield-map-warning'>Gmap Api Key missing (Geocode and ReverseGeocode functionalities won't be available)</span><br>@geofield_map_settings_page_link", [
        '@geofield_map_settings_page_link' => $geofield_map_settings_page_link,
      ]);
    }

    $output_message = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $map_google_api_key_value,
      '#attributes' => [
        'class' => ['geocoder-message'],
      ],
    ];

    return $output_message;
  }

}
