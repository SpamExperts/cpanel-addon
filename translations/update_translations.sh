#!/bin/bash

# Update the SVN translation templates
svn up ~/svn/translations/*/*.pot

# Force commit the Weblate changes to GIT to prevent merge conflicts
curl -s https://hosted.weblate.org/hooks/commit/spamexperts/ > /dev/null

# Retrieve the latest .po translation files from GIT
cd ~/git/translations; git pull

# Copy the latest translations from GIT to SVN
cp ~/git/translations/addons/*.po ~/svn/translations/addons/
cp ~/git/translations/software/*.po ~/svn/translations/software/
cp ~/git/translations/spampanel/*.po ~/svn/translations/spampanel/
cp ~/git/translations/whmcs/*.po ~/svn/translations/whmcs/

# Commit the latest translations to SVN
svn commit -m "Update translation files" ~/svn/translations/*/*.po

# Copy the templates from SVN to GIT
cp ~/svn/translations/addons/addons.pot ~/git/translations/addons/
cp ~/svn/translations/software/spamexperts.pot ~/git/translations/software/
cp ~/svn/translations/spampanel/spamexperts.pot ~/git/translations/spampanel/
cp ~/svn/translations/whmcs/whmcs.pot ~/git/translations/whmcs/

# Apply the new latest .pot templates from SVN to the GitHub .po files
find ~/git/translations/addons/ -type f -iname '*.po' | xargs -I{} msgmerge --suffix=none -vU {} ~/git/translations/addons/addons.pot
find ~/git/translations/software/ -type f -iname '*.po' | xargs -I{} msgmerge --suffix=none -vU {} ~/git/translations/software/spamexperts.pot
find ~/git/translations/spampanel/ -type f -iname '*.po' | xargs -I{} msgmerge --suffix=none -vU {} ~/git/translations/spampanel/spamexperts.pot
find ~/git/translations/whmcs/ -type f -iname '*.po' | xargs -I{} msgmerge --suffix=none -vU {} ~/git/translations/whmcs/whmcs.pot

# Commit the new translation files to GitHub
cd ~/git/translations; find ./ -type f -name \*.ponone -exec rm {} \;
cd ~/git/translations/; git commit -a -m "Update translation files"
cd ~/git/translations/; git push
