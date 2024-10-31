<div class="wrap">
    <hr />
    <div class="row">
        <div class="col-12">
            <table class="table">
                <tbody>
                <?php if(isset($_SESSION['status']) && sanitize_text_field( $_SESSION['status'] ) == 1) : ?>
                    <tr>
                        <td colspan="2">
                            <div class="notice notice-success is-dismissible"><p><?php echo esc_html( $_SESSION['message'] ); ?></p></div>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php if(isset($_SESSION['status']) && sanitize_text_field( $_SESSION['status'] ) == -10) : ?>
                    <tr>
                        <td colspan="2">
                            <div class="notice notice-error is-dismissible"><p><?php echo esc_html( $_SESSION['message'] ); ?></p></div>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php if(isset($_SESSION['status']) && sanitize_text_field( $_SESSION['status'] ) == 0) : ?>
                    <tr>
                        <td colspan="2">
                            <div class="notice notice-success is-dismissible"><p><?php echo esc_html( $_SESSION['message'] ); ?></p></div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="row">
        <div class="col-6">
            <div id="icon-themes" class="icon32"></div>
            <h2><?php echo __('Login and get an Api Key', seocopy_DOMAIN) ?></h2>
            <form action="<?php echo site_url(); ?>/wp-admin/admin-post.php" method="post">
                <?php $nonce = wp_create_nonce( 'seocopy_login_nonce' ); ?>
                <input type="hidden" name="security" value="<?php echo $nonce; ?>" />
                <input type="hidden" name="action" value="seocopy_login" />
                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row">
                            <label for="email">Email</label>
                        </th>
                        <td><input name="email" type="text" id="email" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="password">Password</label>
                        </th>
                        <td><input name="password" type="password" id="password" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"></th>
                        <td><button type="button" class="button button-secondary" onclick="seocopy_forgotPasswordConfirmDialog()">Forgot password?</button></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="text-align: left;">
                            <button type="submit" class="button button-primary">Login</button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </form>
        </div>
        <div class="col-6">
            <div id="icon-themes" class="icon32"></div>
            <h2><?php echo __('Register as new user and get an Api Key', seocopy_DOMAIN) ?></h2>
            <form action="<?php echo site_url(); ?>/wp-admin/admin-post.php" method="post">
                <?php $nonce = wp_create_nonce( 'seocopy_registration_nonce' ); ?>
                <input type="hidden" name="security" value="<?php echo $nonce; ?>" />
                <input type="hidden" name="action" value="seocopy_registration">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="name">Name</label>
                            </th>
                            <td><input name="name" type="text" id="name" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="surname">Surname</label>
                            </th>
                            <td><input name="surname" type="text" id="surname" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="email">Email</label>
                            </th>
                            <td><input name="email" type="text" id="email" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="password">Password</label>
                            </th>
                            <td><input name="password" type="password" id="password" class="regular-text"></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td style="text-align: left;">
                                <button type="submit" class="button button-primary">Register</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
        </div>
    </div>
    <hr />
    <div id="icon-themes" class="icon32"></div>
    <h2><?php echo __('Api Key', seocopy_DOMAIN) ?></h2>
    <?php settings_errors(); ?>

    <form method="POST" action="options.php">
        <?php wp_nonce_field('update-options') ?>
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row">
                    <label for="input_id">API KEY</label>
                </th>
                <td>
                    <input name="sc_api_key" type="text" id="sc_api_key" class="regular-text" value="<?php echo esc_html( ( isset( $_SESSION['status'] ) && sanitize_text_field( $_SESSION['status'] ) == 0 ) ? ( $_SESSION['api_key'] ?? get_option( 'sc_api_key' ) ) : get_option( 'sc_api_key' ) ); ?>">
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: right;">
                    <button type="submit" class="button button-primary">Save</button>
                    <input type="hidden" name="action" value="update" />
                    <input type="hidden" name="page_options" value="sc_api_key" />
                </td>
            </tr>
            </tbody>
        </table>
    </form>
    <p style="text-align: right;"><small><i>You have <span style="color: #ba000d"><?php echo esc_html($this->seocopy_get_credits()); ?> credits left</span> - Click <a href="https://wpseoplugins.org/" target="_blank">here</a> to purchase more credits.</i></small></p>
    <!--
    <form method="POST" action="options.php">
        <?php
        settings_fields('seocopy_general_settings');
        do_settings_sections('seocopy_general_settings');
        ?>
        <?php submit_button(); ?>
    </form>
    -->
    <?php
    if (get_option('seocopy_apikey_setting')) {
        try {
            $balance = seocopyApi::getBalance(get_option('seocopy_apikey_setting'));
            ?>
            <!-- <h3><?php echo __('Balance', seocopy_DOMAIN) ?></h3> -->
            <!-- <p><?php echo __('Your current balance is:') ?>&nbsp;<em><?php echo number_format_i18n($balance) ?></em></p> -->
            <?php
        } catch (\Exception $e) {

        }
    }
    ?>
</div>

<?php
unset( $_SESSION['status'] );
unset( $_SESSION['message'] );
unset( $_SESSION['api_key'] );
