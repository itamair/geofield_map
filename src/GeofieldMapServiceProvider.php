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
      $geocoder_definition = $container->getDefinition('geofield_map.geocoder');
      $geocoder_definition->setClass('Drupal\geofield_map\Services\GeocoderServiceGeocoder')
        ->addArgument(new Reference('geocoder'))
        ->addArgument(new Reference('plugin.manager.geocoder.dumper'))
        ->addArgument(new Reference('plugin.manager.geocoder.provider'))
        ->addArgument(new Reference('plugin.manager.geocoder.formatter'));

      $geocoder_plugin_manager_provider_definition = $container->getDefinition('geofield_map.geocoder_plugin_manager_provider');
      $geocoder_plugin_manager_provider_definition->setClass('Drupal\geofield_map\Services\GeocoderPluginManager')
        ->addArgument(new Reference('plugin.manager.geocoder.provider'));
    }

  }

}
