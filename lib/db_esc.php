<?php

function db_esc($str)
{
return mysqli_real_escape_string($GLOBALS['fbx']['dbh'], (string)$str);
}
