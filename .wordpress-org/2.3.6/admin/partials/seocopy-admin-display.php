<div class="wrap">
    <div style="padding-right: 20px">
        <h3>Copy</h3>
        <form method="POST" action="options.php">
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="seocopy_language" />
            <?php wp_nonce_field('update-options') ?>
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row"><label for="select_id">Select default language</label></th>
                    <td>
                        <select name="seocopy_language" id="seocopy_language-input">
                            <?php $lang = get_option( 'seocopy_language' ); ?>
                            <?php foreach($this->getApiAllowedLanguages() as $langkey => $langname) { ?>
                                <option value="<?php echo esc_html( $langkey ); ?>" <?php if ($langkey === $this->getApiLanguage()){ echo 'selected="true"'; } ?>><?php echo esc_html( $langname ); ?></option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: left;">
                        <button type="submit" class="button button-primary">Save</button>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
    </div>
</div>