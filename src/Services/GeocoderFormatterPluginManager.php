<?php

namespace Drupal\geofield_map\Services;

use Drupal\geofield_map\ GeofieldMapPluginManagerBase;
use Drupal\geofield_map\Plugin\GeofieldMap\Formatter\GeocoderFormatterInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\geofield_map\Annotation\GeofieldMapFormatter;

/**
 * Provides a plugin manager for geofield map formatters.
 */
class GeocoderFormatterPluginManager extends GeofieldMapPluginManagerBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/GeofieldMap/Formatter', $namespaces, $module_handler, GeocoderFormatterInterface::class, GeofieldMapFormatter::class);
    $this->alterInfo('geofield_map_formatter_info');
    $this->setCacheBackend($cache_backend, 'geofield_map_formatter_plugins');
  }

}
