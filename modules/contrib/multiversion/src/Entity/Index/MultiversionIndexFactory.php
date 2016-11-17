<?php

namespace Drupal\multiversion\Entity\Index;

use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MultiversionIndexFactory {

  /** @var  ContainerInterface */
  protected $container;

  /** @var  WorkspaceManagerInterface */
  protected $workspaceManager;

  /** @var EntityIndexInterface[]  */
  protected $indexes = [];

  public function __construct(ContainerInterface $container, WorkspaceManagerInterface $workspace_manager) {
    $this->container = $container;
    $this->workspaceManager = $workspace_manager;
  }

  public function get($service, WorkspaceInterface $workspace = null) {
    $index = $this->container->get($service . '.scope');
    if ($index instanceof IndexInterface) {
      $workspace_id = $workspace ? $workspace->id() : $this->workspaceManager->getActiveWorkspace()->id();
      return $indexes[$workspace_id][$service] = $index->useWorkspace($workspace_id);
    }
    else {
      throw new \InvalidArgumentException("Service $service is not an instance of IndexInterface.");
    }
  }
}