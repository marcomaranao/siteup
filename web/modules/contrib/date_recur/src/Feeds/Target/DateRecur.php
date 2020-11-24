<?php

namespace Drupal\date_recur\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Feeds\Target\DateTime;

/**
 * Defines a string field mapper.
 *
 * @FeedsTarget(
 *   id = "date_recur",
 *   field_types = {"date_recur"}
 * )
 */
class DateRecur extends DateTime {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    return FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('value')
      ->addProperty('end_value');
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    $values['value'] = $this->prepareDateValue($values['value']);
    $values['end_value'] = $this->prepareDateValue($values['end_value']);
    $values['timezone'] = $this->configuration['timezone'];
  }

}