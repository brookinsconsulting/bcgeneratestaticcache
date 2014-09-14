BC Google Sitemaps extension INSTALL

Introduction
============

1. What is the BC Google Sitemaps extension?
   ------------------------------------------------

   BC Google Sitemaps is a true eZ Publish extension
   that provides commandline script, workflow event,
   cronjob parts, class methods, module views and
   admin siteaccess content structure menu items to
   create, remove and regenerate content object
   image alias variations.

   For more information about this extension please read the README file.

1. Copyright
   ---------

   BC Google Sitemaps is copyright 1999 - 2014 Brookins Consulting, 2008 all2e GmbH and 2008 MEDIATA Communications GmbH

   See: doc/COPYRIGHT for more information on the terms of the copyright and license

1.1. License
     -------

     BC Google Sitemaps is licensed under the GNU General Public License.

     The complete license agreement is included in the doc/LICENSE file.

     BC Google Sitemaps is free software: you can redistribute it and/or modify
     it under the terms of the GNU General Public License as published by
     the Free Software Foundation, version 2 of the License.

     BC Google Sitemaps is distributed in the hope that it will be useful,
     but WITHOUT ANY WARRANTY; without even the implied warranty of
     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     GNU General Public License for more details.

     The GNU GPL gives you the right to use, modify and redistribute
     BC Google Sitemaps under certain conditions. The GNU GPL license
     is distributed with the software, see the file doc/LICENSE.

     It is also available at http://www.gnu.org/licenses/gpl.txt

     You should have received a copy of the GNU General Public License
     along with BC Google Sitemaps in doc/LICENSE.  If not, see http://www.gnu.org/licenses/.

     Using BC Google Sitemaps under the terms of the GNU GPL is free (as in freedom).


2. Requirements
   ------------

   The following requirements exists for using BC Google Sitemaps extension:

   o  eZ Publish version:

      Make sure you use eZ Publish version 4.x (required) or higher.

      eZ Publish 4.6.x+ (Community Build, 2012.03+) is recommended.

   o  PHP version:

      Make sure you have PHP 5.x or higher.


Getting eZ Publish
==================

You can download a version of eZ Publish from share.ez.no,
you will find the various versions at:

http://share.ez.no/download

NOTE: You will only require eZ Publish 4.x or higher (if you have a more recent version)

Information on the installation of eZ Publish can be found at:
http://doc.ez.no/eZ-Publish/Technical-manual/4.6/Installation
and
http://doc.ez.no
http://share.ez.no


Installing BC Google Sitemaps extension
==========================================

1. Copy the extension files into the extension directory
   =====================================================

   Copy the package into the `extension' directory
   in the root of your eZ Publish installation.


2. Unpack the extension package files into the extension directory
   ===============================================================

   Unpack the files in the distribution. The command
   necessary is depends on the file you downloaded.

   [tar.gz]
   tar -zxvf bcgooglesitemaps-1_1_0.tar.gz

   [zip]
   unzip bcgooglesitemaps-1_1_0.tar.zip


3. (Optional) Git clone the latest GitHub brookinsconsulting bcgooglesitemaps extension sources into the extension directory
   ===============================================================

   You can optionaly fetch the latest extension source code from GitHub brookinsconsulting bcgooglesitemaps repository into the extension directory

cd /path/to/ezpublish;
cd extension;

mkdir bcgooglesitemaps;
cd bcgooglesitemaps;

git clone git@github.com:brookinsconsulting/bcgooglesitemaps.git . ;

sudo chmod -R 777 ../bcgooglesitemaps;


3.1. (Optional) Git download the latest GitHub brookinsconsulting bcgooglesitemaps extension sources into the extension directory
     ===============================================================

     You can optionaly fetch the latest extension source code from GitHub brookinsconsulting bcgooglesitemaps repository into the extension directory

cd /path/to/ezpublish;
cd extension;

mkdir bcgooglesitemaps;
cd bcgooglesitemaps;

wget https://github.com/brookinsconsulting/bcgooglesitemaps/tarball/master;

or

wget https://github.com/brookinsconsulting/bcgooglesitemaps/zipball/master

tar -zxf brookinsconsulting-bcgooglesitemaps-d1d1411.tar.gz;

or

unzip brookinsconsulting-bcgooglesitemaps-d1d1411.tar.gz;

sudo chmod -R 777 ../bcgooglesitemaps;


4. We must now enable the extension in eZ Publish.
   ===========================

   To do this edit site.ini.append(.php) in the folder
   /path/to/ezpublish/settings/override/. If this file does not exist;
   create it. Locate (or add) the block

   [ExtensionSettings] and add the line:
   ActiveExtensions[]=bcgooglesitemaps

   If you run several sites using only one distribution
   and only some of the sites should use the extension,
   make the changes in the override file of that siteaccess.

   E.g /path/to/ezpublish/settings/siteaccess/ezwebin_site_user/site.ini.append(.php)
   But instead of using ActiveExtensions you must add these lines instead:

   [ExtensionSettings]
   ActiveAccessExtensions[]=bcgooglesitemaps


5. Regenerate eZ Publish class autoloads
   ===========================

   You must regenerate autoloads for extension classes to be available via eZ Publish autoloads. This may mean running the following different commands.

cd /path/to/ezpublish;

php bin/php/ezpgenerateautoloads.php -v -e;


6. There is no need to configure BC Google Sitemaps extension after activation
   ===========================

   There are optional settings in settings/bcgooglesitemaps.ini mostly cronjob part and xml output specific.

   Create a settings override to customize these values. Review to understand extension settings options.


7. Settings configuration
   ===========================

   Make an override for the file googlesitemaps.ini.append.php and edit the settings for the siteaccesses. It might look like this:

    [SiteAccessSettings]
    SiteAccessArray[]
    SiteAccessArray[]=de
    SiteAccessArray[]=en


8. Apache rewrite rules
   ====================

   Please ensure that your Apache rewrite rules permit access to the XML file when accessing like http://www.example.com/sitemap.xml

   This can be done by adding the following line to your .htaccess or Apache configuration file:

    RewriteRule ^sitemap[^/]+.xml - [L]

    or

    RewriteRule ^sitemap[^/]+.xml /var/sitemap[^/]+.xml [L]

    Please note that this line has changed in this release. If you are using a previuous release, than you might need to change it.

    After these steps are finished you might add the script to your crontab so it will be generated frequently.

    You should also take a look at the googlesitemaps.ini.append.php configuration file for other options in settings.


9. Set up the crontab
   ==================

   You can read the following eZ Publish Cronjob documentation on how to run cronjob part scripts and how to configure crontab to run cronjob part scripts.

   Running eZ Publish Cronjob part scripts, http://doc.ez.no/eZ-Publish/Technical-manual/4.x/Features/Cronjobs/Running-cronjobs
   http://doc.ez.no/eZ-Publish/Technical-manual/4.x/Features/Cronjobs/Running-cronjobs#eztoc86298_1

   Crontab configuration guide, http://doc.ez.no/eZ-Publish/Technical-manual/4.x/Features/Cronjobs/Running-cronjobs#eztoc86298_2


10. Set up the sitemap
   ==================

   You can now add the sitemap to your Google account using the Google Webmaster tools


11. Dynamic module view sitemap
    ===========================

You can optionally skip the cronjob based sitemap generator and use the module view instead which is dynamic and generated in real time. http://example.com/googlesitemapdynamic/sitemap/2


12. There is no need to configure BC Google Sitemaps extension after activation
   ===========================

   There are optional settings in settings/bcgooglesitemapdynamic.ini mostly sitemap xml output specific.

   Create a settings override to customize these values. Review to understand extension settings options.

   If you want to have global settings for all your siteaccesses you can use bcgooglesitemapdynamic.ini.append.php
   in <eZ Publish root>/extension/bcgooglesitemaps/settings.

   If you want to have different settings for your siteaccesses, move bcgooglesitemapdynamic.ini.append.php to the
   appropriate directory: <eZ Publish root>/settings/siteaccess/<siteaccess_name>/bcgooglesitemapdynamic.ini.append.php.

   There are many settings to control the priority, change-frequence and visibility of a node:

   To understand the different configuration-settings, please read comments in bcgooglesitemaps.ini.append.php.

   12.1.1 Order the changefreq is determined for a node:

    1. <changefreq> for individual <NodeID>
      [NodeChangeFreqSettings]
      NodeIndividualChangefreq[<NodeID>]=<value>

    2. all children of <NodeID> get the value for <changefreq> as explained in <matrix>
      [FolderChangeFreqSettings]
      FolderChangefreqModified[<NodeID>]=<matrix>

      <matrix> which explains the relation between a <time-interval> (in seconds) and its <changefreq-value>
      <matrix>[]=<time-interval>;<changefreq-value>

    3. <changefreq> for certain subtree
      <NodeID> and all its children get <value> for <changefreq>
      [SubtreeChangeFreqSettings]
      SubtreeChangefreq[<NodeID>]=<value>

    4. <NodeID> and all its children with depth <= <depth> get for <changefreq> the value listed in <matrix>
      depending on the last modified child
      <matrix> must be an array of settings explained beneath
      set <depth>=0 for unlimited depth
      [SubtreeChangeFreqSettings]
      SubtreeChangefreqModified[<NodeID>]=<matrix>;<depth>

      this matrix shows the relation between the time-interval of last modification and its changefreq-value
      <matrix>[]=<time-interval in seconds>;<changefreq-value>

      SubtreeChangefreqModified[69]=NewsSetTree;0 in connection with NewsSetTree[0]=3600;always means:
      Node 69 and all its children (independet of depth as <depth>=0) get "always" for <changefreq>,
      if minimum one node of the subtree 69 was modified in one hour (3600 seconds) or less

    5. <changefreq> based on absolute depth of node;
      [NodeChangeFreqSettings]
      NodeDepthChangefreq[<depth>]=<value>

    6. set standard <value> for last fallback
      [StandardSettings]
      StandardChangefreq=<value>


   12.1.2 Order the priority is determined for a node:

    1. <priority> for individual <NodeID>
      [NodePrioritySettings]
      NodeIndividualPriority[<NodeID>]=<value>

    2. all children of <NodeID> get the value for <priority> as explained in <matrix>
      [FolderPrioritySettings]
      FolderPriorityModified[<NodeID>]=<matrix>

      <matrix> which explains the relation between a <time-interval> (in seconds) and its <priority-value>
      <matrix>[]=<time-interval>;<priority-value>

    3. <priority> for certain subtree
      <NodeID> and all its children get <value> for <priority>
      [SubtreePrioritySettings]
      SubtreePriority[<NodeID>]=<value>

    4. <NodeID> and all its children with depth <= <depth> get for <priority> the value listed in <matrix>
      depending on the last modified child
      <matrix> must be an array of settings explained beneath
      set <depth>=0 for unlimited depth
      [SubtreePrioritySettings]
      SubtreePriorityModified[<NodeID>]=<matrix>;<depth>

      this matrix shows the relation between the time-interval of last modification and its <priority-value>
      <matrix>[]=<time-interval in seconds>;<priority-value>

      SubtreePriorityModified[69]=NewsSetTree;0 in connection with NewsSetTree[0]=3600;1 means:
      Node 69 and all its children (independet of depth as <depth>=0) get "1" for <priority>,
      if minimum one node of the subtree 69 was modified in one hour (3600 seconds) or less

    5. <priority> based on absolute depth of node;
      [NodePrioritySettings]
      NodeDepthPriority[<depth>]=<value>

    6. set standard <value> for last fallback
      [StandardSettings]
      StandardPriority=<value>


   12.1.3 Order the visibility is determined for a node:

    1. visibility for individual NodeID
      it is not neccessary to set 'show' as it is the default value
      [NodeVisibilitySettings]
      NodeIndividualVisibility[<NodeID>]=hide|show

    2. visibility for a subtree
      values are "hide", "show" with "show" as default
      [SubtreeVisibilitySettings]
      SubtreeVisibility[<NodeID>]=hide|show

    3. standard visibility is "show" on default


   12.1.4 Other settings

    set include to include objects of classes listed in Class_Filter_Array
    set exclude to exclude objects of classes listed in Class_Filter_Array
    [Classes]
    ClassFilterType=include

    setting array to include/exclude classes in sitemap
    ClassFilterArray[]
    ClassFilterArray[]=folder
    ClassFilterArray[]=article
    ...

    set true to include only main node of content object in sitemap
    set false to include all nodes of content object in sitemap
    [NodeSettings]
    MainNodeOnly=true|false


Usage
===========================

The complete extension usage documentation is included in the file doc/USAGE.


Use Cases
===========================

The complete extension usage documentation is included in the file doc/USECASES.


Troubleshooting
===============

1. Read the FAQ
   ------------

   Some problems are more common than others. The most common ones are listed
   in the the doc/FAQ.

2. Support
   -------

   If you have find any problems not handled by this document or the FAQ you
   can contact Brookins Consulting trough the support system:
   http://brookinsconsulting.com/contact
