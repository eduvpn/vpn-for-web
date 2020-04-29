<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>

<form class="search" method="get">
    <input type="text" name="searchFor" placeholder="Search..." autofocus>
</form>

<h2>Connect to your Institute</h2>
<ul>
    <form class="searchList" method="post" action="addServer">
<?php  foreach ($instituteList as $instituteEntry): ?>
    <li>
        <button name="baseUri" value="<?=$this->e($instituteEntry['base_uri']); ?>"><?=$this->l($instituteEntry['display_name']); ?></button>
    </li>
<?php endforeach; ?>
    </form>
</ul>

<!--
<h2 id="secureInternet">Protect Yourself Online</h2>

<ul>
    <form class="idpList" method="post" action="selectIdpOrServer">
<?php  foreach ($idpList as $idpEntry): ?>
    <li>
        <button name="orgId" value="<?=$this->e($idpEntry['org_id']); ?>"><?=$this->l($idpEntry['display_name']); ?></button>
    </li>
<?php endforeach; ?>
    </form>
</ul>
 -->
 
<?php $this->stop('content');
