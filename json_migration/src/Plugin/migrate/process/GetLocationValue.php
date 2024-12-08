<?php

namespace Drupal\json_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Perform custom value transformations.
 *
 * @MigrateProcessPlugin(
 *   id = "get_location_value"
 * )
 */
class GetLocationValue extends ProcessPluginBase {

  /**
   * {@inheritDoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $latitude = $row->getSource()['location'][0];
    $logitude = $row->getSource()['location'][1];
    return [
      'lat' => $latitude,
      'lng' => $logitude,
    ];
  }

}
