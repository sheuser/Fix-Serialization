#!/bin/bash -e

echo "Search and replace, and fixing serialization for '*.sql'."
for file in *.sql; do
  echo "File: $f"
  sed -i 's/mydomain\.com/mynewdomain\.com/g' "$file" && /usr/bin/php fix-serialization.php "$file"
done

echo "Done!"
