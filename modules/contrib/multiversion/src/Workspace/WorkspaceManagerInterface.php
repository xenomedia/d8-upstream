<?php

namespace Drupal\multiversion\Workspace;

use Drupal\multiversion\Entity\WorkspaceInterface;

interface WorkspaceManagerInterface {

  /**
   * @param \Drupal\multiversion\Workspace\WorkspaceNegotiatorInterface $negotiator
   * @param int $priority
   */
  public function addNegotiator(WorkspaceNegotiatorInterface $negotiator, $priority);

  /**
   * @param string $workspace_id
   */
  public function load($workspace_id);

  /**
   * @param array|null $workspace_ids
   */
  public function loadMultiple(array $workspace_ids = NULL);

  /**
   * @param string $machine_name
   */
  public function loadByMachineName($machine_name);

  /**
   * @return \Drupal\multiversion\Entity\WorkspaceInterface
   */
  public function getActiveWorkspace();

  /**
   * Sets the active workspace for the site/session.
   *
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   *   The workspace to set as active.
   *
   * @return \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   *
   * @throws WorkspaceAccessException
   */
  public function setActiveWorkspace(WorkspaceInterface $workspace);

}
