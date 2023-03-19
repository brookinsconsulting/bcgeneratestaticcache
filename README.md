BC Generate Static Cache
========================

bcgeneratestaticcache is a command line script that provides a command line script to use to generate static cache using command line options

- Git Repository and Source code: http://github.com/brookinsconsulting/bcgeneratestaticcache


Requirements
============

This extension is now compatible and tested with eZ Publish 5.x+ (Legacy) and PHP 8.2.3

Tested with eZ Publish Community Project 2014.07 and 2013.05 and 2019.11

Autoloads
---------

This script depends on autoloads to function

To use this script you must first regenerate autoloads for your installation

php ./bin/php/ezpgenerateautoloads.php;


Usage
===========================

The complete extension usage documentation is included in the file doc/USAGE.


CREDITS
=======

This solution was originally based on the makestaticcache_cli extension by Damien Pobel

This solution was compatible with eZ Publish 4.x (See GitHub repository's 4.x branch for older version)

This solution was largely gutted by Brookins Consulting to replace old code with proven code to generate static cache node content to disk from bcimagealias which had largly proven a greater success at the time of writting. 


Troubleshooting
===============

1. Read the FAQ
   ------------

   Some problems are more common than others. The most common ones
   are listed in the the doc/FAQ.

2. Support
   -------

   If you have find any problems not handled by this document or the FAQ you
   can contact Brookins Consulting through the support system:
   http://brookinsconsulting.com/contact