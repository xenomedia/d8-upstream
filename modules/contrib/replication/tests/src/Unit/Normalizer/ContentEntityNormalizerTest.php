<?php

namespace Drupal\Tests\replication\Unit\Normalizer;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\entity_test\Entity\EntityTestMulRev;

/**
 * Tests the content serialization format.
 *
 * @group replication
 */
class ContentEntityNormalizerTest extends NormalizerTestBase {

  protected $entityClass = 'Drupal\entity_test\Entity\EntityTest';

  protected function setUp() {
    parent::setUp();

    // @todo: {@link https://www.drupal.org/node/2600468 Attach a file field.}

    // Create a test entity to serialize.
    $this->values = [
      'name' => $this->randomMachineName(),
      'user_id' => 1,
      'field_test_text' => [
        'value' => $this->randomMachineName(),
        'format' => 'full_html',
      ],
    ];

    $this->entity = EntityTestMulRev::create($this->values);
    $this->entity->save();
  }

  public function testNormalizer() {
    // Test normalize.
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
          ['value' => $this->values['name']],
        ],
        'type' => [
          ['value' => 'entity_test_mulrev'],
        ],
        'created' => [
          ['value' => $this->entity->created->value],
        ],
        'default_langcode' => [
          ['value' => TRUE],
        ],
        'user_id' => [
          ['target_id' => $this->values['user_id']],
        ],
        '_rev' => [
          ['value' => $this->entity->_rev->value],
        ],
        'non_rev_field' => [],
        'field_test_text' => [
          [
            'value' => $this->values['field_test_text']['value'],
            'format' => $this->values['field_test_text']['format'],
            'processed' => ''
          ],
        ],
      ],
      '_id' => $this->entity->uuid(),
      '_rev' => $this->entity->_rev->value,
    );

    $normalized = $this->serializer->normalize($this->entity);

    foreach (array_keys($expected) as $key) {
      $this->assertEquals($expected[$key], $normalized[$key], "Field $key is normalized correctly.");
    }
    $this->assertEquals(array_diff_key($normalized, $expected), [], 'No unexpected data is added to the normalized array.');

    // Test normalization when is set the revs query parameter.
    $parts = explode('-', $this->entity->_rev->value);
    $expected['_revisions'] = [
      'ids' => [$parts[1]],
      'start' => (int) $parts[0],
    ];

    $normalized = $this->serializer->normalize($this->entity, NULL, ['query' => ['revs' => TRUE]]);

    foreach (array_keys($expected) as $key) {
      $this->assertEquals($expected[$key], $normalized[$key], "Field $key is normalized correctly.");
    }
    $this->assertTrue($expected['_revisions']['start'] === $normalized['_revisions']['start'], "Correct data type for the start field.");
    $this->assertEquals(array_diff_key($normalized, $expected), [], 'No unexpected data is added to the normalized array.');

    // @todo {@link https://www.drupal.org/node/2600460 Test context switches.}

    // Test serialize.
    $normalized = $this->serializer->normalize($this->entity);
    $expected = json_encode($normalized);
    // Paranoid test because JSON serialization is tested elsewhere.
    $actual = $this->serializer->serialize($this->entity, 'json');
    $this->assertSame($actual, $expected, 'Entity serializes correctly to JSON.');

    // Test denormalize.
    $denormalized = $this->serializer->denormalize($normalized, $this->entityClass, 'json');
    $this->assertTrue($denormalized instanceof $this->entityClass, SafeMarkup::format('Denormalized entity is an instance of @class', ['@class' => $this->entityClass]));
    $this->assertSame($denormalized->getEntityTypeId(), $this->entity->getEntityTypeId(), 'Expected entity type found.');
    $this->assertSame($denormalized->bundle(), $this->entity->bundle(), 'Expected entity bundle found.');
    $this->assertSame($denormalized->uuid(), $this->entity->uuid(), 'Expected entity UUID found.');

    // @todo {@link https://www.drupal.org/node/2600460 Test context switches.}
  }

}
