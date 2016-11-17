<?php

namespace Drupal\multiversion\Entity\Storage\Sql;

use Drupal\multiversion\Entity\Storage\ContentEntityStorageInterface;
use Drupal\multiversion\Entity\Storage\ContentEntityStorageTrait;
use Drupal\taxonomy\TermStorage as CoreTermStorage;

/**
 * Storage handler for taxonomy terms.
 */
class TermStorage extends CoreTermStorage implements ContentEntityStorageInterface {

  use ContentEntityStorageTrait {
    delete as deleteEntities;
  }

  /**
   * {@inheritdoc}
   *
   * @todo: {@link https://www.drupal.org/node/2597530 Can we do a query alter
   * instead of overriding this method?}
   */
  public function loadTree($vid, $parent = 0, $max_depth = NULL, $load_entities = FALSE) {
    $cache_key = implode(':', func_get_args());
    if (!isset($this->trees[$cache_key])) {
      // We cache trees, so it's not CPU-intensive to call on a term and its
      // children, too.
      if (!isset($this->treeChildren[$vid])) {
        $this->treeChildren[$vid] = array();
        $this->treeParents[$vid] = array();
        $this->treeTerms[$vid] = array();
        $active_workspace = \Drupal::service('workspace.manager')->getActiveWorkspace();
        $query = $this->database->select('taxonomy_term_field_data', 't');
        $query->join('taxonomy_term_hierarchy', 'h', 'h.tid = t.tid');
        $result = $query
          ->addTag('term_access')
          ->fields('t')
          ->fields('h', array('parent'))
          ->condition('t.vid', $vid)
          ->condition('t.default_langcode', 1)
          ->condition('t._deleted', 0)
          ->condition('t.workspace', $active_workspace->id())
          ->orderBy('t.weight')
          ->orderBy('t.name')
          ->execute();
        foreach ($result as $key => $term) {
          $this->treeChildren[$vid][$term->parent][] = $term->tid;
          $this->treeParents[$vid][$term->tid][] = $term->parent;
          $this->treeTerms[$vid][$term->tid] = $term;
        }
      }

      // Load full entities, if necessary. The entity controller statically
      // caches the results.
      $term_entities = array();
      if ($load_entities) {
        $term_entities = $this->loadMultiple(array_keys($this->treeTerms[$vid]));
      }

      $max_depth = (!isset($max_depth)) ? count($this->treeChildren[$vid]) : $max_depth;
      $tree = array();

      // Keeps track of the parents we have to process, the last entry is used
      // for the next processing step.
      $process_parents = array();
      $process_parents[] = $parent;

      // Loops over the parent terms and adds its children to the tree array.
      // Uses a loop instead of a recursion, because it's more efficient.
      while (count($process_parents)) {
        $parent = array_pop($process_parents);
        // The number of parents determines the current depth.
        $depth = count($process_parents);
        if ($max_depth > $depth && !empty($this->treeChildren[$vid][$parent])) {
          $has_children = FALSE;
          $child = current($this->treeChildren[$vid][$parent]);
          do {
            if (empty($child)) {
              break;
            }
            $term = $load_entities ? $term_entities[$child] : $this->treeTerms[$vid][$child];
            if (isset($this->treeParents[$vid][$load_entities ? $term->id() : $term->tid])) {
              // Clone the term so that the depth attribute remains correct
              // in the event of multiple parents.
              $term = clone $term;
            }
            $term->depth = $depth;
            unset($term->parent);
            $tid = $load_entities ? $term->id() : $term->tid;
            $term->parents = $this->treeParents[$vid][$tid];
            $tree[] = $term;
            if (!empty($this->treeChildren[$vid][$tid])) {
              $has_children = TRUE;

              // We have to continue with this parent later.
              $process_parents[] = $parent;
              // Use the current term as parent for the next iteration.
              $process_parents[] = $tid;

              // Reset pointers for child lists because we step in there more
              // often with multi parents.
              reset($this->treeChildren[$vid][$tid]);
              // Move pointer so that we get the correct term the next time.
              next($this->treeChildren[$vid][$parent]);
              break;
            }
          } while ($child = next($this->treeChildren[$vid][$parent]));

          if (!$has_children) {
            // We processed all terms in this hierarchy-level, reset pointer
            // so that this function works the next time it gets called.
            reset($this->treeChildren[$vid][$parent]);
          }
        }
      }
      $this->trees[$cache_key] = $tree;
    }
    return $this->trees[$cache_key];
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
    $this->deleteEntities($entities);
    foreach ($entities as $entity) {
      $this->updateParentHierarchy(array($entity->id()));
    }
  }

  /**
   * Updates terms hierarchy information for the children when terms are deleted.
   *
   * @param array $tids
   *   Array of terms that need to be removed from hierarchy.
   */
  public function updateParentHierarchy($tids) {
    $this->database->update('taxonomy_term_hierarchy')
      ->condition('parent', $tids)
      ->fields(array('parent' => 0))
      ->execute();
  }

}
