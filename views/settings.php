<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>
    <h2>Settings</h2>
    <form method="post" action="saveSettings">
        <fieldset>
            <label><input type="checkbox" name="forceTcp" <?=$forceTcp ? 'checked' : ''; ?>> Connect using TCP only</label> 
        </fieldset>
        <fieldset>
            <button value="apply" type="submit">Apply</button>
        </fieldset>
    </form>
    <hr>
    <form method="post" action="resetAppData">
        <fieldset>
            <button value="reset" type="submit">Logout</button>
        </fieldset>
    </form>
<?php $this->stop('content');
