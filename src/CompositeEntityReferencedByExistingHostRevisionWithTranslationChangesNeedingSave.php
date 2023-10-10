<?php

namespace Drupal\optimize_paragraph_revisions;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\TranslatableRevisionableInterface;
use Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem;
use Drupal\entity_reference_revisions\RevisionCreationPolicy\RevisionCreationPolicyInterface;
use Drupal\entity_reference_revisions\RevisionCreationPolicy\CompositeEntityReferencedByNewHostRevisionWithTranslationChanges;

class CompositeEntityReferencedByExistingHostRevisionWithTranslationChangesNeedingSave implements RevisionCreationPolicyInterface {

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

          // Referenced by a existing host revision
          !$host->isNew() && !$host->isNewRevision() &&

          // With translation changes
          $host instanceof TranslatableRevisionableInterface && $host->hasTranslationChanges() &&

          // If we need to save the entity
          $item->entity->needsSave()) {

          $rev_id = $item->target_revision_id;


          # options 1. we can always create a new revision of the paragraph including in scenarios where "create new revision" is unchecked.
          # options 2.Find out if same revision is referenced by other host node revisions
          $target_type =  $host->getEntityType()->getClass();
          \Drupal::logger('info')->info("bundle lable, req node : " . $target_type);

          $node = \Drupal::entityTypeManager()->getStorage('node')
            ->loadByProperties(
              [
                'nid' => $host->id()
              ]
            );

          \Drupal::logger('log type manager')->info('node object' . print_r( TRUE));

//          $revisions2 = \Drupal::entityQuery('node')
//            ->condition('nid', $host->id())
////            ->currentRevision()
////            ->latestRevision()
//            ->allRevisions()
////            ->condition('target_revision_id', $rev_id)
//            ->accessCheck(FALSE)
//            ->execute();
//
//          \Drupal::logger('log query')->info('revisions ids' . print_r($revisions2, TRUE));

          $revisionIds_host = \Drupal::entityTypeManager()->getStorage('node')->revisionIds($host);
          $count_id = count($revisionIds_host);
          \Drupal::logger('log')->info('count ids' . $count_id);
          $id = $revisionIds_host[$count_id - 2];
          \Drupal::logger('log')->info('prev ids' . $id);


//
          $entity_list = \Drupal::entityTypeManager()->getStorage('node')->loadRevision($id)->referencedEntities();

          foreach($entity_list as $ent){
            $para = $item->entity;
            if(($ent->getEntityTypeId() === $para->getEntityTypeId()) && $ent->getRevisionId() === $para->getRevisionId()) {
              \Drupal::logger('entity type')->info("Paragraph matches");
              return TRUE;
            }
          }


//          foreach ($entity_list as $delta => $items) {
//            \Drupal::logger('rev check')->info(print_r($items, TRUE));
//
////            if ($item->target_revision_id !== NULL) {
////
////            }
//
//          }

//          \Drupal::logger('log')->info('host id' . $host->id());
//          \Drupal::logger('rev id')->info('Revision ID' . $rev_id);
//          \Drupal::logger('rev id')->info('Revision count ' . print_r($revisionIds_host, TRUE));
//          \Drupal::logger('rev id')->info('Revision2 count ' . print_r($revisions2, TRUE));

//          foreach($entity_list as $ent){
//
//
//            \Drupal::logger('logid')->info('show paragraph ids' . $ent->target_id);
//            if($ent->id() === $rev_id){
//              \Drupal::logger('log')->info('Match found, create new revision');
//              return TRUE;
//              break;
//            }
//          }

//          \Drupal::logger('rev id')->info('entities ref ' . print_r($entities, TRUE));


//          $host->getOriginalId();
//          $host->referencedEntities();
//          $host->id();




          return FALSE;
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

