#! /bin/bash

set -x

if [ `/usr/bin/svn diff translations/$3/$4.pot | wc -l` -gt 13 ]; then
    /usr/bin/svn commit -m "Update $1 strings to $2" translations/$3/$4.pot;
fi
