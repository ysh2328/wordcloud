<?php
if (isset( $_GET['COLOR'] ) )
	$color = $_GET['COLOR'];
include( $color.'.php' );
?>
