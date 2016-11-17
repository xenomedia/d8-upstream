<?php

namespace Drupal\Tests\replication\Unit\Normalizer;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\user\Entity\User;

/**
 * Tests the entity reference serialization format.
 *
 * @group replication
 */
class EntityReferenceItemNormalizerTest extends NormalizerTestBase {

  protected $entityClass = 'Drupal\entity_test\Entity\EntityTest';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['sequences']);
  }

  /**
   * Tests normalization of entity reference fields that reference users.
   *
   * @todo Write a test of user ID mapping using normalization.
   *
   * @todo Write a test of entity references to other entity types, since
   * EntityReferenceItemNormalizer does special handling for users.
   */
  public function testUserReferenceFieldNormalization() {
    $author = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@localhost',
    ]);
    $author->save();

    // Create a test entity to serialize.
    $entity = EntityTestMulRev::create([
      'name' => $this->randomMachineName(),
      'user_id' => $author->id(),
    ]);
    $entity->save();

    $expected = array(
      '@context' => array(
        '_id' => '@id',
        '@language' => 'en'
      ),
      '@type' => 'entity_test_mulrev',
      'en' => [
        '@context' => [
          '@language' => 'en',
        ],
        'langcode' => [
          ['value' => 'en'],
        ],
        'name' => [
          [
            'value' => $entity->getName(),
          ],
        ],
        'type' => [
          ['value' => 'entity_test_mulrev'],
        ],
        'created' => [
          ['value' => $entity->created->value],
        ],
        'default_langcode' => [
          ['value' => TRUE],
        ],
        'user_id' => [
          [
            'entity_type_id' => $author->getEntityTypeId(),
            'target_uuid' => $author->uuid(),
            'username' => $author->label(),
          ],
        ],
        '_rev' => [
          ['value' => $entity->_rev->value],
        ],
        'non_rev_field' => [],
        'field_test_text' => [],
      ],
      '_id' => $entity->uuid(),
      '_rev' => $entity->_rev->value,
    );

    // Test normalize.
    $normalized = $this->serializer->normalize($entity);
    foreach (array_keys($expected) as $key) {
      $this->assertEquals($expected[$key], $normalized[$key], "Field $key is normalized correctly.");
    }
    $this->assertEquals(array_diff_key($normalized, $expected), [], 'No unexpected data is added to the normalized array.');

    // Test denormalize.
    $denormalized = $this->serializer->denormalize($normalized, $this->entityClass, 'json');
    $this->assertTrue($denormalized instanceof $this->entityClass, SafeMarkup::format('Denormalized entity is an instance of @class', ['@class' => $this->entityClass]));
    $this->assertSame($denormalized->getEntityTypeId(), $entity->getEntityTypeId(), 'Expected entity type found.');
    $this->assertSame($denormalized->bundle(), $entity->bundle(), 'Expected entity bundle found.');
    $this->assertSame($denormalized->uuid(), $entity->uuid(), 'Expected entity UUID found.');
  }

}
