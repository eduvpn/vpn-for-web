<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>
    <h2>Download</h2>

    <form method="post" action="download">
        <input type="hidden" name="provider_id" value="<?=$this->e($providerId); ?>">
        <fieldset>
            <button name="action" value="back">&lt; Back</button>
        </fieldset>
    </form>

    <p>
        Choose a profile to download an OpenVPN configuration file for the VPN provider 
        <strong><?=$this->e($displayName); ?></strong>.
    </p>

    <table>
        <thead>
            <tr><th>Profile</th><th></th></tr>
        </thead>
        <tbody>
<?php foreach ($profileList as $profile): ?>
            <tr>
                <td><?=$this->e($profile['display_name']); ?></td>
                <td class="right">
                    <form method="post" action="download">
                        <input type="hidden" name="provider_id" value="<?=$this->e($providerId); ?>">
                        <input type="hidden" name="profile_id" value="<?=$this->e($profile['profile_id']); ?>">
                        <fieldset>
                            <button name="action" value="download">Download</button>
                        </fieldset>
                    </form>
                </td>
            </tr>
<?php endforeach; ?>
        </tbody>
    </table>
<?php $this->stop('content');
