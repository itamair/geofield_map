<?php

namespace Drupal\geofield_map\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

/**
 * Class GeocoderGoogleMaps.
 */
abstract class GeocoderServiceAbstract implements GeofieldMapGeocoderServiceInterface {

  use StringTranslationTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The current user making the request.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;

  /**
   * The Gmap Api Key.
   *
   * @var string
   */
  protected $gmapApiKey;

  /**
   * The Geocoders String List.
   *
   * @var string
   */
  protected $geocodersPlugins;

  /**
   * The Geocoder debug message preference.
   *
   * @var string
   */
  protected $geocoderDebugMessageFlag;

  /**
   * The Geocoder debug message preference.
   *
   * @var string
   */
  protected $geocoderDebugMessage;

  /**
   * The Url to Geofield Map settings/configuration page.
   *
   * @var string
   */
  protected $geofieldMapSettingsPageUrl;

  /**
   * Get the list of Geocoders Plugins.
   *
   * This will be overwritten by the specific extending service class.
   *
   * @return string
   *   The Geocoders Plugin List in a string format.
   */
  protected function getGeocodersPlugins() {
    return '';
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
   * Output a Message for the Not Empty Gmap API Key.
   *
   * @param string $context
   *   The context identifier.
   *
   * @return array
   *   The output render array.
   */
  protected function emptyGmapApiKeyMessage($context = 'widget') {

    $geofield_map_settings_page_link = $this->currentUser->hasPermission('configure geofield_map')
      ? $this->link->generate(t('Set it in the Geofield Map Configuration Page'), $this->geofieldMapSettingsPageUrl)
      : t('You need proper permissions for the Geofield Map Configuration Page');

    $output_message = [
      '#type' => 'container',
      'gmap_key_missing' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $context == 'widget' ? $this->t("Gmap Api Key missing | Google Maps and Geocode / ReverseGeocode functionalities not available.") : t("Gmap Api Key missing | Google Maps functionalities not available."),
        '#attributes' => [
          'class' => ['geofield-map-warning'],
        ],
      ],
      'geofield_map_settings_page_link' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $geofield_map_settings_page_link,
      ],
    ];

    return $output_message;

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
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ClientInterface $http_client,
    AccountProxyInterface $current_user,
    ModuleHandlerInterface $module_handler,
    LinkGeneratorInterface $link_generator,
    TranslationInterface $string_translation
  ) {
    $this->config = $config_factory;
    $this->httpClient = $http_client;
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->link = $link_generator;

    $this->gmapApiKey = $this->config->get('geofield_map.settings')->get('gmap_api_key');

    $this->geocodersPlugins = [];

    $this->geocoderDebugMessageFlag = $this->config->get('geofield_map.settings')->get('geocoder.debug_message');

    $this->geofieldMapSettingsPageUrl = Url::fromRoute('geofield_map.settings', [],
      strpos(strtolower(Url::fromRoute('<current>')->toString()), 'ajax') === FALSE ? [
        'query' => [
          'destination' => Url::fromRoute('<current>')->toString(),
        ],
      ] : []
      );

    $this->geocoderDebugMessage = [
      'plugins' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => !empty($this->getGeocodersPlugins()) ? $this->t('Enabled Geocoders: @enabled_geocoders', [
          '@enabled_geocoders' => $this->getGeocodersPlugins(),
        ]) : $this->t('No enabled Geocoder: @geofield_map_settings_page_link',
          [
            '@geofield_map_settings_page_link' => $this->link->generate(t('Set it in the Geofield Map Configuration Page'), $this->geofieldMapSettingsPageUrl),
          ]),
      ],
      'min_terms' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Minimum Terms: @min_terms', [
          '@min_terms' => $this->config->get('geofield_map.settings')->get('geocoder.min_terms'),
        ]),
      ],
      'delay_time' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Delay time: @delay_time', [
          '@delay_time' => $this->config->get('geofield_map.settings')->get('geocoder.delay'),
        ]),
      ],
      'options' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Options: @options', [
          '@options' => $this->config->get('geofield_map.settings')->get('geocoder.options'),
        ]),
      ],
      '#weight' => 0,
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function geocoderGeocode($address, array $plugins, array $plugin_options = []) {
    $results = [];
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function googleMapsGeocode($address, $apiKey, array $options) {
    $results = [];
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetElementDescription() {
    return $this->t('Use this to search and geocode your location (type at least @min_terms terms)', [
      '@min_terms' => $this->config->get('geofield_map.settings')->get('geocoder.min_terms'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function gmapApiKeyElementDescription() {

    // Change the message depending on the Geocoder module integration,
    // as gmapApiKey is only needed for GMap in that case.
    $geocode_reverse_add = !$this->moduleHandler->moduleExists('geocoder') ? $this->t('<br>(and Geocode/Reverse Geocode)') : '';

    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('A valid Gmap Api Key is requested by @google_maps_apis_link @geocode_reverse_add functionalities.', [
        '@geocode_reverse_add' => $geocode_reverse_add,
        '@google_maps_apis_link' => $this->link->generate(t('Google Maps APIs'), Url::fromUri('https://developers.google.com/maps/documentation/javascript/', [
          'absolute' => TRUE,
          'attributes' => ['target' => 'blank'],
        ])),
      ]),
      '#attributes' => [
        'class' => ['gmapapikey-element-description'],
      ],
    ];
  }

}
