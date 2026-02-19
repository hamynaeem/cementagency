<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
| SQLite Database Configuration
| Use this configuration if MySQL is not available
| To use this:
| 1. Rename this file to database.php (backup the original first)
| 2. Make sure the sqlite3 directory is writable
*/

$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
  'dsn'  => '',
  'hostname' => '',
  'username' => '',
  'password' => '',
  'database' => APPPATH . '../sqlite3/db_cement.db',
  'dbdriver' => 'sqlite3',
  'dbprefix' => '',
  'pconnect' => FALSE,
  'db_debug' => (ENVIRONMENT !== 'production'),
  'cache_on' => FALSE,
  'cachedir' => '',
  'char_set' => 'utf8',
  'dbcollat' => 'utf8_general_ci',
  'swap_pre' => '',
  'encrypt' => FALSE,
  'compress' => FALSE,
  'stricton' => FALSE,
  'failover' => array(),
  'save_queries' => TRUE
);