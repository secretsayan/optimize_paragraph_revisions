<?php

namespace Drupal\optimize_paragraph_revisions\Policy;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\TranslatableRevisionableInterface;
use Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem;
use Drupal\entity_reference_revisions\RevisionCreationPolicy\RevisionCreationPolicyInterface;
use Drupal\entity_reference_revisions\RevisionCreationPolicy\CompositeEntityReferencedByNewHostRevisionViaUntranslatableReference;

class CompositeEntityReferencedByNewHostRevisionViaUntranslatableReferenceNeedingSave implements RevisionCreationPolicyInterface {

  use DependencySerializationTrait;

  /**
   * @var \Drupal\entity_reference_revisions\RevisionCreationPolicy\RevisionCreationPolicyInterface
   */
  private RevisionCreationPolicyInterface $inner;

  public function __construct(RevisionCreationPolicyInterface $inner) {
    $this->inner = $inner;
  }

  public function initializer(RevisionCreationPolicyInterface $chain) {
    $chain->addPolicy($this);
    return $chain;
  }

  public function shouldCreateNewRevision(EntityReferenceRevisionsItem $item) {
    if (
      // Previous Policy is TRUE
      $this->inner->shouldCreateNewRevision($item) &&
      // And Item is marked for save
      $item->entity->needsSave()
    ) {
      return TRUE;
    }
  }

}

