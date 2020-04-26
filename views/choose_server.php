<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>

<?php if ($showSecureInternetHint): ?>
<?php if (!$hasSecureInternetHome && $hasInstituteAccess): ?>
<form class="home center" method="get" action="chooseIdP">
    <button>🌍 Protect yourself Online...</button>
</form>
<?php endif; ?>
<?php endif; ?>

<h2>Select your Institute</h2>
<ul>
    <form method="post" action="addServer">
<?php  foreach ($instituteList as $instituteEntry): ?>
    <li>
        <button name="baseUri" value="<?=$this->e($instituteEntry['base_uri']); ?>">
            <?=$this->l($instituteEntry['display_name']); ?>
        </button>
    </li>
<?php endforeach; ?>
    </form>
</ul>
<?php if ($showSecureInternetHint): ?>
<?php if (!$hasSecureInternetHome && !$hasInstituteAccess): ?>
<form class="home center" method="get" action="chooseIdP">
    <button>🌍 Protect yourself Online...</button>
</form>
<?php endif; ?>
<form class="home center" method="get" action="addOtherServer">
    <button>👽 Add Other Server...</button>
</form> 
<?php endif; ?>
<?php $this->stop('content');
