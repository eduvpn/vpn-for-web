<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>

<h2>Choose your Institute</h2>
<form class="search" method="get">
    <input type="text" name="searchFor" placeholder="Search..." autofocus>
</form>

<div id="instituteAccess">
    <h3>🏛️ Institute Access</h3>
    <p class="info">
Connect to your Institute's network, for example when working from home and needing access
to data or websites located on campus.
    </p>
</div>

<ul>
    <form id="instituteAccessList" class="searchList" method="post" action="selectServer">
<?php  foreach ($instituteList as $instituteEntry): ?>
    <li>
<?php if (array_key_exists('keyword_list', $instituteEntry)): ?>
        <button name="baseUri" data-keywords="<?=$this->l($instituteEntry['keyword_list']); ?>" value="<?=$this->e($instituteEntry['base_uri']); ?>"><?=$this->l($instituteEntry['display_name']); ?></button>
<?php else: ?>
        <button name="baseUri" data-keywords="" value="<?=$this->e($instituteEntry['base_uri']); ?>"><?=$this->l($instituteEntry['display_name']); ?></button>
<?php endif; ?>
    </li>
<?php endforeach; ?>
    </form>
</ul>

<?php if (!$hasSecureInternet): ?>
<div id="secureInternet">
    <h3>🌍 Secure Internet</h3>
    <p class="info">
    Secure your Internet connection, for example when traveling abroad or working from a coffeehouse.
    </p>
</div>

<ul>
    <form id="secureInternetList" class="searchList" method="post" action="selectOrganization">
<?php  foreach ($organizationList as $organizationEntry): ?>
    <li>
<?php if (array_key_exists('keyword_list', $organizationEntry)): ?>
        <button name="orgId" data-keywords="<?=$this->l($organizationEntry['keyword_list']); ?>" value="<?=$this->e($organizationEntry['org_id']); ?>">
<?php else: ?>
        <button name="orgId" data-keywords="" value="<?=$this->e($organizationEntry['org_id']); ?>">
<?php endif; ?>
            <?=$this->l($organizationEntry['display_name']); ?>
        </button>
    </li>
<?php endforeach; ?>
    </form>
</ul>
<?php endif; ?>

<div id="noResults" class="noResults">⚠️ No Match Found...</div>
<?php $this->stop('content');
