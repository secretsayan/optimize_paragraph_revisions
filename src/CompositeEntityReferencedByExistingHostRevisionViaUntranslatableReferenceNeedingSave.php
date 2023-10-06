<?php

namespace Drupal\optimize_paragraph_revisions;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\TranslatableRevisionableInterface;
use Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem;
use Drupal\entity_reference_revisions\RevisionCreationPolicy\RevisionCreationPolicyInterface;
use Drupal\entity_reference_revisions\RevisionCreationPolicy\CompositeEntityReferencedByNewHostRevisionViaUntranslatableReference;

class CompositeEntityReferencedByExistingHostRevisionViaUntranslatableReferenceNeedingSave implements RevisionCreationPolicyInterface {

  use DependencySerializationTrait;

  /**
   * @var \Drupal\entity_reference_revisions\RevisionCreationPolicy\RevisionCreationPolicyInterface
   */
  private RevisionCreationPolicyInterface $inner;


  public function initializer(RevisionCreationPolicyInterface $chain) {
    $chain->addPolicy($this);
    return $chain;
  }

  public function shouldCreateNewRevision(EntityReferenceRevisionsItem $item) {
        $host = $item->getEntity();
        if (
          // A composite entity
          $item->entity && $item->entity->getEntityType()
            ->get('entity_revision_parent_id_field') &&

          // Referenced by a new host revision
          !$host->isNew() && !$host->isNewRevision() &&

          // Via an untranslatable reference
          !$item->getFieldDefinition()->isTranslatable() &&

          // If we need to save the entity
          $item->entity->needsSave()
        ) {
          return TRUE;
        }
//    if (
//      // Previous Policy is TRUE
//      $this->inner->shouldCreateNewRevision($item) &&
//      // And Item is marked for save
//      $item->entity->needsSave()
//    ) {
//      return TRUE;
//    }
  }

}

