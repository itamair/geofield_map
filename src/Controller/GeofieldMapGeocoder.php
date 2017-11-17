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
   * {@inheritdoc}
   */
  public function geocode(Request $request) {

    // Get data from the POST Request.
    $request_content = json_decode($request->getContent(), TRUE);
    $address = !empty($request->get('address')) ? $request->get('address') : '';

    $geocoder_service = $this->moduleHandler->moduleExists('geocoder') ? 'geocoder_module' : 'googlemaps_service';

    $plugins = [];
    $options = [];
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

    // If gmap apikey set, implement GoogleMap Geocoder plugin as Default one.
    $gmap_apikey = $this->getGmapApiKey();

    // Proceed only if a not empty address has been provided.
    if (!empty($address)) {

      switch ($geocoder_service) {
        // If the Geocoder Module exists, geocode with it ...
        case 'geocoder_module':
          $plugins = !empty($request->get('plugins')) ? explode('+', $request->get('plugins')) : $plugins;
          $options = !empty($request_content['plugins_options']) ? $request_content['plugins_options'] : $options;

          // If the googlemaps plugin is set,
          // use/force the $gmap_apikey as plugin option.
          if (!empty($gmap_apikey) &&
            (in_array('googlemaps', $plugins) && empty($options['googlemaps']['apikey']))) {
            $options['googlemaps'] = [
              'apikey' => $gmap_apikey,
            ];
          }

          if (empty($plugins)) {
            $data = [
              'geocode' => [
                'status' => FALSE,
                'message' => 'No Geocoder Plugins Set.',
              ],
              'results' => '',
            ];
          }

          // Get the result of Address geocoderGeocode.
          $data['results'] = $this->geocoder->geocoderGeocode($address, $plugins, $options);
          if (!empty($data['results'])) {
            $data['geocode']['status'] = TRUE;
            $data['geocode']['message'] = 'The Geocoder(s) succeded on ' . $address . ' with the following plugins: ' . implode(', ', $plugins);
          }
          else {
            $data['geocode']['message'] = 'The Geocoder(s) failed to geocode ' . $address . ' with the following plugins: ' . implode(', ', $plugins);
          }
          break;

        // ... otherwise try with the Google Map Service.
        case 'googlemaps_service':
          // Get the result of Address geocoderGeocode.
          $data['results'] = $this->geocoder->googleMapsGeocode($address, $gmap_apikey);
          if (!empty($data['results'])) {
            $data['geocode']['status'] = TRUE;
            $data['geocode']['message'] = 'The direct Google Map Service succeded on ' . $address . ' with the following plugins: ' . implode(', ', $plugins);
          }
          else {
            $data['geocode']['message'] = 'The Google Map Service failed to geocode ' . $address;
          }
          break;

        default:
      }
    }

    $response = new CacheableJsonResponse($data);
    $response->addCacheableDependency(CacheableMetadata::createFromRenderArray($data));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function reverseGeocode(Request $request) {

    $data = [
      'result' => '',
    ];
    $data['method'] = 'GET';
    $data['#cache'] = [
      'contexts' => [
        'url.path',
        'url.query_args',
      ],
    ];

    $response = new CacheableJsonResponse($data);
    $response->addCacheableDependency(CacheableMetadata::createFromRenderArray($data));
    return $response;
  }

}
