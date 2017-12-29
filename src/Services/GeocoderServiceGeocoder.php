<?php

namespace Drupal\geofield_map\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\geocoder\GeocoderInterface;
use Drupal\geocoder\ProviderPluginManager;
use Drupal\geocoder\DumperPluginManager;
use Geocoder\Model\Address;
use Drupal\geocoder\FormatterPluginManager;

/**
 * Class GeocoderServiceGeocoder.
 */
class GeocoderServiceGeocoder extends GeocoderServiceAbstract implements GeocoderServiceInterface {

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Drupal\geocoder\Geocoder definition.
   *
   * @var \Drupal\geocoder\Geocoder
   */
  protected $geocoder;

  /**
   * The dumper plugin manager service.
   *
   * @var \Drupal\geocoder\DumperPluginManager
   */
  protected $dumperPluginManager;


  /**
   * The provider plugin manager service.
   *
   * @var \Drupal\geocoder\ProviderPluginManager
   */
  protected $providerPluginManager;

  /**
   * The Geocoder formatter plugin manager service.
   *
   * @var \Drupal\geocoder\FormatterPluginManager
   */
  protected $geocoderFormatterPluginManager;

  /**
   * Get the list of Geocoders Plugins.
   *
   * @return array
   *   The Geocoders Plugin List in a string format.
   */
  protected function getGeocodersPlugins() {
    $selected_plugins = $this->config->get('geofield_map.settings')->get('geocoder.plugins');
    $plugins = $this->providerPluginManager->getPluginsAsOptions();

    $geocoders = [];
    foreach ($selected_plugins as $plugin_id) {
      if (array_key_exists($plugin_id, $plugins)) {
        $geocoders[] = $plugins[$plugin_id];
      }
    };
    return implode(", ", $geocoders);
  }

  /**
   * Add a geometry property if not defined (as Google Maps Geocoding does).
   *
   * @param \Geocoder\Model\Address $address
   *   The Address array.
   *
   * @return array
   *   The Address Geometry Property.
   */
  protected function addGeometryProperty(Address $address) {
    /* @var array $address_array */
    $address_array = $address->toArray();

    return [
      'location' => [
        'lat' => $address_array['latitude'],
        'lng' => $address_array['longitude'],
      ],
      'viewport' => [
        'northeast' => [
          'lat' => $address_array['bounds']['north'],
          'lng' => $address_array['bounds']['east'],
        ],
        'southwest' => [
          'lat' => $address_array['bounds']['south'],
          'lng' => $address_array['bounds']['west'],
        ],
      ],
    ];
  }

  /**
   * Constructs a new GeofieldMapGeocoderService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Http Client.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The modules handler.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\geocoder\GeocoderInterface $geocoder
   *   The geocoder service.
   * @param \Drupal\geocoder\DumperPluginManager $dumper_plugin_manager
   *   The geocoder dumper service.
   * @param \Drupal\geocoder\ProviderPluginManager $provider_plugin_manager
   *   The geocoders manager service.
   * @param \Drupal\geocoder\FormatterPluginManager $geocoder_formatter_plugin_manager
   *   The geocoder formatter plugin manager service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ClientInterface $http_client,
    AccountProxyInterface $current_user,
    ModuleHandlerInterface $module_handler,
    LinkGeneratorInterface $link_generator,
    TranslationInterface $string_translation,
    GeocoderInterface $geocoder,
    DumperPluginManager $dumper_plugin_manager,
    ProviderPluginManager $provider_plugin_manager,
    FormatterPluginManager $geocoder_formatter_plugin_manager
  ) {
    $this->geocoder = $geocoder;
    $this->dumperPluginManager = $dumper_plugin_manager;
    $this->providerPluginManager = $provider_plugin_manager;
    $this->geocoderFormatterPluginManager = $geocoder_formatter_plugin_manager;
    parent::__construct($config_factory, $http_client, $current_user, $module_handler, $link_generator, $string_translation);
  }

  /**
   * Manage Language Option for Google Maps Plugin.
   *
   * @param array $plugin_options
   *   Plugin options.
   */
  private function manageGoogleMapsLanguageOption(array &$plugin_options) {
    if (isset($plugin_options['googlemaps'])) {
      foreach ($plugin_options['googlemaps'] as $k => $option) {
        if ($k == 'language') {
          $plugin_options['googlemaps']['locale'] = $option;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function geocode($address, array $plugins, array $plugin_options = []) {

    // Eventually, the Google Maps Geocoding API "language" parameter needs to
    // be translated into "locale" in Geocoder Module API.
    $this->manageGoogleMapsLanguageOption($plugin_options);

    $data = [
      'results' => [],
    ];
    /* @var array $addresses_collection */
    $addresses_collection = $this->geocoder->geocode($address, $plugins, $plugin_options)->all();
    if (!empty($addresses_collection)) {
      /* @var \Geocoder\Model\Address $geo_address */
      foreach ($addresses_collection as $geo_address) {
        $geo_address_array = $geo_address->toArray();
        // If a formatted_address property is not defined (as Google Maps
        // Geocoding does), then create it with our own formatter.
        if (!isset($geo_address_array['formatted_address'])) {

          $geo_address_array['formatted_address'] = $this->geocoderFormatterPluginManager->createInstance($this->getGeofieldMapFormatter())
            ->format($geo_address);
        }
        // If a geometry property is not defined
        // (as Google Maps Geocoding does), then create it with our own dumper.
        if (!isset($geo_address_array['geometry'])) {
          $geo_address_array['geometry'] = $this->addGeometryProperty($geo_address);
        }
        $data['results'][] = $geo_address_array;
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function reverseGeocode($lat, $lng, array $plugins, array $plugin_options = []) {

    // Eventually, the Google Maps Geocoding API "language" parameter needs to
    // be translated into "locale" in Geocoder Module API.
    $this->manageGoogleMapsLanguageOption($plugin_options);

    $data = [
      'results' => [],
    ];

    /* @var \Geocoder\Model\AddressCollection $addresses_collection */
    $addresses_collection = $this->geocoder->reverse($lat, $lng, $plugins, $plugin_options);

    if (!empty($addresses_collection)) {
      /* @var \Geocoder\Model\Address $geo_address */
      $geo_address = $addresses_collection->first();
      $geo_address_array = $geo_address->toArray();
      // If a formatted_address property is not defined (as Google Maps
      // Geocoding does), then create it with our own formatter.
      if (!isset($geo_address_array['formatted_address'])) {
        $geo_address_array['formatted_address'] = $this->geocoderFormatterPluginManager->createInstance($this->getGeofieldMapFormatter())
          ->format($geo_address);
      }
      // If a geometry property is not defined
      // (as Google Maps Geocoding does), then create it with our own dumper.
      if (!isset($geo_address_array['geometry'])) {
        $geo_address_array['geometry'] = $this->addGeometryProperty($geo_address);
      }
      $data['results'][] = $geo_address_array;
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSetupDebugMessage() {

    // If a Gmap Api key has been defined.
    if (!empty($this->gmapApiKey)) {
      $output_message['gmap_apy_key'] = $this->notEmptyGmapApiKeyMessage();
    }

    $geofield_map_settings_page_link = $this->currentUser->hasPermission('configure geofield_map')
      ? $this->link->generate(t('Set it in the Geofield Map Configuration Page'), $this->geofieldMapSettingsPageUrl)
      : t('You need proper permissions for the Geofield Map Configuration Page');

    $output_message['plugins'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => !empty($this->getGeocodersPlugins()) ? $this->t('Enabled Geocoders: @enabled_geocoders', [
        '@enabled_geocoders' => $this->getGeocodersPlugins(),
      ]) : $this->t("<span class='geofield-map-warning'>No enabled Geocoder (Geocode/ReverseGeocode functionalities won't be available)</span>"),
    ];

    if (!empty($this->getGeocodersPlugins())) {
      $output_message['options'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Options: @options', [
          '@options' => $this->config->get('geofield_map.settings')
            ->get('geocoder.options'),
        ]),
      ];
    }

    // If a Gmap Api key has not been defined.
    if (empty($this->gmapApiKey)) {
      return $this->emptyGmapApiKeyMessage();
    }

    if (empty($this->getGeocodersPlugins()) || empty($this->gmapApiKey)) {
      $output_message['geofield_map_settings_page_link'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $geofield_map_settings_page_link,
      ];
    }

    return $output_message;
  }

  /**
   * {@inheritdoc}
   */
  public function formatterSetupDebugMessage() {

    // If a Gmap Api key has been defined.
    if (!empty($this->gmapApiKey)) {
      $output_message['gmap_apy_key'] = $this->notEmptyGmapApiKeyMessage();
    }
    // Else the Gmap Api key is missing.
    else {
      $output_message['gmap_apy_key'] = $this->emptyGmapApiKeyMessage();
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
        '#title' => $this->t('Debug - Geocoder Module Integration enabled'),
        'geocoder-debug-message' => $this->geocoderDebugMessage,
        '#attributes' => [
          'class' => ['geocoder-debug-message'],
        ],
      ];

      // Output a further message regarding the Gmap Api Key.
      if (!empty($this->gmapApiKey)) {
        $output_message['gmap_api_key'] = $this->notEmptyGmapApiKeyMessage('debug');
      }
      else {
        $output_message['gmap_api_key_missing'] = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => t("Gmap Api Key missing | The Google Maps library is not available"),
        ];
        if ($this->config->get('geofield_map.settings')->get('geocoder.plugins') == ['googlemaps']) {
          $output_message['gmap_api_key_missing']['#value'] = t("Gmap Api Key missing | The Google Maps library & Geocode/ReverseGeocode functionalities are not available");
        }
      }

    }

    return $output_message;
  }

  /**
   * {@inheritdoc}
   */
  public function geocodeAddressElementCanWork() {
    $plugins = $this->config->get('geofield_map.settings')->get('geocoder.plugins');
    return !empty($plugins) && !($plugins == ['googlemaps'] && empty($this->gmapApiKey));
  }

}
