<?php

namespace Drupal\multiversion;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

interface MultiversionManagerInterface {

  /**
   * @return string
   * @deprecated Should no longer be used.
   * @see \Drupal\multiversion\Workspace\WorkspaceManager::getActiveWorkspace()
   */
  public function getActiveWorkspaceId();

  /**
   * @param string $id
   * @deprecated Should no longer be used.
   * @see \Drupal\multiversion\Workspace\WorkspaceManager::setActiveWorkspace()
   */
  public function setActiveWorkspaceId($id);

  /**
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @return boolean
   */
  public function isSupportedEntityType(EntityTypeInterface $entity_type);

  /**
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   */
  public function getSupportedEntityTypes();

  /**
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @return boolean
   */
  public function isEnabledEntityType(EntityTypeInterface $entity_type);

  /**
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   */
  public function getEnabledEntityTypes();

  /**
   * @return \Drupal\multiversion\MultiversionManagerInterface
   */
  public function enableEntityTypes();

  /**
   * @return \Drupal\multiversion\MultiversionManagerInterface
   */
  public function disableEntityTypes();

  /**
   * @return integer
   */
  public function newSequenceId();

  /**
   * @return integer
   */
  public function lastSequenceId();

  /**
   * @return string
   */
  public function newRevisionId(ContentEntityInterface $entity, $index = 0);

}
