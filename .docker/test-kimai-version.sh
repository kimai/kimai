#!/bin/sh

# An reg wizard can probably do this without the cut command
VER=$(echo $KIMAI | sed 's/[^0-9]//g' | cut -c1-3)

if test "${VER}" -lt 111
then
  echo "+--------------------------------------------------------------------------+"
  echo "| Kimai versions older than 1.11 require composer 1.x                      |"
  echo "| To build older versions you'll need to use a tagged version of this repo |"
  echo "| https://github.com/tobybatch/kimai2/releases/tag/EOL-composer-1.x        |"
  echo "|                                                                          |"
  echo "| See https://github.com/tobybatch/kimai2/issues/180                       |"
  echo "+--------------------------------------------------------------------------+"
  return 1
fi

