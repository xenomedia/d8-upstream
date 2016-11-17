<?php

namespace Drupal\replication\Changes;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\multiversion\Entity\Index\SequenceIndexInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\replication\Plugin\ReplicationFilterManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * {@inheritdoc}
 */
class Changes implements ChangesInterface {
  use DependencySerializationTrait;

  /**
   * The sequence index.
   * 
   * @var \Drupal\multiversion\Entity\Index\SequenceIndexInterface
   */
  protected $sequenceIndex;

  /**
   * The workspace to generate the changeset from.
   *
   * @var string
   */
  protected $workspaceId;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * @var \Drupal\replication\Plugin\ReplicationFilterManagerInterface
   */
  protected $filterManager;

  /**
   * @var string
   *   The id of the filter plugin to use during replication.
   */
  protected $filter;

  /**
   * The parameters passed to the filter plugin.
   *
   * @var array
   */
  protected $parameters;

  /**
   * Whether to include entities in the changeset.
   *
   * @var boolean
   */
  protected $includeDocs = FALSE;

  /**
   * The sequence ID to start including changes from. Result includes $lastSeq.
   *
   * @var int
   */
  protected $lastSeq = 0;

  /**
   * @param \Drupal\multiversion\Entity\Index\SequenceIndexInterface $sequence_index
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   * @param \Drupal\replication\Plugin\ReplicationFilterManagerInterface $filter_manager
   */
  public function __construct(SequenceIndexInterface $sequence_index, WorkspaceInterface $workspace, EntityTypeManagerInterface $entity_type_manager, SerializerInterface $serializer, ReplicationFilterManagerInterface $filter_manager) {
    $this->sequenceIndex = $sequence_index;
    $this->workspaceId = $workspace->id();
    $this->entityTypeManager = $entity_type_manager;
    $this->serializer = $serializer;
    $this->filterManager = $filter_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function filter($filter) {
    $this->filter = $filter;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function parameters(array $parameters = NULL) {
    $this->parameters = $parameters;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function includeDocs($include_docs) {
    $this->includeDocs = $include_docs;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function lastSeq($seq) {
    $this->lastSeq = $seq;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNormal() {
    $sequences = $this->sequenceIndex
      ->useWorkspace($this->workspaceId)
      ->getRange($this->lastSeq, NULL);

    // Setup filter plugin.
    $parameters = is_array($this->parameters) ? $this->parameters : [];
    $filter = NULL;
    if (is_string($this->filter) && $this->filter) {
      $filter = $this->filterManager->createInstance($this->filter, $parameters);
    }
    // If UUIDs are sent as a parameter, but no filter is set, automatically
    // select the "uuid" filter.
    elseif (isset($parameters['uuids'])) {
      $filter = $this->filterManager->createInstance('uuid', $parameters);
    }

    // Format the result array.
    $changes = array();
    foreach ($sequences as $sequence) {
      if (!empty($sequence['local']) || !empty($sequence['is_stub'])) {
        continue;
      }

      // Get the document.
      $revision = NULL;
      if ($this->includeDocs == TRUE || $filter !== NULL) {
        /** @var \Drupal\multiversion\Entity\Storage\ContentEntityStorageInterface $storage */
        $storage = $this->entityTypeManager->getStorage($sequence['entity_type_id']);
        $storage->useWorkspace($this->workspaceId);
        $revision = $storage->loadRevision($sequence['revision_id']);
      }

      // Filter the document.
      if ($filter !== NULL && !$filter->filter($revision)) {
        continue;
      }

      $uuid = $sequence['entity_uuid'];
      $changes[$uuid] = array(
        'changes' => array(
          array('rev' => $sequence['rev']),
        ),
        'id' => $uuid,
        'seq' => $sequence['seq'],
      );
      if ($sequence['deleted']) {
        $changes[$uuid]['deleted'] = TRUE;
      }

      // Include the document.
      if ($this->includeDocs == TRUE) {
        $changes[$uuid]['doc'] = $this->serializer->normalize($revision);
      }
    }

    // Now when we have rebuilt the result array we need to ensure that the
    // results array is still sorted on the sequence key, as in the index.
    $return = array_values($changes);
    usort($return, function($a, $b) {
      return $a['seq'] - $b['seq'];
    });

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getLongpoll() {
    $no_change = TRUE;
    do {
      $change = $this->sequenceIndex
        ->useWorkspace($this->workspaceId)
        ->getRange($this->lastSeq, NULL);
      $no_change = empty($change) ? TRUE : FALSE;
    } while ($no_change);
    return $change;
  }

}
