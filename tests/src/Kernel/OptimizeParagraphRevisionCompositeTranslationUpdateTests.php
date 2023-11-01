<?php

namespace Drupal\Tests\entity_reference_revisions\Kernel;

use Drupal\entity_composite_relationship_test\Entity\EntityTestCompositeRelationship;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Entity\ContentLanguageSettings;
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
class OptimizeParagraphRevisionCompositeTranslationUpdateTests extends EntityKernelTestBase {

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
    'optimize_paragraph_revisions',
    'paragraphs',
    'file',
    'language',
    'content_translation'
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

    ConfigurableLanguage::createFromLangcode('de')->save();
//    ConfigurableLanguage::createFromLangcode('fr')->save();

    // Create paragraphs and article content types.
    $values = ['type' => 'article', 'name' => 'Article'];
    $node_type = NodeType::create($values);
    $node_type->save();
    $this->installEntitySchema('node');
    $this->installEntitySchema('paragraph');
    $this->installSchema('node', ['node_access']);
    \Drupal::moduleHandler()->loadInclude('paragraphs', 'install');

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

    // Enable translations on the node type and paragraph type.
    ContentLanguageSettings::loadByEntityTypeBundle('node', 'article')
      ->setLanguageAlterable(TRUE)
      ->setDefaultLangcode('en')
      ->save();

//    ContentLanguageSettings::load($field->id())
//      ->setLanguageAlterable(FALSE)
//      ->setDefaultLangcode('en')
//      ->save();
//    ContentLanguageSettings::loadByEntityTypeBundle('paragraphs_type', 'test_text')
//      ->setLanguageAlterable(TRUE)
//      ->setDefaultLangcode('en')
//      ->save();

//    \Drupal::service('content_translation.manager')->setEnabled('node', 'article', TRUE);
//    \Drupal::service('content_translation.manager')->setEnabled('paragraphs_type', 'test_text', TRUE);
//    \Drupal::service('content_translation.manager')->setBundleTranslationSettings('node', 'article', [
//      'untranslatable_fields_hide' => TRUE,
//    ]);
//    \Drupal::service('entity_type.bundle.info')->clearCachedBundles();
  }

  /**
   * Tests no new paragraph revisions are created when Parent has changes.
   */
  public function testNoRevisionsCreatedForUnChangedParagraphsOnHostChanges(): void {


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
      'langcode' => 'en',
      'node_paragraph_field' => [
        $paragraph1,
        $paragraph2,
      ],
    ]);
    $node->save();


    // Edit host node.
    /** @var \Drupal\node\Entity\Node $node_revision1_de */
    $node_revision1_de = Node::load($node->id());

    $node_revision1_de->addTranslation('de', ['title' => 'Translation of new node #1 DE'] + $node->toArray());
    $node_revision1_de->setNewRevision(TRUE);
    $paragraph1_revision1_de = Paragraph::load($paragraph1->id());
    $paragraph1_revision1_de->addTranslation('de');
    $paragraph1_revision1_de->setNeedsSave(TRUE);
//    $paragraph1_revision1_de->setNewRevision();
    $paragraph2_revision1_de = Paragraph::load($paragraph2->id());
    $paragraph2_revision1_de->addTranslation('de');
    $paragraph2_revision1_de->setNeedsSave(TRUE);
//    $paragraph2_revision1_de->setNewRevision();/
    $node_revision1_de->getTranslation('de')->set('node_paragraph_field',
      [
        $paragraph1_revision1_de,
        $paragraph2_revision1_de,
      ]);
    $node_revision1_de->save();

    // Assert new revision created for host node.
    $this->assertNotEquals($node_revision1_de->getRevisionId(), $node->getRevisionId());
    $this->assertNotEquals($paragraph1_revision1_de->getRevisionId(), $paragraph1->getRevisionId());

    // Assert new paragraph revisions are created after node translation.
    $paragraph1_revisions_count = \Drupal::entityQuery('paragraph')
      ->condition('uuid', $paragraph1->uuid())
      ->allRevisions()
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertEquals(2, $paragraph1_revisions_count);

    $paragraph2_revisions_count = \Drupal::entityQuery('paragraph')
      ->condition('uuid', $paragraph2->uuid())
      ->allRevisions()
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertEquals(2, $paragraph2_revisions_count);

  }

//  public function testNewRevisionsCreatedForChangedParagraphsOnly(): void {
//
//
//    // Create a paragraph.
//    $paragraph1 = Paragraph::create([
//      'title' => 'Paragraph',
//      'type' => 'test_text',
//      'text' => 'Test 1',
//    ]);
//    $paragraph1->save();
//    // Create another paragraph.
//    $paragraph2 = Paragraph::create([
//      'title' => 'Paragraph',
//      'type' => 'test_text',
//      'text' => 'Test 1',
//    ]);
//    $paragraph2->save();
//    // Create another paragraph.
//    $paragraph3 = Paragraph::create([
//      'title' => 'Paragraph',
//      'type' => 'test_text',
//      'text' => 'Test 1',
//    ]);
//    $paragraph3->save();
//
//    // Create a node with three paragraphs.
//    $node = Node::create([
//      'title' => $this->randomMachineName(),
//      'type' => 'article',
//      'langcode' => 'en',
//      'node_paragraph_field' => [
//        $paragraph1,
//        $paragraph2,
//        $paragraph3,
//      ],
//    ]);
//    $node->save();
//
//    // Edit a node and a paragraph.
//    /** @var \Drupal\node\Entity\Node $node_revision1_de */
//    $node_revision1_de = Node::load($node->id());
//    $node_revision1_de->addTranslation('de', ['title' => 'Translation of new node #1 DE'] + $node->toArray());
//    // Edit a paragraph and check
//    $paragraph1_revision1_de = Paragraph::load($paragraph1->id());
//    $paragraph1_revision1_de->addTranslation('de');
//    $paragraph1_revision1_de->getTranslation('de')->set('text', 'Changing text of Paragraph 1 to DE');
//    // Mimic paragraph widget behaviour.
//    $paragraph1_revision1_de->setNeedsSave(TRUE);
//    $node_revision1_de->getTranslation('de')->set('node_paragraph_field', [
//      $paragraph1_revision1_de,
//      $paragraph2,
//      $paragraph3,
//    ]);
//    $node_revision1_de->setNewRevision(TRUE);
//    $node_revision1_de->save();
//
//
//    // Assert new paragraph revisions are created for paragraph 1 only.
//    $paragraph1_revisions_count = \Drupal::entityQuery('paragraph')
//      ->condition('uuid', $paragraph1->uuid())
//      ->allRevisions()
//      ->count()
//      ->accessCheck(TRUE)
//      ->execute();
//    $this->assertEquals(2, $paragraph1_revisions_count);
//    // Assert no new paragraphs created for paragraph 2.
//    $paragraph2_revisions_count = \Drupal::entityQuery('paragraph')
//      ->condition('uuid', $paragraph2->uuid())
//      ->allRevisions()
//      ->count()
//      ->accessCheck(TRUE)
//      ->execute();
//    $this->assertEquals(1, $paragraph2_revisions_count);
//    // Assert no new paragraphs created for paragraph 3.
//    $paragraph3_revisions_count = \Drupal::entityQuery('paragraph')
//      ->condition('uuid', $paragraph3->uuid())
//      ->allRevisions()
//      ->count()
//      ->accessCheck(TRUE)
//      ->execute();
//    $this->assertEquals(1, $paragraph3_revisions_count);
//  }

//  public function testRevisionCreationWhenSetNewRevisionIsFalseInHostAndChangedParagraphIsReferencedBySingleOrMultipleHostRevision(): void {
//
//    // Create a paragraph.
//    $paragraph1 = Paragraph::create([
//      'title' => 'Paragraph',
//      'type' => 'test_text',
//      'text' => 'Test 1',
//    ]);
//    $paragraph1->save();
//    // Create another paragraph.
//    $paragraph2 = Paragraph::create([
//      'title' => 'Paragraph',
//      'type' => 'test_text',
//      'text' => 'Test 1',
//    ]);
//    $paragraph2->save();
//    // Create another paragraph.
//    $paragraph3 = Paragraph::create([
//      'title' => 'Paragraph',
//      'type' => 'test_text',
//      'text' => 'Test 1',
//    ]);
//    $paragraph3->save();
//
//    // Create a node with two paragraphs.
//    $node = Node::create([
//      'title' => $this->randomMachineName(),
//      'type' => 'article',
//      'node_paragraph_field' => [
//        $paragraph1,
//        $paragraph2,
//        $paragraph3,
//      ],
//    ]);
//    $node->save();
//
//    // Edit paragraph1
//    $paragraph1_revision1_de = Paragraph::load($paragraph1->id());
//    $paragraph1_revision1_de->addTranslation('de');
//    $paragraph1_revision1_de->getTranslation('de')->set('text', 'Changing text of Paragraph 1 #1 DE');
//    $paragraph1_revision1_de->setNeedsSave(TRUE);
//
//    /** @var \Drupal\node\Entity\Node $node_revision1_de */
//    $node_revision1_de = Node::load($node->id());
//    $node_revision1_de->addTranslation('de', ['title' => 'Translation of new node #1 DE'] + $node->toArray());
//    $node_revision1_de->setNewRevision(TRUE);
//    $node_revision1_de->save();
//    // Assert new node revision is created.
//    $this->assertNotEquals($node->getRevisionId(), $node_revision1_de->getRevisionId());
//
//    // Assert new revisions are created for paragraph1.
//    $paragraph1_revisions_count = \Drupal::entityQuery('paragraph')
//      ->condition('uuid', $paragraph1->uuid())
//      ->allRevisions()
//      ->count()
//      ->accessCheck(TRUE)
//      ->execute();
//    $this->assertEquals(2, $paragraph1_revisions_count);
//
//
//    //Editing paragraph1 again with host node set to SetNewRevision to False.
//    $paragraph1_revision2_de = Paragraph::load($paragraph1->id());
//    $paragraph1_revision2_de->getTranslation('de')->set('text', 'Changing text of Paragraph 1 #2 DE');
//    // Mimic paragraph widget behavior
//    $paragraph1_revision2_de->setNeedsSave(TRUE);
//    /** @var \Drupal\node\Entity\Node $node_revision2 */
//    $node_revision2_de = Node::load($node->id());
//    $node_revision2_de->addTranslation('de', ['title' => 'Translation of new node #2 DE'] + $node->toArray());
//    $node_revision2_de->setNewRevision(FALSE);
//    $node_revision2_de->save();
//
//    // Assert no new node revision is created.
//    $this->assertEquals($node_revision1_de->getRevisionId(), $node_revision2_de->getRevisionId());
//
//    // Assert no new paragraph revisions are created.
//    $paragraph1_revisions_count = \Drupal::entityQuery('paragraph')
//      ->condition('uuid', $paragraph1->uuid())
//      ->allRevisions()
//      ->count()
//      ->accessCheck(TRUE)
//      ->execute();
//    $this->assertEquals(2, $paragraph1_revisions_count);
//
//    // Edit the other paragraph.
//    $paragraph2_revision1_de = Paragraph::load($paragraph2->id());
//    $paragraph2_revision1_de->addTranslation('de');
//    $paragraph2_revision1_de->getTranslation('de')->set('text', 'Changing text of Paragraph 2 #1 DE');
//
//    // Mimic paragraph widget behavior.
//    $paragraph2_revision1_de->setNeedsSave(TRUE);
//
//    /** @var \Drupal\node\Entity\Node $node_revision1 */
//    $node_revision3_de = Node::load($node->id());
//    $node_revision3_de->addTranslation('de', ['title' => 'Translation of new node #3 DE'] + $node->toArray());
//    $node_revision3_de->setNewRevision(FALSE);
//    $node_revision3_de->save();
//
//    // Assert no new node revisions are created.
//    $this->assertEquals($node_revision1_de->getRevisionId(), $node_revision2_de->getRevisionId());
//    $this->assertEquals($node_revision1_de->getRevisionId(), $node_revision3_de->getRevisionId());
//
//    // Assert no new paragraph revisions are created for paragraph1.
//    $paragraph1_revisions_count = \Drupal::entityQuery('paragraph')
//      ->condition('uuid', $paragraph1->uuid())
//      ->allRevisions()
//      ->count()
//      ->accessCheck(TRUE)
//      ->execute();
//    $this->assertEquals(2, $paragraph1_revisions_count);
//
//    // Assert new revision is created for paragraph2.
//    $paragraph2_revisions_count = \Drupal::entityQuery('paragraph')
//      ->condition('uuid', $paragraph2->uuid())
//      ->allRevisions()
//      ->count()
//      ->accessCheck(TRUE)
//      ->execute();
//    $this->assertEquals(2, $paragraph2_revisions_count);
//
//    // Assert no new paragraph revisions are created for paragraph3.
//    $paragraph3_revisions_count = \Drupal::entityQuery('paragraph')
//      ->condition('uuid', $paragraph3->uuid())
//      ->allRevisions()
//      ->count()
//      ->accessCheck(TRUE)
//      ->execute();
//    $this->assertEquals(1, $paragraph3_revisions_count);
//  }

//  public function testNoRevisionsCreatedWhenSetNewRevisionIsFalseInHostAndParagraphsDoNotChange(): void {
//    // Create the paragraph type.
//    $paragraph_type = ParagraphsType::create([
//      'label' => 'test_text',
//      'id' => 'test_text',
//    ]);
//    $paragraph_type->save();
//
//    // Add a paragraphs field.
//    $field_storage = FieldStorageConfig::create([
//      'field_name' => 'text',
//      'entity_type' => 'paragraph',
//      'type' => 'string',
//      'cardinality' => '-1',
//      'settings' => [],
//    ]);
//    $field_storage->save();
//    $field = FieldConfig::create([
//      'field_storage' => $field_storage,
//      'bundle' => 'test_text',
//      'settings' => [
//        'handler' => 'default:paragraph',
//        'handler_settings' => ['target_bundles' => NULL],
//      ],
//    ]);
//    $field->save();
//
//    // Add a paragraph field to the article.
//    $field_storage = FieldStorageConfig::create([
//      'field_name' => 'node_paragraph_field',
//      'entity_type' => 'node',
//      'type' => 'entity_reference_revisions',
//      'cardinality' => '-1',
//      'settings' => [
//        'target_type' => 'paragraph',
//      ],
//    ]);
//    $field_storage->save();
//    $field = FieldConfig::create([
//      'field_storage' => $field_storage,
//      'bundle' => 'article',
//    ]);
//    $field->save();
//
//    // Create a paragraph.
//    $paragraph1 = Paragraph::create([
//      'title' => 'Paragraph',
//      'type' => 'test_text',
//      'text' => 'Test 1',
//    ]);
//    $paragraph1->save();
//    // Create another paragraph.
//    $paragraph2 = Paragraph::create([
//      'title' => 'Paragraph',
//      'type' => 'test_text',
//      'text' => 'Test 1',
//    ]);
//    $paragraph2->save();
//    // Create another paragraph.
//    $paragraph3 = Paragraph::create([
//      'title' => 'Paragraph',
//      'type' => 'test_text',
//      'text' => 'Test 1',
//    ]);
//    $paragraph3->save();
//
//    // Create a node with three paragraphs.
//    $node = Node::create([
//      'title' => $this->randomMachineName(),
//      'type' => 'article',
//      'node_paragraph_field' => [
//        $paragraph1,
//        $paragraph2,
//        $paragraph3,
//      ],
//    ]);
//    $node->save();
//
//    // Edit a paragraph and check
//    /** @var \Drupal\node\Entity\Node $node_revision1 */
//    $node_revision1 = Node::load($node->id());
//    $node_revision1->setTitle("Changing node title");
//    $node_revision1->setNewRevision(FALSE);
//    $node_revision1->save();
//
//    $this->assertEquals($node_revision1->getRevisionId(), $node->getRevisionId());
//
//    // Assert no new paragraph revisions are created
//    $paragraph1_revisions_count = \Drupal::entityQuery('paragraph')
//      ->condition('uuid', $paragraph1->uuid())
//      ->allRevisions()
//      ->count()
//      ->accessCheck(TRUE)
//      ->execute();
//    $this->assertEquals(1, $paragraph1_revisions_count);
//
//    $paragraph2_revisions_count = \Drupal::entityQuery('paragraph')
//      ->condition('uuid', $paragraph2->uuid())
//      ->allRevisions()
//      ->count()
//      ->accessCheck(TRUE)
//      ->execute();
//    $this->assertEquals(1, $paragraph2_revisions_count);
//
//    $paragraph3_revisions_count = \Drupal::entityQuery('paragraph')
//      ->condition('uuid', $paragraph3->uuid())
//      ->allRevisions()
//      ->count()
//      ->accessCheck(TRUE)
//      ->execute();
//    $this->assertEquals(1, $paragraph3_revisions_count);
//
//  }
}

