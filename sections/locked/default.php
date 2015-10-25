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
<? } /*<strip>*/ else if (G::$LoggedUser['LockedAccount'] == EXPIRED_PASSWORD || check_perms('users_mod')) { ?>
<div class="box pad">
    <p>
        Private tracker accounts are frequently targeted by hackers who sell accounts or invites, and over the
        past few weeks a significant number of What.CD accounts have been hacked.
        <strong class="important_text">Every single account that was hacked had an old password that had also
                                       been used on other sites.</strong>
        When passwords used on other sites are leaked, accounts on What.CD become vulnerable.
    </p><br />
    <p>
        Dealing with hacked accounts is time-consuming and inconvenient for both staff members and the users
        involved. If you see this page, it's because your password hasn't been changed in at least the last two
        years. To continue using What.CD, you must choose a new password.
    </p><br />
    <p>
        Remember: <strong class="important_text">NEVER use your What.CD
        password on other sites.</strong>
    </p><br />
    <p>
        Click <a href="locked.php?action=sendEmail">here</a> to send a confirmation email to verify
        that the original account owner is resetting the password. <br />
        <i>Note: </i> When clicking this link, click "Log In", or nagivate to any What.CD page to continue back
        to the site. Resetting your password will <b>not</b> log you out.
    </p><br />
    <p>
        If you no longer have access to your email <?=$Email?>, click
        <a href="locked.php?action=staffpm">here</a> and staff will help you via the Staff Inbox.
    </p>
</div>
<? }
//</strip>
View::show_footer();