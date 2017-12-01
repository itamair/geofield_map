<?php

namespace Drupal\geofield_map\Form;

use Drupal\geofield_map\GeofieldMapFormElementsValidationTrait;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\Config;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

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
   * GeofieldMapSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LinkGeneratorInterface $link_generator,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct($config_factory);
    $this->link = $link_generator;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('link_generator'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('geofield_map.settings');

    $form['#tree'] = TRUE;

    // Attach Geofield Map Library.
    $form['#attached']['library'] = [
      'geofield_map/geofield_map_general',
    ];

    $form['gmap_api_key'] = [
      '#type' => 'textfield',
      '#default_value' => $config->get('gmap_api_key'),
      '#title' => $this->t('Gmap Api Key (@link)', [
        '@link' => $this->link->generate(t('Get a Key/Authentication for Google Maps Javascript Library'), Url::fromUri('https://developers.google.com/maps/documentation/javascript/get-api-key', [
          'absolute' => TRUE,
          'attributes' => ['target' => 'blank'],
        ])),
      ]),
      '#description' => $this->t('Geofield Map requires a valid Google API key for his main features based on Google & Google Maps APIs.'),
      '#placeholder' => $this->t('Input a valid Gmap API Key'),
    ];

    // If the Geocoder Module exists extend the Form with Geocoders Elements.
    if ($this->moduleHandler->moduleExists('geocoder')) {
      $this->addGeocodersFormElements($form, $config);
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
    $geofield_map_settings = $this->configFactory()->getEditable('geofield_map.settings');

    // Get all the form state values, in an array structure.
    $form_state_values = $form_state->getValues();

    // By Default, update the geofield_map 'gmap_api_key' settings
    // with the $form_state_values['gmap_api_key'].
    $geofield_map_settings->set('gmap_api_key', $form_state_values['gmap_api_key']);

    // By default reset the geofield map geocoder settings, if not empty/null.
    if (!empty($geofield_map_settings->get('geocoder'))) {
      $geofield_map_settings->clear('geocoder');
    }

    // Add extended Geofield Map settings for the Geocoder module integration.
    if ($this->moduleHandler->moduleExists('geocoder')) {
      $this->setGeocodersConfigurations($form_state_values, $geofield_map_settings);
    }

    // Save the geofield_map settings updates.
    $geofield_map_settings->save();

    // Output confirmation of the form submission.
    drupal_set_message($this->t('The Geofield Map configurations have been saved.'));
  }

  /**
   * Add Form Elements able to manage Geocoders options from Geocoder Module.
   *
   * @param array $form
   *   The Form.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The configuration.
   */
  protected function addGeocodersFormElements(array &$form, ImmutableConfig $config) {
    $form['geocoder'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Geofield Map Geocoder Settings'),
      '#description' => $this->t('Geofield Map uses the enabled Geocoder Module to integrate external geocoders. Use the following settings to choose and tune the geocoders you want to use and their working weights.'),
      '#description_display' => 'before',
    ];
    $form['geocoder']['min_terms'] = [
      '#type' => 'number',
      '#default_value' => !empty($config->get('geocoder.min_terms')) ? $config->get('geocoder.min_terms') : 4,
      '#title' => $this->t('The (minimum) number of terms for the Geocoder to start processing.'),
      '#description' => $this->t('A too low value (<= 3) will affect the application Geocode Quota usage. Try to increase this value if you are experiencing Quota usage matters.'),
      '#min' => 2,
      '#max' => 10,
      '#size' => 3,
    ];
    $form['geocoder']['delay'] = [
      '#type' => 'number',
      '#default_value' => !empty($config->get('geocoder.delay')) ? $config->get('geocoder.delay') : 800,
      '#title' => $this->t('The delay (in milliseconds) between pressing a key in the Address input field and starting the Geocoder search.'),
      '#description' => $this->t('Valid values ​​for the widget are multiples of 100, between 300 and 5000. A too low value (<= 300) will affect / increase the application Geocode Quota usage. Try to increase this value if you are experiencing Quota usage matters.'),
      '#min' => 300,
      '#max' => 2000,
      '#step' => 100,
      '#size' => 4,
    ];

    // Get the default/selected geocoder plugins.
    $default_plugins = !empty($config->get('geocoder.plugins')) ? $config->get('geocoder.plugins') : [];

    // The form element that stores the set plugins.
    $form['geocoder']['plugins'] = [
      '#type' => 'value',
      '#value' => $default_plugins,
    ];

    // The form element that stores all plugins options.
    $form['geocoder']['options'] = [
      '#type' => 'value',
      '#value' => !empty($config->get('geocoder.options')) ? $config->get('geocoder.options') : '',
    ];

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
      '#caption' => $this->t('<span class="geofield-map-success">The @geocoder_module_link integration is enabled</span>. Select the Geocoder plugins to use. Reorder them in order of priority. The first one able to return a valid value will be used.</br>Add the optional or mandatory options for each Geocoder to make them working properly. Refer to Geocoder Module (and its dependency PHP libraries) for further documentation and specific Geocoders settings.', [
        '@geocoder_module_link' => $this->link->generate(t('Geocoder Module'), Url::fromUri('https://www.drupal.org/project/geocoder')),
      ]),
    ];

    $plugins = array_combine($default_plugins, $default_plugins);
    $plugins_data = $config->get('geocoder.plugins_checkboxes');
    foreach (\Drupal::service('plugin.manager.geocoder.provider')->getPluginsAsOptions() as $plugin_id => $plugin_name) {
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
        'weight' => [
          '#type' => 'weight',
          '#title' => $this->t('Weight for @title', ['@title' => $plugin_name]),
          '#title_display' => 'invisible',
          '#attributes' => ['class' => ['plugins-order-weight']],
        ],
        '#attributes' => ['class' => ['draggable']],
      ];

      $form['geocoder']['plugins_checkboxes'][$plugin_id]['options'] = [
        '#type' => 'details',
        '#title' => $this->t('@title Options', ['@title' => $plugin_name]),
        'json_options' => [
          '#type' => 'textarea',
          '#title' => $this->t('@title Options', ['@title' => $plugin_name]),
          '#title_display' => 'invisible',
          '#rows' => 3,
          '#description' => $this->t('An object literal of Geocoder options. The syntax should respect the javascript object notation (json) format. As suggested in the field placeholder, always use double quotes (") both for the indexes and the string values.'),
          '#default_value' => isset($plugins_data[$plugin_id]) & !empty($plugins_data[$plugin_id]['options']['json_options']) ? $plugins_data[$plugin_id]['options']['json_options'] : '',
          '#placeholder' => '{"key_1": "value_1", "key_2": "value_2", "key_n": "value_n"}',
          '#element_validate' => [[get_class($this), 'jsonValidate']],
        ],
      ];

      // If Geocoder is active, hide the 'gmap_api_key' form to 'value' type.
      $form['gmap_api_key'] = [
        '#type' => 'value',
        '#value' => $config->get('gmap_api_key'),
      ];

      // If it is the 'googlemaps' plugin_id.
      if ($plugin_id == 'googlemaps') {
        $form['geocoder']['plugins_checkboxes'][$plugin_id]['checked']['#states'] = [
          'unchecked' => [
            ':input[name="geocoder[plugins_checkboxes][googlemaps][options][gmap_api_key]"]' => ['value' => ''],
          ],
          'checked' => [
            ':input[name="geocoder[plugins_checkboxes][googlemaps][options][gmap_api_key]"]' => ['!value' => ''],
          ],
        ];
        $form['geocoder']['plugins_checkboxes'][$plugin_id]['options']['gmap_api_key'] = [
          '#weight' => -10,
          '#type' => 'textfield',
          '#default_value' => $config->get('gmap_api_key'),
          '#title' => $this->t('Gmap Api Key (@link)', [
            '@link' => $this->link->generate(t('Get a Key/Authentication for Google Maps Javascript Library'), Url::fromUri('https://developers.google.com/maps/documentation/javascript/get-api-key', [
              'absolute' => TRUE,
              'attributes' => ['target' => 'blank'],
            ])),
          ]),
          '#description' => $this->t('Geofield Map requires a valid Google API key for his main features based on Google & Google Maps APIs.'),
          '#placeholder' => $this->t('Input a valid Gmap API Key'),
        ];
      }

      // As final step, open the plugin options details field,
      // if its 'options content' (or 'gmap_api_key') is not empty.
      if (!empty($form['geocoder']['plugins_checkboxes'][$plugin_id]['options']['json_options']['#default_value'])
        || (isset($form['geocoder']['plugins_checkboxes'][$plugin_id]['options']['gmap_api_key']) && !empty($form['geocoder']['plugins_checkboxes'][$plugin_id]['options']['gmap_api_key']['#default_value']))) {
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
  }

  /**
   * Set additional Geocoders options from Geocoder Module integration.
   *
   * @param array $form_state_values
   *   The Form state values.
   * @param \Drupal\Core\Config\Config $geofield_map_settings
   *   The complete geofield_map_settings to set/update.
   */
  protected function setGeocodersConfigurations(array $form_state_values, Config &$geofield_map_settings) {
    $form_state_values_geocoder_plugins_options = [];

    // Reset and refill the $form_state geocoder plugins value.
    $form_state_values['geocoder']['plugins'] = [];

    foreach ($form_state_values['geocoder']['plugins_checkboxes'] as $k => $plugin) {
      if ($plugin['checked']) {
        $form_state_values['geocoder']['plugins'][] = $k;
        if (!empty($plugin['options']['json_options'])) {
          $form_state_values_geocoder_plugins_options[$k] = JSON::decode($plugin['options']['json_options']);
        }
        if (isset($plugin['options']['gmap_api_key']) && !empty($plugin['options']['gmap_api_key'])) {
          $form_state_values_geocoder_plugins_options[$k]['apiKey'] = $plugin['options']['gmap_api_key'];
        }
      }
    }

    // Set the geocoder options value as combination of single plugin options.
    $form_state_values['geocoder']['options'] = JSON::encode($form_state_values_geocoder_plugins_options);

    // If Geocoder module is active, set the (hidden) 'gmap_api_key' form
    // value from the 'googlemaps plugin gmap_api_key' value.
    $geofield_map_settings->set('gmap_api_key', $form_state_values['geocoder']['plugins_checkboxes']['googlemaps']['options']['gmap_api_key']);

    // Update the geofield_map 'geocoder' configurations.
    $geofield_map_settings->set('geocoder', $form_state_values['geocoder']);
  }

}
