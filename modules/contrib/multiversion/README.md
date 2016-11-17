[![Build Status](https://travis-ci.org/dickolsson/drupal-multiversion.svg?branch=8.x-1.x)](https://travis-ci.org/dickolsson/drupal-multiversion)

Multiversion
============

Extends the revision model for content entities.

## Content staging

This module is part of [the content staging suite for D8](https://www.drupal.org/project/deploy#d8).

### Dependencies

Multiversion depends on
  * Drupal core's serialization module
  * [Key-value Extensions](https://www.drupal.org/project/key_value)
  
## Uninstall

Multiversion can't be uninstalled as other modules because it modifies the entity storage.

To unistall Multiversion use the `multiversion-uninstall` command or the `mun` alias for Drush.

## Presentations

- https://austin2014.drupal.org/session/content-staging-drupal-8
- https://amsterdam2014.drupal.org/session/content-staging-drupal-8-continued
