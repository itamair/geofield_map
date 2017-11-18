<?php

namespace Drupal\geofield_map\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;

/**
 * Implements the GeofieldMapSettingsForm controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class GeofieldMapSettingsForm extends ConfigFormBase {

  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;

  /**
   * GeofieldMapSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LinkGeneratorInterface $link_generator) {
    parent::__construct($config_factory);
    $this->link = $link_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('link_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('geofield_map.settings');

    $form['#tree'] = TRUE;

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

    $form['geocoder'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Geocoder Settings'),
    ];
    $form['geocoder']['min_terms'] = [
      '#type' => 'number',
      '#default_value' => !empty($config->get('geocoder.min_terms')) ? $config->get('geocoder.min_terms') : 4,
      '#title' => $this->t('The minimum terms number for the Geocoder to start processing'),
      '#description' => $this->t('A too low value (<= 3) will affect the application Geocode Quota usage. Try to increase this value if you are experiencing Quota usage matters'),
      '#min' => 2,
      '#max' => 10,
      '#size' => 3,
    ];
    $form['geocoder']['delay'] = [
      '#type' => 'number',
      '#default_value' => !empty($config->get('geocoder.delay')) ? $config->get('geocoder.delay') : 800,
      '#title' => $this->t('The delay in milliseconds between when a keystroke occurs and when a Geocode search is started/performed'),
      '#description' => $this->t('Valid values ​​for the widget are multiples of 100, between 300 and 5000. A too low value (<= 300) will affect / increase the application Geocode Quota usage. Try to increase this value if you are experiencing Quota usage matters'),
      '#min' => 300,
      '#max' => 5000,
      '#step' => 100,
      '#size' => 3,
    ];

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
    $config = $this->configFactory()->getEditable('geofield_map.settings');

    $config->setData($form_state->getValues());

    $config->save();

    // Confirmation on form submission.
    drupal_set_message($this->t('The Geofield Map configurations have been saved.'));
  }

}
