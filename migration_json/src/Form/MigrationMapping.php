<?php

namespace Drupal\migration_json\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\migrate_tools\MigrateBatchExecutable;

/**
 * Call for Migration Mapping interface.
 */
class MigrationMapping extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'json_migration.settings';

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The config Factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Migrate Plugin Manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrationManager;

  /**
   * The controller function.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityTypeManager $entityTypeManager, MigrationPluginManager $migrationManager) {
    parent::__construct($configFactory);
    $this->entityTypeManager = $entityTypeManager;
    $this->migrationManager = $migrationManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.migration'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'json_migration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

  // Fetch source fields from migration configuration.
  $source_fields = $this->getSourceFields();

    // Fetch city entity fields.
    $city_entity_fields = $this->getCityFields();

    $form['city_mapping'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Source Field'),
          $this->t('City Entity Field'),
        ],
      ];

    // Loop through the source fields and build rows for mappings.
    foreach ($source_fields as $field_name => $label) {
        $form['city_mapping'][$field_name]['source'] = [
        '#markup' => $label,
        ];
        $form['city_mapping'][$field_name]['destination'] = [
        '#type' => 'select',
        '#options' => $city_entity_fields,
        '#default_value' => $config->get('mappings.' . $field_name) ?: '',
        ];
    }
   

    $form['run_migration'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run Migration'),
      '#submit' => [
        '::runMigration',
      ],
    ];
    
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);
    $mappings = [];
    $field_mappings = $form_state->getValue('city_mapping');

      foreach ($field_mappings as $source_field => $mapping) {
        $destination_field = $mapping['destination'];
        if (!empty($destination_field)) {
          $mappings[$source_field] = $destination_field;
        }
      }
      
    $config->set('mappings', $mappings)->save();
    // Update the migration configuration.
    $this->updateMigrationConfig($mappings);


    $migration_config = $this->configFactory()->getEditable('migrate_plus.migration.city_entity');
    $process = $migration_config->get('process');

        foreach ($config->get('mappings') as $source_field => $destination_field) {
            $process[$destination_field] = [
                'plugin' => 'get',
                'source' => $source_field,
            ];
        }
    
    $migration_config->set('process', $process);
    $migration_config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Run the migration script.
   */
  public function runMigration($migration_config) {
    $migration_config = $this->configFactory()->getEditable('migrate_plus.migration.city_entity');
    $process = $migration_config->get('process');
    $migration = $this->migrationManager->createInstance('city_entity');
    $migration->setProcess($process);
    if (!empty($migration)) {
      $options = [
        'limit' => 0,
        'update' => 1,
        'force' => 1,
      ];
      $executable = new MigrateBatchExecutable($migration, new MigrateMessage(), $options);
      $executable->batchImport();
    }
  }

  /**
   * Get the city fields.
   *
   * @return field_options
   *   The options for the field.
   */
  protected function getCityFields() {
    $field_options = [];
    $fields = $this->entityTypeManager->getStorage('city')->loadMultiple();
    if (!empty($fields)) {
      $fields = array_keys(reset($fields)->getFields());
      foreach ($fields as $field) {
        $field_options[$field] = $field;
      }
    }
    return $field_options;
  }

  protected function getSourceFields() {
    $migration_config = $this->configFactory()->get('migrate_plus.migration.city_entity');
    $fields = $migration_config->get('source.fields');
    $source_fields = [];
    foreach ($fields as $field) {
      $source_fields[$field['name']] = $field['label'];
    }
    return $source_fields;
  }

  protected function updateMigrationConfig(array $mappings) {
    $migration_config = $this->configFactory()->getEditable('migrate_plus.migration.city_entity');
  
    $process = [];
    
    foreach ($mappings as $source_field => $destination_field) {
        if (!isset($process[$destination_field])) {
            $process[$destination_field] = [
                'plugin' => 'get',
                'source' => [],
            ];
        }
        $process[$destination_field]['source'][] = $source_field;
    }
    // Update the process section dynamically.
    $migration_config->set('process', $process)->save();
  }
  
}
