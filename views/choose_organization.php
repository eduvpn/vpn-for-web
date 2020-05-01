<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>

<h2>🌍 Secure Internet</h2>
<h3>Choose your Institute</h3>
<form class="search" method="get">
    <input type="text" name="searchFor" placeholder="Search..." autofocus>
</form>

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

<div class="noResults">No results...</div>

<?php $this->stop('content');
