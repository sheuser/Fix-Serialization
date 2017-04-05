#!/bin/bash -e

# Enum sql files and fix serialization.
echo "Search and replace, and fixing serialization for '*.sql'."
for f in `ls *.sql 2> /dev/null`
do
  echo "File: $f"
  sed 's/mydomain\.com/mynewdomain\.com/g' $f > $f.tmp && /usr/bin/php fix-serialization $f.tmp && mv $f.tmp $f
done

echo "Done!"
