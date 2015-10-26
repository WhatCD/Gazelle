<?
View::show_header('Locked Account');
?>
<div class="header">
    <h2>Locked Account</h2>
</div>
<? if (G::$LoggedUser['LockedAccount'] == STAFF_LOCKED) { ?>
<div class="box pad">
    <p>Your account has been locked. Please send a <a href="staffpm.php">Staff PM</a> to find out how this happened.</p>
</div>
<? } 
View::show_footer();
