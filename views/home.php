<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>
<h2>Institute Access</h2>
<?php if (0 === count($myInstituteList)): ?>
    <span class="mute">No Institute Yet...</span>
<?php else: ?>
    <ul>
        <form method="get" action="getProfileList">
<?php  foreach ($myInstituteList as $instituteEntry): ?>
        <li>
            <button name="baseUri" value="<?=$this->e($instituteEntry['base_uri']); ?>"><?=$this->e($instituteEntry['display_name']); ?></button>
        </li>
<?php endforeach; ?>
        </form>
    </ul>
<?php endif; ?>
<details>
    <summary>Add New Institute</summary>
    <ul>
        <form method="post" action="addInstitute">
<?php  foreach ($instituteList as $instituteEntry): ?>
        <li>
            <button name="baseUri" value="<?=$this->e($instituteEntry['base_uri']); ?>"><?=$this->e($instituteEntry['display_name']); ?></button>
        </li>
<?php endforeach; ?>
        </form>
    </ul>
</details>

<h2>Secure Internet</h2>
<span class="mute">Not Yet Implemented...</span>

<?php $this->stop('content');
