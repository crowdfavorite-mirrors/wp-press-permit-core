<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<div class="wrap pp-groups">
<?php pp_icon(); ?>
<h2>
<?php
_e( 'User Permissions', 'pp' );
?>
</h2>

<div style="margin-top:3em">
<?php
$url = "users.php";
printf( __( 'Both supplemental roles and exceptions can be assigned directly to a single user.  To do so, find the user in this site\'s %1$sUsers%2$s list and click their "Site Role" value.  If you don\'t see it, make sure the column is enabled in Screen Options.  The following links may be helpful:', 'pp' ), "<a href='$url'>", '</a>' );
?>
<br /><br />
<ul class="pp-notes">
<li><?php printf( __( '%1$sUsers%2$s', 'pp' ), "<a href='$url'>", '</a>' );?></li>
</ul>
<br />
<ul class="pp-notes">
<li><?php printf( __( '%1$sUsers who already have Supplemental Roles assigned directly%2$s', 'pp' ), "<a href='$url?pp_user_roles=1'>", '</a>' );?></li>
<li><?php printf( __( '%1$sUsers who already have Exceptions assigned directly%2$s', 'pp' ), "<a href='$url?pp_user_exceptions=1'>", '</a>' );?></li>
<li><?php printf( __( '%1$sUsers who already have Supplemental Roles or Exceptions directly%2$s', 'pp' ), "<a href='$url?pp_user_perms=1'>", '</a>' );?></li>
</ul>
<br />
<ul class="pp-notes">
<li><?php printf( __( '%1$sUsers who already have Supplemental Roles (directly or via group)%2$s', 'pp' ), "<a href='$url?pp_has_roles=1'>", '</a>' );?></li>
<li><?php printf( __( '%1$sUsers who already have Exceptions  (directly or via group)%2$s', 'pp' ), "<a href='$url?pp_has_exceptions=1'>", '</a>' );?></li>
<li><?php printf( __( '%1$sUsers who already have Supplemental Roles or Exceptions  (directly or via group)%2$s', 'pp' ), "<a href='$url?pp_has_perms=1'>", '</a>' );?></li>
</ul>
</div>

</div>