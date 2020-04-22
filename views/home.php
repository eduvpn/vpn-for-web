<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>
    <h2>Choose your Connection Type</h2>
    <form class="discoChooser" method="post" action="setDiscoveryUrl">
        <select name="discoveryUrl">
<?php foreach ($discoChooser as $discoItem):?>
            <option value="<?=$this->e($discoItem['discoveryUrl']); ?>">
                <?=$this->e($discoItem['displayName']); ?>
            </option>
<?php endforeach; ?>
        </select>
        <button type="submit">Switch</button>
    </form>
<?php $this->stop('content');
