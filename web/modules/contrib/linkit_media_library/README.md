# Linkit Media Library

This module provides a button from the Link dialog within the WYSIWYG to insert
links to media items.

Upon link insertion, the 'target' attribute is automatically set to '_blank' to
open the linked document in a new window/tab.

## REQUIREMENTS

* Drupal 8 or 9
* [Linkit](https://www.drupal.org/project/linkit) module
  * Supported versions: 8.x-5.x and 6.x.x

## INSTALLATION

The module can be installed via the
[standard Drupal installation process](http://drupal.org/node/895232).

## CONFIGURATION

In order for the 'Media Library' button to be added to the Linkit modal, both
the Linkit CKEditor plugin and the 'Linkit URL converter' filter must be
enabled for the filter format.

If the 'Limit allowed HTML tags and correct faulty HTML' filter is enabled for
a filter format, then the filter format must be configured to allow the
'target' attribute on 'a' elements in order for linked documents to open in a
new window/tab.
