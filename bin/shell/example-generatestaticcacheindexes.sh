#!/bin/bash

if [[ -z "$1" ]]; then
siteaccess="ezwebin_site_user";
else
siteaccess="$1";
fi

echo "Using siteaccess $siteaccess";
echo "";

if [[ -z "$2" ]]; then
type="index";
else
type="$2";
fi

if [[ -z "$3" ]]; then
subtree="";
echo "Caching indexes only";
echo "";
else
echo "Caching subtree with *";
echo "";
subtree="$3";
fi

if [[ "$type" = "index" ]]; then
echo "Generating indexes ...";
echo "";

# Refresh Home Page Indexes

php ./extension/bcgeneratestaticcache/bin/php/bcgeneratestaticcache.php --subtree=/$subtree --children --force -s $siteaccess;

elif [[ "$type" = "blogindex" ]]; then
echo "Generating blog indexes ...";
echo "";

# Refresh Mirror Blog Indexes

php ./extension/bcgeneratestaticcache/bin/php/bcgeneratestaticcache.php --subtree=/Mirror$subtree --children --force -s $siteaccess;

fi

exit;

# END