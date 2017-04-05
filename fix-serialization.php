#!/usr/bin/php
<?php

/*
* Blogestudio Fix Serialization  1.2
* Fixer script of length attributes for serialized strings (e.g. Wordpress databases)
* License: GPL version 3 or later - http://www.gnu.org/licenses/gpl.txt
* By Pau Iglesias
* http://blogestudio.com
*
* Inspiration and regular expression code base from David Coveney:
* http://davidcoveney.com/575/php-serialization-fix-for-wordpress-migrations/
*
* Usage:
*
*   /usr/bin/php fix-serialization.php my-sql-file.sql
*
* Versions:
*
*   1.0 2011-08-03 Initial release
*   1.1 2011-08-18 Support for backslashed quotes, added some code warnings
*   1.2 2011-09-29 Support for null or zero length strings after preg_replace is called, and explain how to handle these errors
*
* Knowed errors:
*
* - Memory size exhausted
* Allowed memory size of 67108864 bytes exhausted (tried to allocate 35266489 bytes)
* How to fix: update php.ini memory_limit to 512M or more, and restart cgi service or web server
*
* - Function preg_replace returns null or 0 length string
* If preg_last_error = PREG_BACKTRACK_LIMIT_ERROR (value 2), increase pcre.backtrack_limit in php.ini (by default 100k, change to 2M by example)
* Same way for others preg_last_error codes: http://www.php.net/manual/en/function.preg-last-error.php
*
* TODO next versions
*
* - Check if needed UTF-8 support detecting and adding u PCRE modifier
*
*/



// Unescape to avoid dump-text issues
function unescape_mysql($value) {
  return str_replace(array("\\\\", "\\0", "\\n", "\\r", "\Z",  "\'", '\"'),
                     array("\\",   "\0",  "\n",  "\r",  "\x1a", "'", '"'),
                     $value);
}

function escape_mysql($value) {
  return str_replace(array("\\",   "\0",  "\n",  "\r",  "\x1a", "'", '"'),
                     array("\\\\", "\\0", "\\n", "\\r", "\Z",  "\'", '\"'),
                     $value);
}

function preg_cb($m, $mysql) {
  $v = isset($m[3]) ? $m[3] : '';
  $v2 = $mysql ? unescape_mysql($v) : $v;
  $s = trim(serialize($v2));

  return $mysql ? escape_mysql($s) : $s;
}

function cb1_mysql($m) {
  return preg_cb($m, true);
}

function cb2_plain($m) {
  return preg_cb($m, false);
}


try {
  // Check command line arguments.
  if (!is_array($argv) || !isset($argv[1])) {
    throw new Exception("No input file specified.");
  }

  // Compose path from argument.
  $path = $argv[1]; // Handle relative paths.
  if (!file_exists($path)) {
    throw new Exception("Input file does not exist: $path");
  }

  // File exists

  // Get file contents
  if (!($fp = fopen($path, 'r'))) {
    throw new Exception("Can`t open input file for reading: $path");
  }

  // File opened for read

  // Copy data
  if (!($data = fread($fp, filesize($path)))) {
    throw new Exception("Can`t read entire data from input file: $path");
  }

  // Check data
  if (!(isset($data) && strlen($data) > 0)) {
    throw new Exception("The file is empty or can't read contents: $path");
  }

  // Data ok

  // Replace serialized string values
  $data = preg_replace_callback('/s:(\d+):([\\\\]"[\\\\]"|[\\\\]"((.*?)[^\\\\])[\\\\]");(?=($|[}]|[a-z]:\d+|[\'][)]))/', "cb1_mysql", $data);
  $data = preg_replace_callback('/s:(\d+):(""|"(.*?)");(?=($|[}]|[a-z]:\d+))/', "cb2_plain", $data);

  // Close file
  fclose($fp);

  // Check data
  if (!(isset($data) && strlen($data) > 0)) {
    $preg_error = function_exists('preg_last_error') ? preg_last_error() : '';
    throw new Exception("preg_replace returns nothing\n$preg_error");
  }

  // Data Ok

  // And finally write data
  if (!($fp = fopen($path, 'w'))) {
    throw new Exception("Can't open input file for writing: $path");
  }

  // Open for write

  // Write file data
  if (!fwrite($fp, $data)) {
    throw new Exception("Can't write input file: $path");
  }

  // Close file
  fclose($fp);
}
catch (Exception $e) {
  echo "ERROR: " . trim($e->getMessage()) . "\n";
  exit(1);
}
