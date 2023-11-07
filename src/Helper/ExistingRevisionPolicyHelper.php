<?php

namespace Drupal\optimize_paragraph_revisions\Helper;

use Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem;

class ExistingRevisionPolicyHelper {

  /**
   * @param \Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem $item
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function isItemReferencedByOtherHostRevisions(EntityReferenceRevisionsItem $item): bool {
    $host_revision_ids = $this->getPreviousRevisionsOfHost($item);
    // Get host entity
    $host_entity = $item->getEntity();
    // Get host storage type.
    $target_type = $host_entity->getEntityTypeId();
    // Check if the previous host revisions are referring to the current
    // revision of the paragraph item.
    foreach ($host_revision_ids as $revision_id) {
      $entity_list = \Drupal::entityTypeManager()
        ->getStorage($target_type)
        ->loadRevision($revision_id)
        ->referencedEntities();
      foreach ($entity_list as $ent) {
        if (
          $ent->getEntityTypeId() === $item->entity->getEntityTypeId() &&
          $ent->getRevisionId() === $item->entity->getRevisionId()
        ) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * @param \Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem $item
   *
   * @return mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getPreviousRevisionsOfHost(EntityReferenceRevisionsItem $item): array {
    $host_entity = $item->getEntity();
    // Get host storage type.
    $target_type = $host_entity->getEntityTypeId();
    // Get all revisions of the host node.
    $host_revision_ids = \Drupal::entityTypeManager()
      ->getStorage($target_type)
      ->revisionIds($host_entity);
    // Sort them in reverse order and remove the current revision.
    rsort($host_revision_ids);
    array_shift($host_revision_ids);

    return $host_revision_ids;
  }

}
