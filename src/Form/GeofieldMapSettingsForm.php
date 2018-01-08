<?php

namespace Drupal\geofield_map\Form;

use Drupal\geofield_map\GeofieldMapFormElementsValidationTrait;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\geocoder\FormatterPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\geofield_map\Services\GeocoderPluginManagerInterface;
use Drupal\geofield_map\Services\GeocoderServiceInterface;

/**
 * Implements the GeofieldMapSettingsForm controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class GeofieldMapSettingsForm extends ConfigFormBase {

  use GeofieldMapFormElementsValidationTrait;

  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The provider plugin manager service.
   *
   * @var \Drupal\geofield_map\Services\GeocoderPluginManagerInterface
   */
  protected $geocoderPluginManager;

  /**
   * The Geofield Map Geocoder service.
   *
   * @var \Drupal\geofield_map\Services\GeocoderServiceInterface
   */
  protected $geofieldMapGeocoder;

  /**
   * The Geocoder Formatter plugin manager service.
   *
   * @var \Drupal\geocoder\FormatterPluginManager
   */
  protected $geocoderFormatterPluginManager;

  /**
   * GeofieldMapSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\geofield_map\Services\GeocoderPluginManagerInterface $geocoder_plugin_manager
   *   The provider plugin manager service.
   * @param \Drupal\geofield_map\Services\GeocoderServiceInterface $geofield_map_geocoder
   *   The geofield map geocoder service.
   * @param \Drupal\geocoder\FormatterPluginManager $geocoder_formatter_plugin_manager
   *   The geofield map dumper service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LinkGeneratorInterface $link_generator,
    ModuleHandlerInterface $module_handler,
    LanguageManagerInterface $language_manager,
    GeocoderPluginManagerInterface $geocoder_plugin_manager,
    GeocoderServiceInterface $geofield_map_geocoder,
    FormatterPluginManager $geocoder_formatter_plugin_manager
  ) {
    parent::__construct($config_factory);
    $this->link = $link_generator;
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
    $this->geocoderPluginManager = $geocoder_plugin_manager;
    $this->geofieldMapGeocoder = $geofield_map_geocoder;
    $this->geocoderFormatterPluginManager = $geocoder_formatter_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('config.factory'),
      $container->get('link_generator'),
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('geofield_map.geocoder_plugin_manager_provider'),
      $container->get('geofield_map.geocoder'),
      $container->get('plugin.manager.geocoder.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('geofield_map.settings');

    $language_id = $this->languageManager->getCurrentLanguage()->getId();
    $geocoder_module_link = $this->link->generate(t('Geocoder Module'), Url::fromUri('https://www.drupal.org/project/geocoder', [
      'absolute' => TRUE,
      'attributes' => ['target' => 'blank'],
    ]));

    $geocoder_php_library_link = $this->link->generate(t('Geocoder Php Library'), Url::fromUri('http://geocoder-php.org/Geocoder/#address-based-providers', [
      'absolute' => TRUE,
      'attributes' => ['target' => 'blank'],
    ]));

    $form['#tree'] = TRUE;

    // Attach Geofield Map Library.
    $form['#attached']['library'] = [
      'geofield_map/geofield_map_general',
      'geofield_map/geofield_map_settings',
    ];

    // Hide the 'gmap_api_key' form field to 'value' type.
    $form['gmap_api_key'] = [
      '#type' => 'value',
      '#value' => $config->get('gmap_api_key'),
    ];

    $form['geocoder'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Geofield Map Geocoder Settings'),
    ];

    $form['geocoder']['debug_message'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('geocoder.debug_message'),
      '#title' => $this->t('Enable Geocoder Debug Message'),
      '#description' => $this->t('If checked, a recap / warning message will be output (to the Geofield Map manager user) in the Geofield Map Widget Element regarding the working status and set up of the chosen Geocoder.'),
    ];
    $form['geocoder']['min_terms'] = [
      '#type' => 'number',
      '#default_value' => !empty($config->get('geocoder.min_terms')) ? $config->get('geocoder.min_terms') : 4,
      '#title' => $this->t('The (minimum) number of terms for the Geocoder to start processing.'),
      '#description' => $this->t('Valid values ​​for the widget are between 2 and 10. A too low value (<= 3) will affect the application Geocode Quota usage. Try to increase this value if you are experiencing Quota usage matters.'),
      '#min' => 2,
      '#max' => 10,
      '#size' => 3,
    ];
    $form['geocoder']['delay'] = [
      '#type' => 'number',
      '#default_value' => !empty($config->get('geocoder.delay')) ? $config->get('geocoder.delay') : 800,
      '#title' => $this->t('The delay (in milliseconds) between pressing a key in the Address Input field and starting the Geocoder search.'),
      '#description' => $this->t('Valid values ​​for the widget are multiples of 100, between 300 and 1500. A too low value (<= 300) will affect / increase the application Geocode Quota usage. Try to increase this value if you are experiencing Quota usage matters.'),
      '#min' => 300,
      '#max' => 1500,
      '#step' => 100,
      '#size' => 4,
    ];

    // Define base values for the options field.
    $options_field_description = $this->t('An object literal of Geocoder options. The syntax should respect the javascript object notation (json) format. As suggested in the field placeholder, always use double quotes (") both for the indexes and the string values.');
    $options_field_placeholder = '{"locale":"' . $language_id . '", "key_2": "value_2", "key_n": "value_n"}';

    $form['geocoder']['integration_message'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('<strong>The Geocoder Module integration is not enabled.</strong>'),
    ];

    if ($this->moduleHandler->moduleExists('geocoder')) {
      $form['geocoder']['integration_message']['#value'] = $this->t('<span class="geofield-map-success">The @geocoder_module_link integration is enabled.</span>', [
        '@geocoder_module_link' => $geocoder_module_link,
      ]);
    }

    // If the Geocoder Module exists extend the Form with Geocoders Elements.
    if ($this->moduleHandler->moduleExists('geocoder')) {

      $geocoder_formatter_options = $this->geocoderFormatterPluginManager->getPluginsAsOptions();

      $form['geocoder']['formatter'] = [
        '#type' => 'select',
        '#title' => $this->t('Geocoder Address Formatter'),
        '#options' => $geocoder_formatter_options,
        '#default_value' => $this->geofieldMapGeocoder->getGeofieldMapFormatter(),
        '#description' => [
          '#type' => 'container',
          'description_base' => [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => $this->t('This defines the way a Geocoded/Reverse-Geocoded Address will be formatted, according to the @geocoder_php_library_link Formatters APIs.', [
              '@geocoder_php_library_link' => $geocoder_php_library_link,
            ]),
          ],
          'description_add' => [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => $this->t('The Default Address Formatter corresponds to @geofieldmap_default_formatter plugin.<br>You can add your custom Geofield Map Geocoder Formatter copying and personalizing the default one in your "@geofield_map_formatter_folder" folder.', [
              '@geofieldmap_default_formatter' => 'src/Plugin/GeofieldMap/Formatter/GeofieldMapFormattedAddress',
              '@geofield_map_formatter_folder' => '[your_module_name]/src/Plugin/GeofieldMap/Formatter/',
            ]),
          ],
        ],
      ];

      /* @var \Drupal\geocoder\ProviderPluginManager $geocoder_provider_plugin_manager */
      $geocoder_provider_plugin_manager = $this->geocoderPluginManager->getGeocoderProviderPluginManager();

      // Get the default/selected geocoder plugins.
      $default_plugins = !empty($config->get('geocoder.plugins')) ? $config->get('geocoder.plugins') : [];
      $geocoders = $geocoder_provider_plugin_manager->getPluginsAsOptions();
      $geocoders_table_caption = $this->t('Select the Geocoder plugins to use. Reorder them in order of priority. The first one able to return a valid value will be used.</br>Add the optional or mandatory options for each Geocoder to make them working properly. Refer to Geocoder Module (and its dependency PHP libraries) for further documentation and specific Geocoders settings.', [
        '@geocoder_module_link' => $geocoder_module_link,
      ]);

      $form['geocoder']['plugins_checkboxes'] = [
        '#type' => 'table',
        '#header' => [
          t('Geocoder plugins'),
          $this->t('Weight'),
          $this->t('Options'),
        ],
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'plugins-order-weight',
          ],
        ],
        '#caption' => $geocoders_table_caption,
      ];
    }
    else {
      // Enable/Force the Google Maps Geocoder.
      $geocoders = ['googlemaps' => 'Google Maps'];
      // Get the default/selected geocoder plugins.
      $default_plugins = ['googlemaps'];
      $geocoders_table_caption = $this->t('The Google Maps Geocoder is being used as default one. Add and enable the @geocoder_module_link to take advantage of many more Geocoders and its functionalities integration.', [
        '@geocoder_module_link' => $geocoder_module_link,
      ]);

      $form['geocoder']['plugins_checkboxes'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Geocoder plugins'),
          $this->t('Options'),
        ],
        '#caption' => $geocoders_table_caption,
      ];
    }

    // The form element that stores all plugins options.
    $form['geocoder']['plugins_options'] = [
      '#type' => 'value',
      '#value' => !empty($config->get('geocoder.plugins_options')) ? $config->get('geocoder.plugins_options') : '',
    ];

    // Set plugins and plugins_data variables.
    $plugins = array_combine($default_plugins, $default_plugins);
    $plugins_data = $config->get('geocoder.plugins_checkboxes');
    foreach ($geocoders as $plugin_id => $plugin_name) {
      // Non-default values are appended at the end.
      $plugins[$plugin_id] = $plugin_name;
    }
    foreach ($plugins as $plugin_id => $plugin_name) {
      $form['geocoder']['plugins_checkboxes'][$plugin_id] = [
        'checked' => [
          '#type' => 'checkbox',
          '#title' => $plugin_name,
          '#default_value' => in_array($plugin_id, $default_plugins) ? TRUE : FALSE,
        ],
      ];

      // If the Geocoder Module exists add the weight column to the geocoders
      // plugins table and make the row draggable.
      if ($this->moduleHandler->moduleExists('geocoder')) {

        $form['geocoder']['plugins_checkboxes'][$plugin_id]['weight'] = [
          '#type' => 'weight',
          '#title' => $this->t('Weight for @title', ['@title' => $plugin_name]),
          '#title_display' => 'invisible',
          '#attributes' => ['class' => ['plugins-order-weight']],
        ];
        $form['geocoder']['plugins_checkboxes'][$plugin_id]['#attributes'] = [
          'class' => ['draggable'],
        ];
      }

      // If the Geocoder Module not exists force and disable the Googlemaps
      // checkbox.
      if (!$this->moduleHandler->moduleExists('geocoder') && $plugin_id == 'googlemaps') {
        $form['geocoder']['plugins_checkboxes'][$plugin_id]['#checked']['#default_value'] = TRUE;
        $form['geocoder']['plugins_checkboxes'][$plugin_id]['checked']['#disabled'] = TRUE;
      }

      $form['geocoder']['plugins_checkboxes'][$plugin_id]['options'] = [
        '#type' => 'details',
        '#title' => $this->t('@title Options', ['@title' => $plugin_name]),
        'json_options' => [
          '#type' => 'textarea',
          '#title' => $this->t('@title Options', ['@title' => $plugin_name]),
          '#title_display' => 'invisible',
          '#rows' => 3,
          '#description' => $options_field_description,
          '#default_value' => isset($plugins_data[$plugin_id]) & !empty($plugins_data[$plugin_id]['options']['json_options']) ? $plugins_data[$plugin_id]['options']['json_options'] : '',
          '#placeholder' => $options_field_placeholder,
          '#element_validate' => [[get_class($this), 'jsonValidate']],
        ],
        '#weight' => 0,
      ];

      // If it is the 'googlemaps' plugin_id.
      if ($plugin_id == 'googlemaps') {

        // Set the Use Ssl Checkbox Option (disabled to true)
        $form['geocoder']['plugins_checkboxes'][$plugin_id]['options']['use_ssl'] = [
          '#weight' => -3,
          '#type' => 'checkbox',
          '#default_value' => $config->get('geocoder.plugins_checkboxes.googlemaps.options.use_ssl') ? TRUE : FALSE,
          '#title' => $this->t('Use Ssl'),
          '#description' => $this->t('This needs to be checked for the Google Maps Geocoder be able to work.'),
          //'#disabled' => TRUE,
        ];

        $gmap_api_key_text = empty($config->get('gmap_api_key')) ? '<span class="geofield-map-warning">Gmap Api Key</span>' : 'Gmap Api Key';

        $form['geocoder']['plugins_checkboxes'][$plugin_id]['options']['gmap_api_key'] = [
          '#weight' => -2,
          '#type' => 'textfield',
          '#default_value' => $config->get('gmap_api_key'),
          '#title' => new FormattableMarkup($gmap_api_key_text . ' (@gmap_api_key_link)', [
            '@gmap_api_key_link' => $this->link->generate($this->t('Get a Key/Authentication for Google Maps Javascript Library'), Url::fromUri('https://developers.google.com/maps/documentation/javascript/get-api-key', [
              'absolute' => TRUE,
              'attributes' => ['target' => 'blank'],
            ])),
          ]),
          '#description' => $this->geofieldMapGeocoder->gmapApiKeyElementDescription(),
          '#placeholder' => $this->t('Input a valid Gmap API Key'),
        ];

        $options_field_description_google_maps_geocoder_warning = $this->moduleHandler->moduleExists('geocoder') ? '<br><u>Note: The Google Maps Geocoding API "language" parameter should (and will) be translated into "locale" in Geocoder Module API.</u>' : '';

        // Override for GoogleMaps base values for its options fields.
        $form['geocoder']['plugins_checkboxes'][$plugin_id]['options']['json_options']['#description'] = $this->t('<u>Add here additional options <strong>(besides the Use Ssl & Gmap Api Key)</strong>.</u><br>') . ' ' . $options_field_description . $options_field_description_google_maps_geocoder_warning;
        $form['geocoder']['plugins_checkboxes'][$plugin_id]['options']['json_options']['#placeholder'] = $this->moduleHandler->moduleExists('geocoder') ? '{"locale":"' . $language_id . '", "region":"' . $language_id . '"}' : '{"language":"' . $language_id . '", "region":"' . $language_id . '"}';

      }

      // As final step, open the plugin options details field,
      // if its 'options content' (or 'gmap_api_key') is not empty.
      if (!$this->moduleHandler->moduleExists('geocoder') ||
        !empty($form['geocoder']['plugins_checkboxes'][$plugin_id]['options']['json_options']['#default_value'])
        || (isset($form['geocoder']['plugins_checkboxes'][$plugin_id]['options']['gmap_api_key']))) {
        $form['geocoder']['plugins_checkboxes'][$plugin_id]['options']['#open'] = TRUE;
      }

    }

    // If the Geocoder module has been just enabled, no plugins has been yet
    // selected and there is a not null 'gmap_api_key', then promote the
    // googlemaps plugin as the first one checked.
    if (!empty($config->get('gmap_api_key')) && empty($default_plugins)) {
      $form['geocoder']['plugins_checkboxes']['googlemaps']['weight']['#default_value'] = -10;
      $form['geocoder']['plugins_checkboxes'] = ['googlemaps' => $form['geocoder']['plugins_checkboxes']['googlemaps']] + $form['geocoder']['plugins_checkboxes'];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'geofield_map_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'geofield_map.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get the Geofield Map Settings/Configurations.
    /* @var \Drupal\Core\Config\Config $geofield_map_settings */
    $geofield_map_settings = $this->config('geofield_map.settings');

    // Get all the form state values, in an array structure.
    $form_state_values = $form_state->getValues();

    // Add extended Geofield Map Geocoders settings.
    $form_state_values_geocoder_plugins_options = [];

    // Reset and refill the $form_state geocoder plugins value.
    $form_state_values['geocoder']['plugins'] = [];
    foreach ($form_state_values['geocoder']['plugins_checkboxes'] as $k => $plugin) {

      if (isset($plugin['checked']) && $plugin['checked']) {
        $form_state_values['geocoder']['plugins'][] = $k;
        if (!empty($plugin['options']['json_options'])) {
          $form_state_values_geocoder_plugins_options[$k] = JSON::decode($plugin['options']['json_options']);
        }
        // Set the Google Maps useSsl option, in the Geocoder integration.
        if (isset($plugin['options']['use_ssl']) && !empty($plugin['options']['use_ssl'])) {
          $form_state_values_geocoder_plugins_options[$k]['useSsl'] = $plugin['options']['use_ssl'];
        }
        // Set the Google Maps apiKey plugin option.
        if (isset($plugin['options']['gmap_api_key']) && !empty($plugin['options']['gmap_api_key'])) {
          $form_state_values_geocoder_plugins_options[$k]['apiKey'] = $plugin['options']['gmap_api_key'];
        }
      }

    }

    // Set the geocoder options value as combination of single plugin options.
    $form_state_values['geocoder']['plugins_options'] = JSON::encode($form_state_values_geocoder_plugins_options);

    // If Geocoder module is active, set the (hidden) 'gmap_api_key' form
    // value from the 'googlemaps plugin gmap_api_key' value.
    $geofield_map_settings->set('gmap_api_key', $form_state_values['geocoder']['plugins_checkboxes']['googlemaps']['options']['gmap_api_key']);

    // Update the geofield_map 'geocoder' configurations.
    $geofield_map_settings->set('geocoder', $form_state_values['geocoder']);

    // Save the geofield_map settings updates.
    $geofield_map_settings->save();

    // Output confirmation of the form submission.
    drupal_set_message($this->t('The Geofield Map configurations have been saved.'));
  }

}
