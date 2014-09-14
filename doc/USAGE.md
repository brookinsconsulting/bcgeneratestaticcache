BC Generate Static Cache USAGE
==============================

Usage
============

1. Running the script - Inspecting script arguments
   ========================

The first thing you should do is run the script to inspect it's options and required parameters.

php ./extension/bcgeneratestaticcache/bin/php/bcgeneratestaticcache.php --help

2. Running the script - Example Usage Use Cases
   ========================

Subtree
-------

php ./extension/bcgeneratestaticcache/bin/php/bcgeneratestaticcache.php --subtree=/Mirror* --force -s ezwebin_site_user;

HomePage
--------

php ./extension/bcgeneratestaticcache/bin/php/bcgeneratestaticcache.php --subtree=/ --force -s ezwebin_site_user;

Entire Content Tree
-------------------

php ./extension/bcgeneratestaticcache/bin/php/bcgeneratestaticcache.php --subtree=/* --force -s ezwebin_site_user;

