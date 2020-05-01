<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>

<h2>Institute Access</h2>
<h3>Choose your Institute</h3>
<form class="search" method="get">
    <input type="text" name="searchFor" placeholder="Search..." autofocus>
</form>
<ul>
    <form class="searchList" method="post" action="addServer">
<?php  foreach ($instituteList as $instituteEntry): ?>
    <li>
        <button name="baseUri" value="<?=$this->e($instituteEntry['base_uri']); ?>"><?=$this->l($instituteEntry['display_name']); ?></button>
    </li>
<?php endforeach; ?>
    </form>
</ul>

<div class="noResults">No results...</div>

<?php if (!$hasSecureInternet): ?>
<h2>Secure Internet</h2>
<form class="searchList" method="get" action="chooseOrganization">
    <button>🌍 Protect Yourself Online...</button>
</form>
<?php endif; ?>
<?php $this->stop('content');
