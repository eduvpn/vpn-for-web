<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>

<form class="search" method="get">
    <input type="text" name="searchFor" placeholder="Search..." autofocus>
</form>

<h2>Select your Organization</h2>
<ul>
    <form class="searchList" method="post" action="selectOrganization">
<?php  foreach ($organizationList as $organizationEntry): ?>
    <li>
        <button name="orgId" value="<?=$this->e($organizationEntry['org_id']); ?>">
            <?=$this->l($organizationEntry['display_name']); ?>
        </button>
    </li>
<?php endforeach; ?>
    </form>
</ul>
<?php $this->stop('content');
