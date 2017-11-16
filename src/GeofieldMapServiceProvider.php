<?php

namespace Drupal\geofield_map;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Geofield Map Service Provider.
 */
class GeofieldMapServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    // Check for installed Geocoder Module.
    if (isset($modules['geocoder'])) {
      $definition = $container->getDefinition('geofield_map.geocoder');
      $definition->setClass('Drupal\geofield_map\Services\GeocoderGeocoderService')
        ->addArgument(new Reference('geocoder'))
        ->addArgument(new Reference('plugin.manager.geocoder.dumper'));
    }
  }

}
