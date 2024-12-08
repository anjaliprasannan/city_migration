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
    $form['city_mapping'] = [
      '#type' => 'select',
      '#title' => $this->t('Mapping city Name field'),
      '#options' => $this->getCityFields(),
      '#default_value' => $config->get('field_name'),
    ];

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
    $config->set('field_name', $form_state->getValue('city_mapping'))
      ->save();
    $migration_config = $this->configFactory()->getEditable('migrate_plus.migration.city_entity');
    $process = $migration_config->get('process');
    $process[$config->get('field_name')] =[
      'plugin' => 'get',
      'source' => 'city_name',
    ];
    $migration_config->set('process', $process);
    $migration_config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Run the migration script.
   */
  public function runMigration() {
    $migration = $this->migrationManager->createInstance('city_entity');
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
    $fields = $this->entityTypeManager->getStorage('city')->loadMultiple();
    if (!empty($fields)) {
      $fields = array_keys(reset($fields)->getFields());
      foreach ($fields as $field) {
        $field_options[$field] = $field;
      }
    }
    return $field_options;
  }

}