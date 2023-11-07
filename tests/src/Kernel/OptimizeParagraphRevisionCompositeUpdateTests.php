<?php

namespace Drupal\Tests\entity_reference_revisions\Kernel;

use Drupal\entity_composite_relationship_test\Entity\EntityTestCompositeRelationship;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests the entity_reference_revisions ChainRevisionCreationPolicy.
 *
 * @group entity_reference_revisions
 */
class OptimizeParagraphRevisionCompositeUpdateTests extends EntityKernelTestBase {

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
    'optimize_paragraph_revisions',
    'paragraphs',
    'file',
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
    $this->installSchema('node', ['node_access']);
    \Drupal::moduleHandler()->loadInclude('paragraphs', 'install');
  }

  /**
   * Tests no new paragraph revisions are created when Parent has changes.
   */
  public function testNoRevisionsCreatedForUnChangedParagraphsOnHostChanges(): void {
    // Create the paragraph type.
    $paragraph_type = ParagraphsType::create([
      'label' => 'test_text',
      'id' => 'test_text',
    ]);
    // Add a paragraph field to the article.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'node_paragraph_field',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'cardinality' => '-1',
      'settings' => [
        'target_type' => 'paragraph',
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
    ]);
    $field->save();

    // Create a paragraph.
    $paragraph1 = Paragraph::create([
      'title' => 'Paragraph',
      'type' => 'test_text',
    ]);
    $paragraph1->save();
    // Create another paragraph.
    $paragraph2 = Paragraph::create([
      'title' => 'Paragraph',
      'type' => 'test_text',
    ]);
    $paragraph2->save();

    // Create a node with two paragraphs.
    $node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'article',
      'node_paragraph_field' => [
        $paragraph1,
        $paragraph2,
      ],
    ]);
    $node->save();

    // Edit a paragraph and check
    /** @var \Drupal\node\Entity\Node $node_revision1 */
    $node_revision1 = Node::load($node->id());
    $node_revision1->setTitle("Changing node title");
    $node_revision1->setNewRevision(TRUE);
    $node_revision1->save();

    // Assert new revision created for host node.
    $this->assertNotEquals($node_revision1->getRevisionId(), $node->getRevisionId());
    // Assert no new paragraph revisions are created
    $paragraph1_revisions_count = \Drupal::entityQuery('paragraph')
      ->condition('uuid', $paragraph1->uuid())
      ->allRevisions()
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertEquals(1, $paragraph1_revisions_count);

    $paragraph2_revisions_count = \Drupal::entityQuery('paragraph')
      ->condition('uuid', $paragraph2->uuid())
      ->allRevisions()
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertEquals(1, $paragraph2_revisions_count);
  }

  public function testNewRevisionsCreatedForChangedParagraphsOnly(): void {
    // Create the paragraph type.
    $paragraph_type = ParagraphsType::create([
      'label' => 'test_text',
      'id' => 'test_text',
    ]);
    $paragraph_type->save();

    // Add a paragraphs field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'text',
      'entity_type' => 'paragraph',
      'type' => 'string',
      'cardinality' => '-1',
      'settings' => [],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test_text',
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => ['target_bundles' => NULL],
      ],
    ]);
    $field->save();

    // Add a paragraph field to the article.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'node_paragraph_field',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'cardinality' => '-1',
      'settings' => [
        'target_type' => 'paragraph',
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
    ]);
    $field->save();

    // Create a paragraph.
    $paragraph1 = Paragraph::create([
      'title' => 'Paragraph',
      'type' => 'test_text',
      'text' => 'Test 1',
    ]);
    $paragraph1->save();
    // Create another paragraph.
    $paragraph2 = Paragraph::create([
      'title' => 'Paragraph',
      'type' => 'test_text',
      'text' => 'Test 1',
    ]);
    $paragraph2->save();
    // Create another paragraph.
    $paragraph3 = Paragraph::create([
      'title' => 'Paragraph',
      'type' => 'test_text',
      'text' => 'Test 1',
    ]);
    $paragraph3->save();

    // Create a node with two paragraphs.
    $node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'article',
      'node_paragraph_field' => [
        $paragraph1,
        $paragraph2,
        $paragraph3,
      ],
    ]);
    $node->save();

    // Edit a paragraph and check
    $paragraph1_revision1 = Paragraph::load($paragraph1->id());
    $paragraph1_revision1->set('text', 'Changing text of Paragraph 1');
    // Mimic paragraph widget behaviour.
    $paragraph1_revision1->setNeedsSave(TRUE);
    /** @var \Drupal\node\Entity\Node $node_revision1 */
    $node_revision1 = Node::load($node->id());
    $node_revision1->setNewRevision(TRUE);
    $node_revision1->set('node_paragraph_field', [
      $paragraph1_revision1,
      $paragraph2,
      $paragraph3,
    ]);
    $node_revision1->save();

    // Assert new paragraph revisions are created for paragraph 1 only.
    $paragraph1_revisions_count = \Drupal::entityQuery('paragraph')
      ->condition('uuid', $paragraph1->uuid())
      ->allRevisions()
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertEquals(2, $paragraph1_revisions_count);
    // Assert no new paragraphs created for paragraph 2.
    $paragraph2_revisions_count = \Drupal::entityQuery('paragraph')
      ->condition('uuid', $paragraph2->uuid())
      ->allRevisions()
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertEquals(1, $paragraph2_revisions_count);
    // Assert no new paragraphs created for paragraph 3.
    $paragraph3_revisions_count = \Drupal::entityQuery('paragraph')
      ->condition('uuid', $paragraph3->uuid())
      ->allRevisions()
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertEquals(1, $paragraph3_revisions_count);
  }

  public function testRevisionCreationWhenSetNewRevisionIsFalseInHostAndChangedParagraphIsReferencedBySingleOrMultipleHostRevision(): void {
    // Create the paragraph type.
    $paragraph_type = ParagraphsType::create([
      'label' => 'test_text',
      'id' => 'test_text',
    ]);
    $paragraph_type->save();

    // Add a paragraphs field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'text',
      'entity_type' => 'paragraph',
      'type' => 'string',
      'cardinality' => '-1',
      'settings' => [],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test_text',
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => ['target_bundles' => NULL],
      ],
    ]);
    $field->save();

    // Add a paragraph field to the article.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'node_paragraph_field',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'cardinality' => '-1',
      'settings' => [
        'target_type' => 'paragraph',
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
    ]);
    $field->save();

    // Create a paragraph.
    $paragraph1 = Paragraph::create([
      'title' => 'Paragraph',
      'type' => 'test_text',
      'text' => 'Test 1',
    ]);
    $paragraph1->save();
    // Create another paragraph.
    $paragraph2 = Paragraph::create([
      'title' => 'Paragraph',
      'type' => 'test_text',
      'text' => 'Test 1',
    ]);
    $paragraph2->save();
    // Create another paragraph.
    $paragraph3 = Paragraph::create([
      'title' => 'Paragraph',
      'type' => 'test_text',
      'text' => 'Test 1',
    ]);
    $paragraph3->save();

    // Create a node with two paragraphs.
    $node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'article',
      'node_paragraph_field' => [
        $paragraph1,
        $paragraph2,
        $paragraph3,
      ],
    ]);
    $node->save();

    // Edit paragraph1
    $paragraph1_revision1 = Paragraph::load($paragraph1->id());
    $paragraph1_revision1->set('text', 'Changing text of Paragraph 1');
    $paragraph1_revision1->setNeedsSave(TRUE);

    /** @var \Drupal\node\Entity\Node $node_revision1 */
    $node_revision1 = Node::load($node->id());
    $node_revision1->setTitle("Changing node title");
    $node_revision1->setNewRevision(TRUE);
    $node_revision1->save();
    // Assert new node revision is created.
    $this->assertNotEquals($node->getRevisionId(), $node_revision1->getRevisionId());

    // Assert new revisions are created for paragraph1.
    $paragraph1_revisions_count = \Drupal::entityQuery('paragraph')
      ->condition('uuid', $paragraph1->uuid())
      ->allRevisions()
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertEquals(2, $paragraph1_revisions_count);

    //Editing paragraph1 again with host node set to SetNewRevision to False.
    $paragraph1_revision2 = Paragraph::load($paragraph1->id());
    $paragraph1_revision2->set('text', 'Changing text of Paragraph 1 again ');
    // Mimic paragraph widget
    $paragraph1_revision2->setNeedsSave(TRUE);
    /** @var \Drupal\node\Entity\Node $node_revision2 */
    $node_revision2 = Node::load($node->id());
    $node_revision2->setTitle("Changing node title");
    $node_revision2->setNewRevision(FALSE);
    $node_revision2->save();

    // Assert no new node revision is created.
    $this->assertEquals($node_revision1->getRevisionId(), $node_revision2->getRevisionId());

    // Assert no new paragraph revisions are created.
    $paragraph1_revisions_count = \Drupal::entityQuery('paragraph')
      ->condition('uuid', $paragraph1->uuid())
      ->allRevisions()
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertEquals(2, $paragraph1_revisions_count);

    // Edit a paragraph
    $paragraph2_revision1 = Paragraph::load($paragraph2->id());
    $paragraph2_revision1->set('text', 'Changing text of Paragraph 2');
    // Mimic paragraph widget behavior.
    $paragraph2_revision1->setNeedsSave(TRUE);

    /** @var \Drupal\node\Entity\Node $node_revision1 */
    $node_revision3 = Node::load($node->id());
    $node_revision3->setTitle("Changing node title");
    $node_revision3->setNewRevision(FALSE);
    $node_revision3->save();

    $this->assertNotEquals($node->getRevisionId(), $node_revision1->getRevisionId());
    $this->assertEquals($node_revision1->getRevisionId(), $node_revision2->getRevisionId());
    $this->assertEquals($node_revision1->getRevisionId(), $node_revision3->getRevisionId());

    // Assert no new paragraph revisions are created
    $paragraph1_revisions_count = \Drupal::entityQuery('paragraph')
      ->condition('uuid', $paragraph1->uuid())
      ->allRevisions()
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertEquals(2, $paragraph1_revisions_count);

    // Asset new revision is created for paragraph2.
    $paragraph2_revisions_count = \Drupal::entityQuery('paragraph')
      ->condition('uuid', $paragraph2->uuid())
      ->allRevisions()
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertEquals(2, $paragraph2_revisions_count);
    // Assert no new paragraph revisions are created for paragraph3.
    $paragraph3_revisions_count = \Drupal::entityQuery('paragraph')
      ->condition('uuid', $paragraph3->uuid())
      ->allRevisions()
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertEquals(1, $paragraph3_revisions_count);
  }

  public function testNoRevisionsCreatedWhenSetNewRevisionIsFalseInHostAndParagraphsDoNotChange(): void {
    // Create the paragraph type.
    $paragraph_type = ParagraphsType::create([
      'label' => 'test_text',
      'id' => 'test_text',
    ]);
    $paragraph_type->save();

    // Add a paragraphs field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'text',
      'entity_type' => 'paragraph',
      'type' => 'string',
      'cardinality' => '-1',
      'settings' => [],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test_text',
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => ['target_bundles' => NULL],
      ],
    ]);
    $field->save();

    // Add a paragraph field to the article.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'node_paragraph_field',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'cardinality' => '-1',
      'settings' => [
        'target_type' => 'paragraph',
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
    ]);
    $field->save();

    // Create a paragraph.
    $paragraph1 = Paragraph::create([
      'title' => 'Paragraph',
      'type' => 'test_text',
      'text' => 'Test 1',
    ]);
    $paragraph1->save();
    // Create another paragraph.
    $paragraph2 = Paragraph::create([
      'title' => 'Paragraph',
      'type' => 'test_text',
      'text' => 'Test 1',
    ]);
    $paragraph2->save();
    // Create another paragraph.
    $paragraph3 = Paragraph::create([
      'title' => 'Paragraph',
      'type' => 'test_text',
      'text' => 'Test 1',
    ]);
    $paragraph3->save();

    // Create a node with three paragraphs.
    $node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'article',
      'node_paragraph_field' => [
        $paragraph1,
        $paragraph2,
        $paragraph3,
      ],
    ]);
    $node->save();

    // Edit a paragraph and check
    /** @var \Drupal\node\Entity\Node $node_revision1 */
    $node_revision1 = Node::load($node->id());
    $node_revision1->setTitle("Changing node title");
    $node_revision1->setNewRevision(FALSE);
    $node_revision1->save();

    $this->assertEquals($node_revision1->getRevisionId(), $node->getRevisionId());

    // Assert no new paragraph revisions are created
    $paragraph1_revisions_count = \Drupal::entityQuery('paragraph')
      ->condition('uuid', $paragraph1->uuid())
      ->allRevisions()
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertEquals(1, $paragraph1_revisions_count);

    $paragraph2_revisions_count = \Drupal::entityQuery('paragraph')
      ->condition('uuid', $paragraph2->uuid())
      ->allRevisions()
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertEquals(1, $paragraph2_revisions_count);

    $paragraph3_revisions_count = \Drupal::entityQuery('paragraph')
      ->condition('uuid', $paragraph3->uuid())
      ->allRevisions()
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertEquals(1, $paragraph3_revisions_count);
  }

}
