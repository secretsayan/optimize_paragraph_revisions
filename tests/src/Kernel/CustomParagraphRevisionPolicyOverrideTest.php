<?php

namespace Drupal\Tests\entity_reference_revisions\Kernel;

use Drupal\entity_composite_relationship_test\Entity\EntityTestCompositeRelationship;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests the entity_reference_revisions ChainRevisionCreationPolicy.
 *
 * @group entity_reference_revisions
 */
class CustomParagraphRevisionPolicyOverrideTest extends EntityKernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'user',
    'system',
    'field',
    'entity_reference_revisions',
    'entity_composite_relationship_test',
    'custom_paragraph_revision_policy',
    'paragraphs',
  ];

  /**
   * The current database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   *
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create paragraphs and article content types.
    $values = ['type' => 'article', 'name' => 'Article'];
    $node_type = NodeType::create($values);
    $node_type->save();
    $this->installEntitySchema('node');
    $this->installEntitySchema('paragraph');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);
    \Drupal::moduleHandler()->loadInclude('paragraphs', 'install');
//
//    $this->installEntitySchema('entity_test_composite');
//    $this->installSchema('node', ['node_access']);
//
//    // Create article content type.
//    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();
//
//    // Create the reference to the composite entity test.
//    $field_storage = FieldStorageConfig::create([
//      'field_name' => 'composite_reference',
//      'entity_type' => 'node',
//      'type' => 'entity_reference_revisions',
//      'settings' => [
//        'target_type' => 'entity_test_composite',
//      ],
//    ]);
//    $field_storage->save();
//    $field = FieldConfig::create([
//      'field_storage' => $field_storage,
//      'bundle' => 'article',
//      'translatable' => FALSE,
//    ]);
//    $field->save();
//
//    // Inject database connection, entity type manager and cron for the tests.
//    $this->database = \Drupal::database();
//    $this->entityTypeManager = \Drupal::entityTypeManager();
//    $this->cron = \Drupal::service('cron');


  }

  /**
   * Tests entity_reference_revisions policies can be overridden.
   */
  public function testCustomParagraphRevisionPolicySwap() {
//    // Create the test composite entity.
//    $composite = EntityTestCompositeRelationship::create([
//      'uuid' => $this->randomMachineName(),
//      'name' => $this->randomMachineName(),
//    ]);
//    $composite->save();
//
//    // Assert that there is only 1 revision of the composite entity.
//    $composite_revisions_count = \Drupal::entityQuery('entity_test_composite')
//      ->condition('uuid', $composite->uuid())
//      ->allRevisions()
//      ->count()
//      ->accessCheck(TRUE)
//      ->execute();
//    $this->assertEquals(1, $composite_revisions_count);
//
//    // Create a node with a reference to the test composite entity.
//    /** @var \Drupal\node\NodeInterface $node */
//    $node = Node::create([
//      'title' => $this->randomMachineName(),
//      'type' => 'article',
//    ]);
//    $node->save();
//    $node->set('composite_reference', $composite);
//    $this->assertTrue($node->hasTranslationChanges());
//    $node->save();
//
//    // Assert that there is only 1 revision when creating a node.
//    $node_revisions_count = \Drupal::entityQuery('node')
//      ->condition('nid', $node->id())
//      ->allRevisions()
//      ->count()
//      ->accessCheck(TRUE)
//      ->execute();
//    $this->assertEquals(1, $node_revisions_count);
//    // Assert there is no new composite revision after creating a host entity.
//    $composite_revisions_count = \Drupal::entityQuery('entity_test_composite')
//      ->condition('uuid', $composite->uuid())
//      ->allRevisions()
//      ->count()
//      ->accessCheck(TRUE)
//      ->execute();
//    $this->assertEquals(1, $composite_revisions_count);
//
//    // Verify the value of parent type and id after create a node.
//    $composite = EntityTestCompositeRelationship::load($composite->id());
//    $this->assertEquals($node->getEntityTypeId(), $composite->parent_type->value);
//    $this->assertEquals($node->id(), $composite->parent_id->value);
//    $this->assertEquals('composite_reference', $composite->parent_field_name->value);
//    // Create second revision of the node.
//    $original_composite_revision = $node->composite_reference[0]->target_revision_id;
//    $original_node_revision = $node->getRevisionId();
//    $node->setTitle('2nd revision');
//    $node->setNewRevision();
//    $node->save();
//    $node = Node::load($node->id());
//    // Check the revision of the node.
//    $this->assertEquals('2nd revision', $node->getTitle(), 'New node revision has changed data.');
//    //Check composite entity revisions
//    $this->assertEquals($original_composite_revision, $node->composite_reference[0]->target_revision_id, 'Composite entity got no new revision when its host did.');
//
//    // Make sure that there are only 1 revision for the composite entity
//    $node_revisions_count = \Drupal::entityQuery('entity_test_composite')
//      ->condition('uuid', $composite->uuid())
//      ->allRevisions()
//      ->count()
//      ->accessCheck(TRUE)
//      ->execute();
//    $this->assertEquals(1, $node_revisions_count);
  }

}


