#!/bin/bash

## Check if being ran by root
username=`whoami`
if [ "$username" != "root" ]; then
    echo "Please run this script as root";
    exit 1
fi

## Check if the WGET path is set
path_wget=`which wget`
if [ -z "$path_wget" ]; then
    echo "Missing WGET. Aborting execution"
    exit 1
fi

REQUIRED_VERSION=5.2.1
RECOMMENDED_VERSION=5.4

function version {
    echo "$@" | awk -F. '{ printf("%d%03d%03d%03d\n", $1,$2,$3,$4); }';
}

function check_php_version {
    ## double check that the provided binary is actually a PHP one
    ## $1 is the path to the PHP binary to check.
    version_info="$("$1" -v 2> /dev/null)"
    if [[ ! $version_info =~ ^PHP ]]; then
        echo "$1: invalid PHP binary"
        exit 1
    fi
    PHPVERSION=`echo "$version_info" | head -n1 | awk {'print $2'} | sed -e 's/-/\n/g' | head -n1`
    if [ -z "$PHPVERSION" ]; then
        echo "$1: PHP version empty"
        exit 1
    fi

    # First check the PHP version against the recommended one
    if [ $(version $PHPVERSION) -ge $(version $RECOMMENDED_VERSION) ]; then
        OPENSSL_SUPPORT=`echo "<?php echo (in_array('https', stream_get_wrappers()) ? 'Y' : 'N');" | $1 -q`
        if [ 'Y' == "$OPENSSL_SUPPORT" ]; then
            echo "$1"
            exit 0
        fi
    # Then check against the minimum required version
    elif [ $(version $PHPVERSION) -ge $(version $REQUIRED_VERSION) ]; then
        OPENSSL_SUPPORT=`echo "<?php echo (in_array('https', stream_get_wrappers()) ? 'Y' : 'N');" | $1 -q`
        if [ 'Y' == "$OPENSSL_SUPPORT" ]; then
            echo "$1"
            exit 255
        fi
    fi

    # If we reach this, I guess we havent found what we need
    exit 1
}

function find_php {
    # We need to find and check cPanel's PHP binary, if it exists
    phpLocations=( "/usr/local/cpanel/3rdparty/bin/php-cgi" "/var/cpanel/3rdparty/bin/php-cgi" )
    for loc in "${phpLocations[@]}"
    do :
        if [ -e "$loc" ]; then
            PHP_BINARY="$loc"
            $(check_php_version "$PHP_BINARY" > /dev/null 2>&1)
            status=$?
            if [[ $status -eq 0 ]] || [[ $status -eq 255 ]]; then
                # Found either the recommended version or the minimum required one, so forward the info
                echo "$PHP_BINARY"
                exit $status
            fi
        fi
    done

    # Cannot find binary, not good.
    exit 1
}

BYPASS=false
while getopts :f FLAG; do
    case $FLAG in
        f)  # set "BYPASS" to true
            BYPASS=true
            ;;
    esac
done

shift $((OPTIND-1))

if [ "$BYPASS" == false ]; then
    php_binary=`find_php`
    status=$?
    if [[ $status -eq 0 ]]; then
        # Found the recommended version, can use current versions
        frozen=false
    elif [[ $status -eq 255 ]]; then
        # Found the minimum required version, stuck on the 'frozen' tier
        frozen=true
    fi

    # Request the path to a VALID PHP binary if auto-detect failed
    if [[ -z "$php_binary" || ! -e "$php_binary" || -d "$php_binary" ]]; then
        echo ""
        echo -e "\033[31mUnable to detect your cPanel's PHP binary. Please specify that path manually (must be at least PHP5 $REQUIRED_VERSION), followed by [ENTER]:\033[0m"
        echo ""
        read PHP_CUSTOM

        while [[ true ]]; do
            if [[ -z "$PHP_CUSTOM" || ! -e "$PHP_CUSTOM" || -d "$PHP_CUSTOM" ]]; then
                echo -e "\033[31mPlease enter a valid path to the PHP5 binary:\033[0m"
                read PHP_CUSTOM
            else
                $(check_php_version "$PHP_CUSTOM" > /dev/null 2>&1)
                status=$?
                if [[ $status -eq 0 ]]; then
                    frozen=false
                    break
                elif [[ $status -eq 255 ]]; then
                    frozen=true
                    break
                else
                    echo -e "\033[31mThe provided PHP binary ($PHP_CUSTOM) does not meet the minimum requirements. Please enter the path to a valid one:\033[0m"
                    read PHP_CUSTOM
                fi

            fi
        done

        php_binary=$PHP_CUSTOM
    fi

    path_php="$php_binary"
fi

## Lets check paneltype
if [ -d "/usr/local/cpanel/" ]; then
    echo "Installing ProSpamFilter for cPanel.."
    paneltype="cpanel"
else
    echo "Unable to detect cPanel installation"
    exit 1
fi

random=`cat /dev/urandom | tr -dc A-Za-z0-9 | head -c5 | md5sum | awk {'print $1'}`

# set base path for the url that checks the current version
basepath="http://download.seinternal.com/integration"

# check if it's restricted to the 'frozen' tier or not
if [ "$frozen" == true ] || [ "frozen" == "$1" ]; then
    CHECKURL="$basepath/?act=getversion&panel=$paneltype&tier=frozen&rand=$random"
    filepart="_frozen.tar.gz"
    echo -e "\033[33mYou are running an old PHP version no longer actively supported by this addon so only the 'frozen' update tier is available, for critical bugfixes. It is highly recommended to upgrade to a newer PHP version (>=$RECOMMENDED_VERSION) to have access to all the latest features.\033[0m"
else
    # check for branch
    if [ -n "$1" ]; then
        if [ "trunk" == "$1" ] || [ "master" == "$1" ]; then
            CHECKURL="$basepath/?act=getversion&panel=$paneltype&tier=testing&rand=$random"
            filepart="_testing.tar.gz"
        else
            CHECKURL="$basepath/?act=getversion&panel=$paneltype&tier=testing&rand=$random&branch=$1"
            filepart="_testing_$1.tar.gz"
        fi
    else
        CHECKURL="$basepath/?act=getversion&panel=$paneltype&tier=stable&rand=$random"
        filepart="_stable.tar.gz"
    fi
fi

version=`$path_wget -q -O - "$CHECKURL" | sed -e 's/[{}]/''/g' | awk -v RS=',"' -F: '/^data/ {print $3}' |  sed s/\"//g`

if [ -z "$version" ]; then
    echo "Unable to retrieve latest version"
    exit 1
fi

# update basepath for download files
basepath="$basepath/files/$paneltype"
package="v$version"
fullfile="$package$filepart"
srcpath="/usr/src/prospamfilter"

if [ "$paneltype" == "cpanel" ]; then
    ## Make passwd hash
    if [ -f "/root/.accesshash" ]; then
        echo "Access hash for WHM API already exists, skipping step."
    else
        echo -n "Generating access hash for the WHM API.."
        export REMOTE_USER="root"
        /usr/local/cpanel/bin/realmkaccesshash
        chmod 660 /root/.accesshash
        echo "Done"
    fi

    if [ -f "/root/.accesstoken" ]; then
                 echo "Access token for WHM API already exists, skipping step."
             else
                 echo -n "Generating access token for the WHM API.."
                 output=`whmapi1 api_token_create token_name=prospamfilter acl-1=list-acct | egrep 'result: 1|token:'`

     	    arr=(${output//: /})
     		key=5
            if [[ -z "${arr[${key}]}" ]]; then
     		    token=${arr[0]}
     		    token="${token/token/}"
                echo $token > /root/.accesstoken
                chmod 660 /root/.accesstoken
                echo "Done"
     	    else
                rm -rf /root/.accesstoken
                echo "Unable to create token. $output"
                exit 1
            fi;
    fi

fi

# (Re)create /usr/local/bin/prospamfilter_php symlink to point to the validated php_binary (if needed)
# /usr/local/bin/prospamfilter_php symlink should exist so that auto-update works
if [[ ! "$php_binary" == "/usr/local/bin/prospamfilter_php" ]]; then
    ln -sfn "$php_binary" /usr/local/bin/prospamfilter_php
fi

## If we reached this point, everything seems to work fine.
if [ -d "$srcpath" ]; then
    rm $srcpath -rf
    mkdir $srcpath
else
    mkdir $srcpath
fi

if [ -d "$srcpath" ]; then
    cd $srcpath
    $path_wget -q "$basepath/$fullfile"
    if [ -f "$srcpath/$fullfile" ]; then
        tar zxf $fullfile
        if [ -d "$srcpath/bin/" ]; then
            cd bin
            if [ -f "$srcpath/bin/install.php" ]; then
                chmod +x install.php
                chmod +x installer/installer.sh
                ./install.php

                exit 0
            else
                echo "Installer does not exist"
                exit 1
            fi
            exit 1
        else
            echo "Extracted folder does not exist"
            exit 1
        fi
        exit 1
    else
        echo "Unable to download installer"
        exit 1
    fi
fi

exit 1
