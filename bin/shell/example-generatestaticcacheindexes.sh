#!/bin/bash

# Test for optional siteacess shell argument #1
if [[ -z "$1" ]]; then
# Refresh using the default ezpublish siteacces name
siteaccess="ezwebin_site_user";
else
# Refresh using the optional siteaccess shell argument
siteaccess="$1";
fi

# Alert the user of the siteaccess being used
echo "Using siteaccess $siteaccess";
echo "";

# Test for optional uri shell argument #2
if [[ -z "$2" ]]; then
# Refresh Home Page Indexes
uri="/";
else
uri="$2";
fi

# Test for optional subtree shell argument #3
if [[ -z "$3" ]]; then
# Refresh indexes only
subtree="";
children=" --children ";
echo "Caching indexes only";
echo "";
else
# Refresh subtree indexes only
echo "Caching subtree with *";
echo "";
subtree="$3";
children=" ";
fi

# Alert the user of the static cache generation starting
echo "Generating $uri indexes ...";
echo "";

# Refresh Static Cache
php ./extension/bcgeneratestaticcache/bin/php/bcgeneratestaticcache.php --subtree=$uri$subtree$children--force -s $siteaccess;

exit;

# END