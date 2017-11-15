<?php

namespace Drupal\geofield_map\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\geocoder\GeocoderInterface;
use Drupal\geocoder\DumperPluginManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Class GeofieldMapGeocoder.
 */
class GeofieldMapGeocoder extends ControllerBase implements GeofieldMapGeocoderInterface {

  /**
   * Drupal\geocoder\Geocoder definition.
   *
   * @var \Drupal\geocoder\GeocoderInterface
   */
  protected $geocoder;

  /**
   * Drupal\geocoder\DumperPluginManager definition.
   *
   * @var \Drupal\geocoder\DumperPluginManager
   */
  protected $geocoderDumper;

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new GeofieldMapGeocoder object.
   *
   * @param \Drupal\geocoder\GeocoderInterface $geocoder
   *   The geocoder service.
   * @param \Drupal\geocoder\DumperPluginManager $geocoder_dumper
   *   The geocoder dumper service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The modules handler.
   */
  public function __construct(GeocoderInterface $geocoder, DumperPluginManager $geocoder_dumper, ModuleHandlerInterface $module_handler) {
    $this->geocoder = $geocoder;
    $this->geocoderDumper = $geocoder_dumper;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('geocoder'),
      $container->get('plugin.manager.geocoder.dumper'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function geocode(Request $request) {

    // @TODO: Add logic here to implement Google Geocoder as Default one.

    $data = [
      'results' => '',
    ];
    $data['message'] = 'The Geocoders module are disabled';

    // If the Geocoders Modules are enabled.
    if ($this->moduleHandler->moduleExists('geocoder') &&
      $this->moduleHandler->moduleExists('geocoder_geofield')) {

      // Get data from the POST Request.
      $request_content = json_decode($request->getContent(), TRUE);

      $address = $request_content['address'] ? $request_content['address'] : '';
      $plugins = !empty($request_content['plugins']) ? explode('+', $request_content['plugins']) : ['openstreetmap'];
      $options = !empty($request_content['plugins_options']) ? $request_content['plugins_options'] : [];

      /* @var \Geocoder\Model\Address $addressesCollection */
      $addressesCollection = $this->geocoder->geocode($address, $plugins, $options)->all();
      if (!empty($addressesCollection)) {
        /* @var \Geocoder\Model\Address $geoAddress */
        foreach ($addressesCollection as $geoAddress) {
          $geoAddressArray = $geoAddress->toArray();
          if ($this->moduleHandler->moduleExists('geocoder_address')) {
            $geoAddressString = $this->geocoderDumper->createInstance('geofieldmap_addresstext')->dump($geoAddress);
            $geoAddressArray['address'] = $geoAddressString;
          }
          $data['results'][] = $geoAddressArray;
        }
        $data['message'] = 'The Geocoder(s) succeded on ' . $address . ' with the following plugins: ' . implode(', ', $plugins);
      }
      else {
        $data['message'] = 'The Geocoder(s) failed to geocode ' . $address . ' with the following plugins: ' . implode(', ', $plugins);
      }
    }

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
