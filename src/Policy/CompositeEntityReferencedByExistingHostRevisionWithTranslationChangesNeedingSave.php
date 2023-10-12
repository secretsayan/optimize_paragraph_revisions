<?php

namespace Drupal\optimize_paragraph_revisions\Policy;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\TranslatableRevisionableInterface;
use Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem;
use Drupal\entity_reference_revisions\RevisionCreationPolicy\RevisionCreationPolicyInterface;
use Drupal\entity_reference_revisions\RevisionCreationPolicy\CompositeEntityReferencedByNewHostRevisionWithTranslationChanges;
use Drupal\optimize_paragraph_revisions\Helper\ExistingRevisionPolicyHelper;

class CompositeEntityReferencedByExistingHostRevisionWithTranslationChangesNeedingSave implements RevisionCreationPolicyInterface {

  use DependencySerializationTrait;

  /**
   * @var \Drupal\entity_reference_revisions\RevisionCreationPolicy\RevisionCreationPolicyInterface
   */
  private RevisionCreationPolicyInterface $inner;

  /**
   * @var \Drupal\optimize_paragraph_revisions\Helper\ExistingRevisionPolicyHelper
   */
  private ExistingRevisionPolicyHelper $policyHelper;

  public function __construct(ExistingRevisionPolicyHelper $policyHelper) {
    $this->policyHelper = $policyHelper;
  }

  public function initializer(RevisionCreationPolicyInterface $chain) {
    $chain->addPolicy($this);
    return $chain;
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function shouldCreateNewRevision(EntityReferenceRevisionsItem $item) {
    $host_entity = $item->getEntity();
    if (
      // A composite entity
      $item->entity && $item->entity->getEntityType()
        ->get('entity_revision_parent_id_field') &&

      // Referenced by a existing host revision
      !$host_entity->isNew() && !$host_entity->isNewRevision() &&

      // With translation changes
      $host_entity instanceof TranslatableRevisionableInterface && $host_entity->hasTranslationChanges() &&

      // If we need to save the entity
      $item->entity->needsSave() &&

      //If item is referenced by Other Host Revisions
      $this->policyHelper->isItemReferencedByOtherHostRevisions($item)

    ) {
      return TRUE;
    }
  }

}

