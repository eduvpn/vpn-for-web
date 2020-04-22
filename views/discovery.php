<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>
    <h2>Choose your Provider</h2>

    <form method="post" action="home">
        <button type="submit">&lt; Back</button>
    </form>

    <div id="providerList">
        <ul id="chooser">
<?php foreach ($providerList as $key => $provider): ?>
            <li>
                <form class="entity" method="post">
                    <button name="provider_id" value="<?=$this->e($provider['base_uri']); ?>" tabindex="<?=$this->e($key + 2); ?>" class="<?=$this->e($provider['hostName']); ?>"><?=$this->e($provider['display_name']); ?></button>
                </form>
            </li>
<?php endforeach; ?>
        </ul>
    </div>
<?php $this->stop('content');
