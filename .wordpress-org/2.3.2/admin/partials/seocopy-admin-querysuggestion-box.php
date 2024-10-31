<script>window.seocopySettings = <?php echo json_encode(array(
        //'key' => get_option('seocopy_apikey_setting'),
        'key' => get_option('sc_api_key'),
        'baseurl' => seocopyApi::BASEURL,
        'language' => array(
            'wrong_tag' => __('Wrong tag used', seocopy_DOMAIN),
            'tag_title' => __('Article title', seocopy_DOMAIN),
            'tag_strong' => __('Bold', seocopy_DOMAIN),
            'tag_p' => __('Paragraph', seocopy_DOMAIN),
            'tag_h2' => __('Title 2', seocopy_DOMAIN),
        )
    )) ?>;</script>
<p style="text-align: left;"><small><i>You have <span style="color: #ba000d"><?php echo esc_html($this->seocopy_get_credits()); ?> credits left</span> - Click <a href="https://wpseoplugins.org/" target="_blank">here</a> to purchase more credits.</i></small></p>
<div id="seocopy-query-suggestion-div">
    <label for="seocopy-query-suggestion-language-input">
        <?php echo __('Language', seocopy_DOMAIN) ?>
    </label>
    <?php $api_key = get_option( 'sc_api_key', true );?>
    <?php if( !empty($api_key) ) : ?>
    <select id="seocopy_query-suggestion-language-input">
        <?php foreach($this->getApiAllowedLanguages() as $langkey=>$langname) { ?>
            <option value="<?php echo esc_html( $langkey ); ?>" <?php if ($langkey === $this->getApiLanguage()){ echo 'selected="true"'; } ?>><?php echo esc_html( $langname ); ?></option>
        <?php } ?>
    </select>
    <label for="seocopy-query-suggestion-input"><?php echo __('Enter a keyword or a topic', seocopy_DOMAIN) ?></label>
    <input id="seocopy-query-suggestion-input" type="text" name="seocopy_keyword" size="16" autocomplete="off" value="" />
    <button id="seocopy-query-suggestion-submit" type="button" class="button button-primary button-large" data-api-key="<?php echo esc_html( $api_key ); ?>"><?php echo __('Search') ?></button>
    <?php else: ?>
    <p>You must specify an api key.</p>
    <?php endif; ?>
</div>
<div id="seocopy-query-suggestion-searching-div" style="display: none">
    <p><?php echo __('Loading results.. Please wait', seocopy_DOMAIN) ?></p>
    <img src="<?php echo plugin_dir_url( dirname(dirname(__FILE__))) . 'admin/img/loader.svg'; ?>" alt="loading" />
    <div class="seocopy-query-suggestion-loader-div"></div>
    <div id="seocopy-query-suggestion-searching-random-texts-div">
        <?php
        $randomText = [
            __('We are looking for keywords.. meawhile, you can start writing! ', seocopy_DOMAIN),
            __('Try to write an in depth long article for your keyword', seocopy_DOMAIN),
            __('Remember to use your goal keyword  in titles and paragraphs.', seocopy_DOMAIN),
            __('Optimize titles, subtitles and text with semantic related keywords for better rankings', seocopy_DOMAIN),
            __('Use images and remember to use keywords in images too!', seocopy_DOMAIN),
            __('Write short paragraphs, and use subtitles to make an article easy to understand', seocopy_DOMAIN),
        ];
        foreach($randomText as $i=>$text){
            echo '<span>'. esc_html( $text ) .'</span>';
        }
        ?>
    </div>
</div>
<div id="seocopy-query-suggestion-error-div" style="display: none">
    <p id="seocopy-query-suggestion-error-unable-connect"><?php echo __('Unable to connect.. Please retry', seocopy_DOMAIN) ?></p>
    <p id="seocopy-query-suggestion-error-no-balance"><a href="https://www.seocopy.com/" target="_blank" ref="nofollow"><?php echo __('Balance finished, you can top it up clicking here', seocopy_DOMAIN) ?></a></p>
    <p id="seocopy-query-suggestion-error-no-key"><a href="<?php echo admin_url('admin.php?page=seocopy_menu_page') ?>"><?php echo __('No key added, please click here to set it', seocopy_DOMAIN) ?></a></p>
    <p id="seocopy-query-suggestion-error-wrong-key"><a href="<?php echo admin_url('admin.php?page=seocopy_menu_page') ?>"><?php echo __('Wrong key, please check', seocopy_DOMAIN) ?></a></p>
</div>
<div id="seocopy-query-suggestion-results-div" style="display: none">
    <script type="text/template" id="seocopy-query-suggestion-resultgroup-template">
        <div class="seocopy-query-suggestion-resultgroup-tagwrap">
            <p class="seocopy-query-suggestion-resultgroup-tagname"><span class="seocopy-query-suggestion-resultgroup-tagname-text"></span><span class="seocopy-query-suggestion-resultgroup-counter"></span></p>
            <ul class="seocopy-query-suggestion-results-group-container"></ul>
        </div>
    </script>
    <script type="text/template" id="seocopy-query-suggestion-resultitem-template">
        <li>
           <span class="seocopy-query-suggestion-resultitem-name"></span>
        </li>
    </script>
    <div id="seocopy-query-suggestion-results-reset">
        <button id="seocopy-query-suggestion-results-reset-button" type="button"  class="button button-link"><?php echo __('Reset') ?></button>
        <div id="seocopy-query-suggestion-results-reset-confirm" style="display: none">
            <div><?php echo __('Are you sure? The current search will be lost and you will have to submit a new one') ?></div>
            <button id="seocopy-query-suggestion-results-reset-button-cancel" type="button" class="button button-link"><?php echo __('Cancel reset') ?></button>
            <button id="seocopy-query-suggestion-results-reset-button-confirm" type="button" class="button button-link"><?php echo __('Confirm reset') ?></button>
        </div>
    </div>
    <div id="seocopy-query-suggestion-results-container">

    </div>
</div>
