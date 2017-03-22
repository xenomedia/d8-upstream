# D8 Upstream

This repository can be used to set up a Composer-Managed Drupal 8 site on [Pantheon](https://pantheon.io).

## Overview

This project contains only the canonical resources used to build a Drupal site for use on Pantheon. There are two different ways that it can be used:

- Create a separate canonical repository on GitHub; maintain using a pull request workflow.
- Build the full Drupal site and then install it on Pantheon; maintain using `terminus composer` and on-server development.

The setup instructions vary based on which of these options you select.

## Pull Request Workflow

When using a pull request workflow, only the canonical resources (code, configuration, etc.) exists in the master repository, stored on GitHub. A build step is used to create the full Drupal site and automatically deploy it to Pantheon. Pull requests are the primary means of doing site development; however, new pull requests can be created directly from your Pantheon dashboard in SFTP mode using on-server development. However, the Pantheon repository should be considered as "scratch space" only. The persistent project resources are maintained in the canonical repository; the Pantheon repository is only used to hold the build results to be served.

### Terminus Build Tools Plugin

To get started, first install [Terminus](https://pantheon.io/docs/terminus) and the [Terminus Build Tools Plugin](https://github.com/pantheon-systems/terminus-build-tools-plugin).

## Pantheon "Standalone" Development

This project can also be used to do traditional "standalone" development on Pantheon using on-server development. In this mode, the canonical repository is immediately built out into a full Drupal site, and the results are committed to the Pantheon repository. Thereafter, no canoncial repository is used; all development will be done exclusively using the Pantheon database.

When doing "standalone" development, this project can either be used as an upstream repository, or it can be set up manually. The instructions for doing either follows in the section below.

### As an Upstream

Create a custom upstream for this project following the instructions in the [Pantheon Custom Upstream documentation](https://pantheon.io/docs/custom-upstream/). When you do this, Pantheon will automatically run composer install to populate the web and vendor directories each time you create a site.

### Manual Setup

Enter the commands below to create a a new site on Pantheon and push a copy of this project up to it.
```
$ SITE="my-site"
$ terminus site:create $SITE "My Site" "Drupal 8" --org="My Team"
$ composer create-project pantheon-systems/example-drops-8-composer $SITE
$ cd $SITE
$ composer prepare-for-pantheon
$ git init
$ git add -A .
$ git commit -m "Initial commit"
$ terminus  connection:set $SITE.dev git
$ PANTHEON_REPO=$(terminus connection:info $SITE.dev --field=git_url)
$ git remote add origin $PANTHEON_REPO
$ git push --force origin master
$ terminus drush $SITE.dev -- site-install --site-name="My Drupal Site"
$ terminus dashboard:view $SITE
```
Replace my-site with the name that you gave your Pantheon site. Customize the parameters of the `site:create` and `site-install` lines to suit.

### Installing Drupal

Note that this example repository sets the installation profile to 'standard' in settings.php, so that the installer will not need to modify the settings file. If you would like to install a different profile, modify settings.php appropriately before installing your site.

### Updating Your Site

When using this repository to manage your Drupal site, you will no longer use the Pantheon dashboard to update your Drupal version. Instead, you will manage your updates using Composer. Updates can be applied either directly on Pantheon, by using Terminus, or on your local machine.

#### Update with Terminus

Install [Terminus 1](https://pantheon.io/docs/terminus/) and the [Terminus Composer plugin](https://github.com/pantheon-systems/terminus-composer-plugin).  Then, to update your site, ensure it is in SFTP mode, and then run:
```
terminus composer <sitename>.<dev> update
```
Other commands will work as well; for example, you may install new modules using `terminus composer <sitename>.<dev> require drupal/pathauto`.

#### Update on your local machine

You may also place your site in Git mode, clone it locally, and then run composer commands from there.  Commit and push your files back up to Pantheon as usual.
