<?php

namespace Drupal\geofield_map\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\geofield_map\GeofieldMapFieldTrait;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\geofield_map\Services\GeofieldMapGeocoderServiceInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Class GeofieldMapGeocoder.
 */
class GeofieldMapGeocoder extends ControllerBase implements GeofieldMapGeocoderInterface {

  use GeofieldMapFieldTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Drupal\geofield_map\GeofieldMapGeocoder definition.
   *
   * @var \Drupal\geofield_map\Services\GeocoderGoogleMapsService
   */
  protected $geocoder;

  /**
   * The Geocoder service integration flag.
   *
   * @var bool
   */
  protected $geocoderIntegration;

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new GeofieldMapGeocoder object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The modules handler.
   * @param \Drupal\geofield_map\Services\GeofieldMapGeocoderServiceInterface $geofield_map_geocoder
   *   The Geofield Map Geocoder service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, GeofieldMapGeocoderServiceInterface $geofield_map_geocoder) {
    $this->config = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->geocoder = $geofield_map_geocoder;
    $this->geocoderIntegration = $this->moduleHandler->moduleExists('geocoder') ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('geofield_map.geocoder')
    );
  }

  /**
   * Get data from the POST Request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request.
   *
   * @return array
   *   The requested data.
   */
  protected function getRequestData(Request $request) {
    $plugins = !empty($request->get('plugins')) ? explode('+', $request->get('plugins')) : [];
    $options = !empty($request->getContent()) ? json_decode($request->getContent(), TRUE) : [];
    return [$plugins, $options];
  }

  /**
   * The Result to output in case of empty plugins.
   *
   * @return array
   *   The result array.
   */
  protected function emptyPluginsResult() {
    $data = [
      'geocode' => [
        'status' => FALSE,
        'message' => 'No Geocoder Plugins Set.',
      ],
      'results' => '',
    ];
    return $data;
  }

  /**
   * Write Geocoding Service Response Status and Message.
   *
   * @param array $data
   *   The Response result data.
   * @param string $input
   *   The Request input.
   * @param array $plugins
   *   The Geocode Plugins.
   *
   * @return array
   *   The updated Response array
   */
  protected function writeResponseStatusAndMessage(array $data, $input, array $plugins) {
    if (!empty($data['results'])) {
      $data['geocode']['status'] = TRUE;
      $data['geocode']['message'] = $this->t('The @geocoder_service succeeded on  @latlng  with the following plugins: @plugins', [
        '@geocoder_service' => $this->geocoderIntegration ? $this->t('Geocoder(s) Module') : 'Google Map Geocoder API',
        '@latlng' => $input,
        '@plugins' => implode(', ', $plugins),
      ]);
    }
    else {
      $data['geocode']['message'] = $this->t('The @geocoder_service succeeded on  @latlng  with the following plugins: @plugins', [
        '@geocoder_service' => $this->geocoderIntegration ? $this->t('Geocoder(s) Module') : 'Google Map Geocoder API',
        '@latlng' => $input,
        '@plugins' => implode(', ', $plugins),
      ]);
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function geocode(Request $request) {

    // Get data from the POST Request.
    list($plugins, $options) = $this->getRequestData($request);

    // If no plugin has been set, then return without processing.
    if (empty($plugins)) {
      $data = $this->emptyPluginsResult();
      return $data;
    }

    $data = [
      'geocode' => [
        'status' => FALSE,
        'message' => 'No address provided',
      ],
      'results' => '',
      '#cache' => [
        'contexts' => [
          'url.path',
          'url.query_args',
        ],
      ],
    ];

    $address = !empty($request->get('address')) ? $request->get('address') : '';

    // Try to get the gmap apikey set,
    // and implement GoogleMap Geocoder plugin as Default one.
    $gmap_apikey = $this->config->get('geofield_map.settings')->get('gmap_api_key');

    // If the googlemaps plugin is set,
    // use/force the $gmap_apikey as plugin option.
    if (!empty($gmap_apikey) &&
      (in_array('googlemaps', $plugins) && empty($options['googlemaps']['apikey']))) {
      $options['googlemaps']['apiKey'] = $gmap_apikey;
    }

    // Proceed if an address have been provided.
    if (!empty($address)) {

      // If the googlemaps plugin is set,
      // use/force the $gmap_apikey as plugin option.
      if (!empty($gmap_apikey) &&
        (in_array('googlemaps', $plugins) && empty($options['googlemaps']['apikey']))) {
        $options['googlemaps']['apiKey'] = $gmap_apikey;
      }

      // Get the result of Address Geocode.
      $data['results'] = $this->geocoder->geocode($address, $plugins, $options);

      // Write Response Status and Message.
      $data = $this->writeResponseStatusAndMessage($data, $address, $plugins);
    }

    $response = new CacheableJsonResponse($data);
    $response->addCacheableDependency(CacheableMetadata::createFromRenderArray($data));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function reverseGeocode(Request $request) {

    // Get data from the POST Request.
    list($plugins, $options) = $this->getRequestData($request);

    // If no plugin has been set, then return without processing.
    if (empty($plugins)) {
      $data = $this->emptyPluginsResult();
      return $data;
    }

    $data = [
      'geocode' => [
        'status' => FALSE,
        'message' => 'No Lat Lng coordinates provided',
      ],
      'results' => '',
      '#cache' => [
        'contexts' => [
          'url.path',
          'url.query_args',
        ],
      ],
    ];

    $latlng = !empty($request->get('latlng')) ? $request->get('latlng') : '';

    // Try to get the gmap apikey set,
    // and implement GoogleMap Geocoder plugin as Default one.
    $gmap_apikey = $this->config->get('geofield_map.settings')->get('gmap_api_key');

    // Proceed if a couple of Geo Coordinates have been provided.
    if (!empty($latlng)) {

      // If the googlemaps plugin is set,
      // use/force the $gmap_apikey as plugin option.
      if (!empty($gmap_apikey) &&
        (in_array('googlemaps', $plugins) && empty($options['googlemaps']['apikey']))) {
        $options['googlemaps']['apiKey'] = $gmap_apikey;
      }

      // Get the result of Address Reverse Geocode.
      $data['results'] = $this->geocoder->reverseGeocode($latlng, $plugins, $options);

      // Write Geocoding Service Response Status and Message.
      $data = $this->writeResponseStatusAndMessage($data, $latlng, $plugins);
    }

    $response = new CacheableJsonResponse($data);
    $response->addCacheableDependency(CacheableMetadata::createFromRenderArray($data));
    return $response;
  }

}
