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

bcgeneratestaticcache - Subtree
-------

php ./extension/bcgeneratestaticcache/bin/php/bcgeneratestaticcache.php --subtree=/Mirror* --force -s ezwebin_site_user;

bcgeneratestaticcache - HomePage
--------

php ./extension/bcgeneratestaticcache/bin/php/bcgeneratestaticcache.php --subtree=/ --force -s ezwebin_site_user;

bcgeneratestaticcache - Entire Content Tree
-------------------

php ./extension/bcgeneratestaticcache/bin/php/bcgeneratestaticcache.php --subtree=/* --force -s ezwebin_site_user;


example-generatestaticcacheindexes - Root Node and Child Node Indexes Only
-------------------

./extension/bcgeneratestaticcache/bin/shell/example-generatestaticcacheindexes.sh ezwebin_site_user /;

example-generatestaticcacheindexes - Node and Child Node Indexes Only
-------------------

./extension/bcgeneratestaticcache/bin/shell/example-generatestaticcacheindexes.sh ezwebin_site_user /Mirror;

example-generatestaticcacheindexes - Node and All Child Node Indexes - Every Content Object Bellow Uri
-------------------

./extension/bcgeneratestaticcache/bin/shell/example-generatestaticcacheindexes.sh ezwebin_site_user /Mirror '*';

