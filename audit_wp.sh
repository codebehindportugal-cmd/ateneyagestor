#!/bin/bash
for dir in /var/www/*/; do
  site=$(basename "$dir")
  wpconf="$dir/wp-config.php"
  [ -f "$dir/public_html/wp-config.php" ] && wpconf="$dir/public_html/wp-config.php"
  [ -f "$wpconf" ] || continue
  root=$(dirname "$wpconf")
  db=$(php -r "include '$wpconf'; echo DB_NAME;" 2>/dev/null)
  user=$(php -r "include '$wpconf'; echo DB_USER;" 2>/dev/null)
  pass=$(php -r "include '$wpconf'; echo DB_PASSWORD;" 2>/dev/null)
  size=$(du -sh "$root/wp-content" 2>/dev/null | cut -f1)
  echo "$site | root=$root | db=$db | user=$user | pass=$pass | wpcontent=$size"
done
