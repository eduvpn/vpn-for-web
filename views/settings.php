<?php $this->layout('base'); ?>
<?php $this->start('content'); ?>
    <h2>Settings</h2>
    <form method="post" action="saveSettings">
        <fieldset>
            <label><input type="checkbox" name="forceTcp" <?=$forceTcp ? 'checked' : ''; ?>> Connect using TCP only</label> 
        </fieldset>

        <fieldset>
            <label>Flow <select name="flowId">
                <option value="modern_two_buttons" <?='modern_two_buttons' === $flowId ? 'selected' : ''; ?>>Modern Two Buttons (Tangui 2)</option>
                <option value="merged_server_idp" <?='merged_server_idp' === $flowId ? 'selected' : ''; ?>>Merged Idp &amp; Server (Tangui 3)</option>  
                <option value="focus_on_institute_access" <?='focus_on_institute_access' === $flowId ? 'selected' : ''; ?>>Focus on Institute Access (FrKo 2)</option>  
            </select></label>
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
