<?php

declare(strict_types=1);

namespace Drupal\custom_entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the city entity edit forms.
 */
final class CityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $messages = ['%label' => $this->entity->toLink()->toString()];
    $logger_args = [
      '%label' => $this->entity->label(),
      'link' => $this->entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New city %label created.', $messages));
        $this->logger('city')->notice('New city %label created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The city %label updated.', $messages));
        $this->logger('city')->notice('The city %label updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Entity not saved.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl());

    return $result;
  }

}