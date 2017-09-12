<?php

$content = fopen($VARS['flowFilePath'], "r");

while(!feof($content)) {
	echo fgets($content). "<br />";
}

fclose($content);
