<?php

namespace Drupal\geofield_map\Services;

use Drupal\geocoder\GeocoderPluginManagerBase;
use Drupal\geocoder\DumperInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\geofield_map\Annotation\GeofieldMapDumper;

/**
 * Provides a plugin manager for geofield map dumpers.
 */
class GeofieldMapGeocoderDumperPluginManager extends GeocoderPluginManagerBase implements GeofieldMapGeocoderDumperPluginManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/GeofieldMap/Dumper', $namespaces, $module_handler, DumperInterface::class, GeofieldMapDumper::class);
    $this->alterInfo('geofield_map_dumper_info');
    $this->setCacheBackend($cache_backend, 'geofield_map_dumper_plugins');
  }

}
