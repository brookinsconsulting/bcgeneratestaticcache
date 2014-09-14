BC Generate Static Cache
========================

bcgeneratestaticcache is a command line script that provides a command line script to use to generate static cache using command line options

- Project: http://projects.ez.no/bcgeneratestaticcache

- Source code: http://github.com/bcgeneratestaticcache


REQUIREMENTS
============

This extension is now compatible and tested with eZ Publish 5.x Legacy.

Tested with eZ Publish Community Project 2014.07

Autoloads
---------

This script depends on autoloads to function

To use this script you must first regenerate autoloads for your installation

php ./bin/php/ezpgenerateautoloads.php;


USAGE
=====

Subtree
-------

php ./extension/bcgeneratestaticcache/bin/php/bcgeneratestaticcache.php --subtree=/Mirror --max-level=1 --force -s ezwebin_site_user;

HomePage
--------

php ./extension/bcgeneratestaticcache/bin/php/bcgeneratestaticcache.php --subtree=/ --max-level=0 --force -s ezwebin_site_user;


CREDITS
=======

This solution was based on the makestaticcache_cli extension by Damien Pobel

This solution was compatible with eZ Publish 4.x (See GitHub repository's 4.x branch for older version)
