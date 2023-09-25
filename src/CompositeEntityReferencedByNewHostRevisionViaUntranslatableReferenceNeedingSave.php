<?php

namespace Drupal\custom_paragraph_revision_policy;

use Drupal\Core\Entity\TranslatableRevisionableInterface;
use Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem;
use Drupal\entity_reference_revisions\RevisionCreationPolicy\RevisionCreationPolicyInterface;
use Drupal\entity_reference_revisions\RevisionCreationPolicy\CompositeEntityReferencedByNewHostRevisionViaUntranslatableReference;
class CompositeEntityReferencedByNewHostRevisionViaUntranslatableReferenceNeedingSave implements RevisionCreationPolicyInterface {

  public function initializer(RevisionCreationPolicyInterface $chain) {
    $chain->swapPolicies(new CompositeEntityReferencedByNewHostRevisionViaUntranslatableReference(), $this);
    return $chain;
  }

  public function shouldCreateNewRevision(EntityReferenceRevisionsItem $item) {
    $host = $item->getEntity();
    if (
      // A composite entity
      $item->entity && $item->entity->getEntityType()
        ->get('entity_revision_parent_id_field') &&

      // Referenced by a new host revision
      !$host->isNew() && $host->isNewRevision() &&

      // Via an untranslatable reference
      !$item->getFieldDefinition()->isTranslatable() &&

      // If we need to save the entity
      $item->entity->needsSave()
    ) {
      return TRUE;
    }
  }

}

