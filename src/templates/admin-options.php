<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2><?php echo $this->pluginName; ?> <?php _e('settings', 'rb-fitocracy'); ?></h2>

    <?php if (isset($updateSuccess)): ?>
        <div class="updated"><p><strong><?php _e('Settings saved', 'rb-fitocracy'); ?>.</strong></p></div>
    <?php endif; ?>

    <form method="post" action="">
        <input type="hidden" name="rbfitocracy_submit" value="1" />

        <h3><?php _e('General', 'rb-fitocracy'); ?></h3>
        <table class="form-table" width="100%">
            <tr valign="top">
                <th scope="row"><?php _e('Fitocracy username', 'rb-fitocracy'); ?></th>
                <td>
                    <fieldset>
                        <label for="rb-fitocracy-username">
                            <input name="rb-fitocracy-username" type="name" value="<?php echo $options['rb-fitocracy-username']; ?>" />
                        </label>
                    </fieldset>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Fitocracy password', 'rb-fitocracy'); ?></th>
                <td>
                    <fieldset>
                        <label for="rb-fitocracy-password">
                            <input name="rb-fitocracy-password" type="password" />
                        </label>
                    </fieldset>
                    <p>As soon as Fitocracy release an API we can ditch the password field. Your password will be encrypted and stored in your Wordpress database but please use something unique to Fitocracy - just incase!</p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="Submit" class="button-primary" value="<?php _e('Save Changes', 'rb-internal-links'); ?>" />
        </p>

    </form>

</div>