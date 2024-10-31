(function ($) {
    'use strict';

    var seocopy = {
        data: {
            currentSearchUuid: false,
            currentSearchedKeyword: null,
            currentSearchKeywords: null,
            currentAnalyzeResults: null,
            debouncingAnalyzeTimeout:null,
            debouncingAnalyzeChanged:false,
            debouncingAnalyzeData:null,

            currentQuerySuggestionUuid: false,
            currentQuerySuggestionKeywords: [],
            currentQuerySuggestionAnalyzeResults: null,
        },
        api: {
            // https://www.seocopy.com/apidocs/
            createResourceRequest: function (feature, language, query, options, callback, callbackerror, callbackanyway) {
                var data = {
                    query: query,
                    database: language,
                    features: feature,
                    'key': seocopySettings.key,
					limit: 30,
                    action: 'seocopy_keyword_search'
                };
                data = $.extend(data, options);
                var request = $.ajax({
                    url: seocopySettings.baseurl + 'keyword-research',
                    type: "get",
                    data: data,
                    headers: {
                        // 'x-seocopy-key': seocopySettings.key
                    }
                });
                request.done(function (response, textStatus, jqXHR) {
                    if (response && response.code && response.code !== 200) {
                        callbackerror(response.code);
                    } else {
                        response = JSON.parse( response );
                        if( response && response.status && ( response.status == -1 || response.status == -2 || response.status == -3 ) ){
                            callbackerror( response );
                        } else if( response.status == -4 ) {
                            setTimeout(function(){
                                $('#no_more_credits_modal').modal('show');
                                $('#seocopy-keyword-searching-div').hide();
                                $('#seocopy-keyword-search-div').show();
                            }, 800);
                        } else {
                            callback( response ); // NEW
                            //callback( response.token ); // OLD
                        }
                    }
                });
                request.fail(function (jqXHR, textStatus, errorThrown) {
                    console.error(
                        "The following error occurred: " +
                        textStatus, errorThrown
                    );
                    callbackerror(response);
                });
                request.always(function () {
                    callbackanyway();
                });
            },
            retrieveResource: function (uuid, callback, callbackerror, callbackanyway) {
                var data = {
                    'key': seocopySettings.key
                };
                var request = $.ajax({
                    url: seocopySettings.baseurl + 'keyword-research/' + uuid,
                    type: "get",
                    data: data,
                    headers: {
                        // 'x-seocopy-key': seocopySettings.key
                    }
                });
                request.done(function (response, textStatus, jqXHR) {
                    if (response && response.code && response.code !== 200) {
                        callbackerror(response);
                    } else {
                        callback(response);
                    }
                });
                request.fail(function (jqXHR, textStatus, errorThrown) {
                    console.error(
                        "The following error occurred: " +
                        textStatus, errorThrown
                    );
                });
                request.always(function () {

                });
            },

        },
        decideKeywordTags:function(keyword){
            var rets = [];
            if(keyword.use_in_title){
                rets.push('title');
            }
            if(keyword.use_in_p){
                rets.push('p');
            }
            if(keyword.use_in_h2){
                rets.push('h2');
            }
            if(keyword.use_in_strong){
                rets.push('strong');
            }

            return rets;
        },
        printFoundKeywords:function(keywords){
            console.log( 'printFoundKeywords' );
            var that = this;
            $('#seocopy-keyword-searching-div').hide();
            $('#seocopy-keyword-error-div').hide();
            $('#seocopy-keyword-results-div').show();
            $('#seocopy-keyword-results-container').html('');
            var templateResultGroup = $($('#seocopy-keyword-resultgroup-template').html());
            var templateResultItem = $($('#seocopy-keyword-resultitem-template').html());
            var groups = {'title':[],'h2':[],'strong':[],'p':[]};
            // Sort by relevance
            keywords = keywords.sort(function(y,x){
                return x.relevance - y.relevance;
            });
            // keywords = keywords.slice(0, 30);
            // Choose tags
            keywords.forEach(function(keyword){
                var decidedTags = that.decideKeywordTags(keyword);
                if(!decidedTags){
                    return;
                }
                decidedTags.map(function(y){
                   if(!groups[y]){
                       groups[y] = [];
                   }
                   groups[y].push(keyword);
                });
            });
            // Sort only p by count instead of relevance
            groups['p'] = groups['p'].sort(function(y,x){
                return x.count - y.count;
            });
            // Print
            var firstiterated = false;
            var keyid = 0;
            Object.keys(groups).forEach(function(group){
                if(!groups[group].length){
                    return;
                }
               var groupcontainer = templateResultGroup.clone();
               $('.seocopy-keyword-resultgroup-tagname-text',groupcontainer).text(seocopySettings.language['tag_'+group.toLowerCase()]);
               $('.seocopy-keyword-resultgroup-tagname',groupcontainer).click(function(e){
                   e.preventDefault();
                   groupcontainer.toggleClass('seocopy-keyword-resultgroup-tagwrap-closed');
               });
               if(firstiterated){
                   groupcontainer.addClass('seocopy-keyword-resultgroup-tagwrap-closed');
               }
               groups[group].forEach(function(key){
                   var itemcontainer = templateResultItem.clone();
                   $('.seocopy-keyword-resultitem-name',itemcontainer).text(key.keyword);
                   if(!key['_tagnames']){
                       key['_tagnames'] = {};
                   }
                   key['_tagnames'][group.toUpperCase()] = itemcontainer;
                   key['_id'] = keyid++;
                   $('.seocopy-keyword-results-group-container',groupcontainer).append(itemcontainer);
               });
                $('#seocopy-keyword-results-container').append(groupcontainer);
                firstiterated = true;
            });
            // this.computeResultsCounters();
            // this.fillDensityNoResults();
            this.forceTriggerAnalysis();
        },
        printFoundQuerySuggestionKeywords: function( keywords ){
            console.log( 'printFoundQuerySuggestionKeywords' );
            var that = this;
            $('#seocopy-query-suggestion-searching-div').hide();
            $('#seocopy-query-suggestion-error-div').hide();
            $('#seocopy-query-suggestion-results-div').show();
            $('#seocopy-query-suggestion-results-container').html('');
            var templateResultGroup = $($('#seocopy-query-suggestion-resultgroup-template').html());
            var templateResultItem = $($('#seocopy-query-suggestion-resultitem-template').html());
            // Sort by relevance
            keywords = keywords.sort(function(y,x){
                return x.relevance - y.relevance;
            });

            var firstiterated = false;
            var keyid = 0;
            Object.keys( keywords ).forEach(function(group){

                var groupcontainer = templateResultGroup.clone();

                const group_name = group.split('+').join(' ');
                $('.seocopy-query-suggestion-resultgroup-tagname-text',groupcontainer).text( group_name );
                //$('.seocopy-query-suggestion-resultgroup-tagname-text',groupcontainer).text( decodeURI( group ).replace('+', ' ') );
                $('.seocopy-query-suggestion-resultgroup-tagname',groupcontainer).click(function(e){
                    e.preventDefault();
                    groupcontainer.toggleClass('seocopy-query-suggestion-resultgroup-tagwrap-closed');
                });
                if(firstiterated){
                    groupcontainer.addClass('seocopy-query-suggestion-resultgroup-tagwrap-closed');
                }
                keywords[group].forEach(function(key){
                    //console.log( key );
                    var itemcontainer = templateResultItem.clone();
                    $('.seocopy-query-suggestion-resultitem-name',itemcontainer).text(key.suggest);
                    if(!key['_tagnames']){
                        key['_tagnames'] = {};
                    }
                    key['_tagnames'][group.toUpperCase()] = itemcontainer;
                    key['_id'] = keyid++;
                    $('.seocopy-query-suggestion-results-group-container',groupcontainer).append(itemcontainer);
                });
                $('#seocopy-query-suggestion-results-container').append(groupcontainer);
                firstiterated = true;
            });
            // this.computeResultsCounters();
            // this.fillDensityNoResults();
            console.log( 'end ');
            this.forceTriggerAnalysis();
        },
        resetSearch:function(){
            console.log( 'resetSearch' );
            this.data.currentSearchKeywords = null;
            clearTimeout(this.data.debouncingTimeout);
            $('#seocopy-keyword-searching-div').hide();
            $('#seocopy-keyword-error-div').hide();
            $('#seocopy-keyword-results-div').hide();
            $('#seocopy-keyword-results-reset-confirm').hide();
            $('#seocopy-keyword-results-reset-button').show();
            $('#seocopy-keyword-search-div').show();
        },
        // New api, checkSearchSubmission is useless
        /*checkSearchSubmission: function () {
            console.log( 'checkSearchSubmission' );
            var that = this;
            this.api.retrieveResource(this.data.currentSearchUuid, function (data) {
                data = JSON.parse(data);
                $('#seocopy-keyword-error-div').hide();
                if (data && data.report && data.report.LSIKeywords && (
                    data.report.LSIKeywords.status === 'ERROR' ||
                    data.report.LSIKeywords.status === 'SUCCESS'
                )
                ) {
                    // finished
                    // alert('finished' + JSON.stringify(data.report));
                    if(data.report.LSIKeywords.status === 'ERROR'){
                        $('#seocopy-keyword-searching-div').hide();
                        $('#seocopy-keyword-error-div').show();
                        $('#seocopy-keyword-error-div > p').hide();
                        $('#seocopy-keyword-error-unable-connect').show();
                        $('#seocopy-keyword-search-div').show();
                    }else {
                        // Filter nulls to prevent errors
                        that.data.currentSearchKeywords = data.LSIKeywords.filter(function(x){
                            return x !== null;
                        });
                        // Add density manually
                        that.data.currentSearchKeywords = that.data.currentSearchKeywords.map(function(x){
                            x.dens = Math.ceil(x.count * 0.42);
                            return x;
                        });

                        that.printFoundKeywords(that.data.currentSearchKeywords);
                    }
                } else {
                    setTimeout(that.checkSearchSubmission.bind(that), 2000);
                }

            }, function () {
                $('#seocopy-keyword-error-div').show();
                $('#seocopy-keyword-error-div > p').hide();
                $('#seocopy-keyword-error-unable-connect').show();
            }, function () {

            });
        },*/
        checkQuerySuggestionSubmission: function(){
            console.log( 'checkQuerySuggestionSubmission' );
            var that = this;
            this.api.retrieveResource(this.data.currentQuerySuggestionUuid, function (data) {
                data = JSON.parse(data);
                $('#seocopy-query-suggestion-error-div').hide();
                if (data && data.querySuggestion ) {
                    for( const key  in data.querySuggestion ) {
                        // Filter nulls to prevent errors
                        const querySuggestion = data.querySuggestion[ key ];
                        // TODO - Da capire questa cosa
                        that.data.currentQuerySuggestionKeywords[ key ] = querySuggestion.filter(function(x){
                            return x !== null;
                        });
                        // Add density manually
                        that.data.currentQuerySuggestionKeywords[ key ] = that.data.currentQuerySuggestionKeywords[ key ].map(function(x){
                            x.dens = Math.ceil(x.count * 0.42);
                            return x;
                        });
                    }
                    that.printFoundQuerySuggestionKeywords(that.data.currentQuerySuggestionKeywords);
                } else {
                    setTimeout(that.checkQuerySuggestionSubmission.bind(that), 2000);
                }

            }, function () {
                $('#seocopy-keyword-error-div').show();
                $('#seocopy-keyword-error-div > p').hide();
                $('#seocopy-keyword-error-unable-connect').show();
            }, function () {

            });
        },
        changeLoadingSuggestions:function(first){
            console.log( 'changeLoadingSuggestions' );
            var that = this;
            var n_elements = $('#seocopy-keyword-searching-random-texts-div > span').length;
            //var random = Math.floor(Math.random()*n_elements);
            var random = 0;
            if(!first){
                random = ($('#seocopy-keyword-searching-random-texts-div > span.seocopy-keyword-searching-random-texts-cur').index() + 1) % n_elements;
            }
            $('#seocopy-keyword-searching-random-texts-div > span em').text(this.data.currentSearchedKeyword);
            $('#seocopy-keyword-searching-random-texts-div > span')
                .removeClass('seocopy-keyword-searching-random-texts-cur')
                .eq(random)
                .addClass('seocopy-keyword-searching-random-texts-cur');
            if($('#seocopy-keyword-searching-div:visible').length){
                setTimeout(that.changeLoadingSuggestions.bind(that), 4500);
            }
        },
        submitSearch: function () {
            console.log( 'submitSearch' );
            var that = this;
            const api_key = $('#seocopy-keyword-submit').data('api-key');
            var keyword = $('#seocopy-keyword-input').val();
            this.data.currentSearchedKeyword = keyword;
            var language = $('#seocopy_language-input').val();
            $('#seocopy-keyword-error-div').hide();
            if(!seocopySettings.key){
                $('#seocopy-keyword-error-div').show();
                $('#seocopy-keyword-error-div > p').hide();
                $('#seocopy-keyword-error-no-key').show();
            } else {
                $('#seocopy-keyword-search-div').hide();
                $('#seocopy-keyword-searching-div').show();
                that.changeLoadingSuggestions(true);

                this.api.createResourceRequest('LSIKeywords', language, keyword, {
                    'LSIKeywords.advice':true,
                    'api-key': api_key
                },function ( data ) {
                    //that.data.currentSearchUuid = token;
                    //setTimeout(that.checkSearchSubmission.bind(that), 2000);
                    // Filter nulls to prevent errors
                    that.data.currentSearchKeywords = data.LSIKeywords.filter(function(x){
                        return x !== null;
                    });
                    // Add density manually
                    that.data.currentSearchKeywords = that.data.currentSearchKeywords.map(function(x){
                        x.dens = Math.ceil(x.count * 0.42);
                        return x;
                    });

                    that.printFoundKeywords(that.data.currentSearchKeywords);
                }, function (e) {
                    $('#seocopy-keyword-search-div').show();
                    $('#seocopy-keyword-searching-div').hide();
                    $('#seocopy-keyword-error-div').show();
                    $('#seocopy-keyword-error-div > p').hide();
                    if (e && e.message && e.message==='Payment Required') {
                        // TODO MANAGE RIGHT ERROR MESSAGE
                        $('#seocopy-keyword-error-no-balance').show();
                    } else if(e && e.status && e.status == -1) {
                        $('#seocopy-keyword-error-no-key').show();
                    } else if(e && e.status && e.status == -2) {
                        $('#seocopy-keyword-error-wrong-key').show();
                    } else if(e && e.status && e.status == -3 ){
                        $('#seocopy-keyword-error-unable-connect').html( e.message ).show();
                    } else {
                        $('#seocopy-keyword-error-unable-connect').show();
                    }
                }, function () {

                });
            }
        },
        changeLoadingQuerySuggestion:function(first){
            console.log( 'changeLoadingQuerySuggestion' );
            var that = this;
            var n_elements = $('#seocopy-query-suggestion-searching-random-texts-div > span').length;
            var random = 0;
            if(!first){
                random = ($('#seocopy-query-suggestion-searching-random-texts-div > span.seocopy-keyword-searching-random-texts-cur').index() + 1) % n_elements;
            }
            $('#seocopy-query-suggestion-searching-random-texts-div > span em').text(this.data.currentSearchedKeyword);
            $('#seocopy-query-suggestion-searching-random-texts-div > span')
                .removeClass('seocopy-keyword-searching-random-texts-cur')
                .eq(random)
                .addClass('seocopy-keyword-searching-random-texts-cur');
            if($('#seocopy-query-suggestion-searching-div:visible').length){
                setTimeout(that.changeLoadingQuerySuggestion.bind(that), 4500);
            }
        },
        submitQuerySuggestion: function(){
            console.log( 'submitQuerySuggestion' );
            var that = this;
            const api_key = $('#seocopy-query-suggestion-submit').data('api-key');
            var keyword = $('#seocopy-query-suggestion-input').val();
            this.data.currentSearchedKeyword = keyword;
            var language = $('#seocopy_query-suggestion-language-input').val();
            $('#seocopy-query-suggestion-error-div').hide();
            if(!seocopySettings.key){
                $('#seocopy-query-suggestion-error-div').show();
                $('#seocopy-query-suggestion-error-div > p').hide();
                $('#seocopy-query-suggestion-error-no-key').show();
            } else {
                $('#seocopy-query-suggestion-div').hide();
                $('#seocopy-query-suggestion-searching-div').show();

                // TODO - Vedere cosa fa
                that.changeLoadingQuerySuggestion(true);

                this.api.createResourceRequest('Suggestqueries', language, keyword, {
                    'querySuggestion.renew':true,
                    'api-key': api_key
                },
                function ( data ) {
                    // Suggestions
                    //that.data.currentQuerySuggestionUuid = response.token;
                    //that.checkQuerySuggestionSubmission();
                    //setTimeout(that.checkQuerySuggestionSubmission().bind(that), 2000);

                    for( const key  in data.querySuggestion ) {
                        // Filter nulls to prevent errors
                        const querySuggestion = data.querySuggestion[ key ];
                        // TODO - Da capire questa cosa
                        that.data.currentQuerySuggestionKeywords[ key ] = querySuggestion.filter(function(x){
                            return x !== null;
                        });
                        // Add density manually
                        that.data.currentQuerySuggestionKeywords[ key ] = that.data.currentQuerySuggestionKeywords[ key ].map(function(x){
                            x.dens = Math.ceil(x.count * 0.42);
                            return x;
                        });
                    }
                    that.printFoundQuerySuggestionKeywords(that.data.currentQuerySuggestionKeywords);

                }, function (e) {
                    $('#seocopy-query-suggestion-div').show();
                    $('#seocopy-query-suggestion-searching-div').hide();
                    $('#seocopy-query-suggestion-error-div').show();
                    $('#seocopy-query-suggestion-error-div > p').hide();
                    if (e && e.message && e.message==='Payment Required') {
                        // TODO MANAGE RIGHT ERROR MESSAGE
                        $('#seocopy-query-suggestion-error-no-balance').show();
                    } else if(e && e.status && e.status == -1) {
                        $('#seocopy-query-suggestion-error-no-key').show();
                    } else if(e && e.status && e.status == -2) {
                        $('#seocopy-query-suggestion-error-wrong-key').show();
                    } else if(e && e.status && e.status == -3) {
                        $('#seocopy-query-suggestion-error-unable-connect').html( e.message ).show();
                    } else {
                        $('#seocopy-query-suggestion-error-unable-connect').show();
                    }
                }, function () {

                });
            }
        },
        resetQuerySuggestion: function(){
            console.log( 'resetQuerySuggestion' );
            this.data.currentQuerySuggestionKeywords = [];
            clearTimeout(this.data.debouncingTimeout);
            $('#seocopy-query-suggestion-searching-div').hide();
            $('#seocopy-query-suggestion-error-div').hide();
            $('#seocopy-query-suggestion-results-div').hide();
            $('#seocopy-query-suggestion-results-reset-confirm').hide();
            $('#seocopy-query-suggestion-results-reset-button').show();
            $('#seocopy-query-suggestion-div').show();
        },
        onLoad: function () {
            console.log( 'onLoad' );
            var that = this;
            $('#seocopy-keyword-submit').click(function (e) {
                e.preventDefault();
                that.submitSearch();
                return false;
            });
            $('#seocopy-keyword-input').on('keypress', function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    that.submitSearch();
                    return false;
                }
            });

            $('#seocopy-query-suggestion-submit').click(function(e){
                e.preventDefault();
                that.submitQuerySuggestion();
                return false;
            });
            $('#seocopy-query-suggestion-input').on('keypress', function(e){
                if(e.which === 13) {
                    e.preventDefault();
                    that.submitQuerySuggestion();
                    return false;
                }
            });

            $('#seocopy-keyword-results-reset-button').click(function(e){
                e.preventDefault();
                $(this).hide();
                $('#seocopy-keyword-results-reset-confirm').show();
            });
            $('#seocopy-keyword-results-reset-button-cancel').click(function(e){
                e.preventDefault();
                $('#seocopy-keyword-results-reset-confirm').hide();
                $('#seocopy-keyword-results-reset-button').show();
            });
            $('#seocopy-keyword-results-reset-button-confirm').click(function(e){
                e.preventDefault();
                that.resetSearch();
            });

            $('#seocopy-query-suggestion-results-reset-button').click(function(e){
                e.preventDefault();
                $(this).hide();
                $('#seocopy-query-suggestion-results-reset-confirm').show();
            });
            $('#seocopy-query-suggestion-results-reset-button-cancel').click(function(e){
                e.preventDefault();
                $('#seocopy-query-suggestion-results-reset-confirm').hide();
                $('#seocopy-query-suggestion-results-reset-button').show();
            });
            $('#seocopy-query-suggestion-results-reset-button-confirm').click(function(e){
                e.preventDefault();
                that.resetQuerySuggestion();
            });

            // Classic editor
            var elems = ["excerpt", "content", "title"];
            for (var i = 0; i < elems.length; i++) {
                var elem = document.getElementById(elems[i]);
                if (elem !== null) {
                    var doc = document.getElementById(elems[i]);
                    (function () {
                        var elemname = elems[i];
                        doc.addEventListener("input", function (event) {
                            that.analyzeText(elemname, function () {
                                // return event.target.value;
                                return '<title>'+document.getElementById('title').value+'</title>' +
                                    seocopy_tinyMce.getContent();
                            }, event);
                        });
                    }()); // immediate invocation
                }
            }

            seocopy_tinyMce.tinyMceEventBinder(function (event, getcontent) {
                that.analyzeText('classic', function(){
                    return '<title>'+document.getElementById('title').value+'</title>' +
                        seocopy_tinyMce.getContent();
                }, event);
            });

            // Gutenberg
            // wp.data.select("core/editor").getBlocks()
            // wp.data.select("core")
            // var originalContent = wp.data.select( "core/editor" ).getCurrentPost().content;
            // var editedContent = wp.data.select( "core/editor" ).getEditedPostContent();

            if(wp.data && wp.data.select( "core/editor" )){
                wp.data.subscribe( function() {
                    that.analyzeText('block', function(){
                        return '<title>'+wp.data.select( "core/editor" ).getEditedPostAttribute( 'title' )+'</title>' +
                            wp.data.select( "core/editor" ).getEditedPostContent();
                    }, null);
                } );
            }

        },
        /**
         * Force trigger current text analysis
         */
        forceTriggerAnalysis: function(){
            console.log('forceTriggerAnalysis');
            var that = this;
            if(wp.data && wp.data.select( "core/editor" )){
                that.analyzeText('block', function(){
                    return '<title>'+wp.data.select( "core/editor" ).getEditedPostAttribute( 'title' )+'</title>' +
                        wp.data.select( "core/editor" ).getEditedPostContent();
                }, null);
            }else{
                that.analyzeText('classic', function () {
                    // return event.target.value;
                    return '<title>'+document.getElementById('title').value+'</title>' +
                        seocopy_tinyMce.getContent();
                }, event);
            }
        },
        /**
         * Check written text
         */
        analyzeTextFinal: function (element, getcontent, event) {
            console.log( 'analyzeTextFinal' );
            var that = this;
            if(!this.data.currentSearchKeywords && !this.data.currentQuerySuggestionKeywords){
                return;
            }
            var content = getcontent();
            // console.log('Analyze text', element, content, event);
            // var domcontent;
            // if(element === 'title'){
            //     domcontent = $('<title>'+content+'</title>').get(0);
            // }else{
            //     domcontent = $('<div>'+content+'</div>').get(0);
            // }
            var domcontent = $('<div>'+content+'</div>').get(0);
            var results = {};
            var querySuggestionResults = {};

            /**
             * Keyword Research
             */
            if( this.data.currentSearchKeywords != null ) {
                this.data.currentSearchKeywords.forEach(function(keyword){
                    if(!keyword._id){ // Keyword not used in any paragraph
                        return;
                    }
                    // var results = testing.findString(domcontent, 'hello');
                    if(keyword.keyword){
                        var res = that.textutils.findString(domcontent, keyword.keyword);
                        if (res.length) {
                            // console.log(res, keyword.keyword, keyword._id);
                            if(!results[keyword._id]){
                                results[keyword._id] = {
                                    resource: keyword,
                                    results:{}
                                };
                            }
                            res.forEach(function(tag){
                                if(!results[keyword._id].results[tag.tagName]){
                                    results[keyword._id].results[tag.tagName] = 0;
                                }
                                results[keyword._id].results[tag.tagName]++;
                            });
                        }
                    }
                });
                $('.seocopy-keyword-results-group-container .seocopy-keyword-resultitem-founddata').remove();
                $('.seocopy-keyword-results-group-container .seocopy-keyword-resultitem-denscustom').remove();
                $('.seocopy-keyword-results-group-container .seocopy-keyword-resultitem-found').removeClass('seocopy-keyword-resultitem-found');
                Object.keys(results).forEach(function(keywordid){
                    var hasOneSameTag = false;
                    Object.keys(results[keywordid].resource._tagnames).map(function(tagname){
                        var isSameTag = tagname in results[keywordid].results;
                        if(isSameTag){
                            hasOneSameTag = true;
                        }
                    });
                    Object.keys(results[keywordid].resource._tagnames).map(function(tagname){
                        var isSameTag = tagname in results[keywordid].results;
                        var text = isSameTag?'':seocopySettings.language.wrong_tag;
                        if(isSameTag || !hasOneSameTag) {
                            var completedLength = true;
                            if(!$('.seocopy-keyword-resultitem-dens',results[keywordid].resource._tagnames[tagname]).length){
                                $('.seocopy-keyword-resultitem-name',results[keywordid].resource._tagnames[tagname]).after($('<div>').addClass('seocopy-keyword-resultitem-dens'));
                            }
                            if(tagname === 'P') {
                                var densCurrent = (tagname in results[keywordid].results ? results[keywordid].results[tagname] : 0);
                                var densTotal = results[keywordid].resource.dens;
                                $('.seocopy-keyword-resultitem-dens', results[keywordid].resource._tagnames[tagname])
                                    .addClass('seocopy-keyword-resultitem-denscustom')
                                    .text(densCurrent + '/' + densTotal);
                                if(densCurrent < densTotal){
                                    completedLength = false;
                                }
                            }
                            $(results[keywordid].resource._tagnames[tagname]).addClass('seocopy-keyword-resultitem-found').append(
                                $('<div>').addClass('seocopy-keyword-resultitem-founddata')
                                    .addClass('seocopy-keyword-resultitem-founddata-' + (isSameTag ? 'sametag' : 'wrongtag'))
                                    .addClass('seocopy-keyword-resultitem-founddata-' + (completedLength ? 'completedlength' : 'notcompletedlength'))
                                    .text(text)
                            );
                        }
                    });
                });
                this.data.currentAnalyzeResults = results;
                this.computeResultsCounters();
                this.fillDensityNoResults();
            }

            /**
             * People also ask
             */
            if( this.data.currentQuerySuggestionKeywords != null ) {
                for( let k in this.data.currentQuerySuggestionKeywords ) {
                    let keywords = this.data.currentQuerySuggestionKeywords[k];
                    for( let i in keywords ) {
                        let keyword = keywords[i];
                        if (!keyword._id) { // Keyword not used in any paragraph
                            continue;
                        }
                        // var results = testing.findString(domcontent, 'hello');
                        if (keyword.suggest) {
                            var res = that.textutils.findString(domcontent, keyword.suggest);
                            if (res.length) {
                                // console.log(res, keyword.keyword, keyword._id);
                                if (!querySuggestionResults[keyword._id]) {
                                    querySuggestionResults[keyword._id] = {
                                        resource: keyword,
                                        results: {}
                                    };
                                }
                                res.forEach(function (tag) {
                                    if (!querySuggestionResults[keyword._id].results[tag.tagName]) {
                                        querySuggestionResults[keyword._id].results[tag.tagName] = 0;
                                    }
                                    querySuggestionResults[keyword._id].results[tag.tagName]++;
                                });
                            }
                        }
                    }
                }
                $('.seocopy-query-suggestion-results-group-container .seocopy-query-suggestion-resultitem-founddata').remove();
                $('.seocopy-query-suggestion-results-group-container .seocopy-query-suggestion-resultitem-denscustom').remove();
                $('.seocopy-query-suggestion-results-group-container .seocopy-query-suggestion-resultitem-found').removeClass('seocopy-query-suggestion-resultitem-found');
                Object.keys(querySuggestionResults).forEach(function(keywordid){
                    var hasOneSameTag = false;
                    Object.keys(querySuggestionResults[keywordid].resource._tagnames).map(function(tagname){
                        var isSameTag = tagname in querySuggestionResults[keywordid].results;
                        if(isSameTag){
                            hasOneSameTag = true;
                        }
                    });
                    Object.keys(querySuggestionResults[keywordid].resource._tagnames).map(function(tagname){
                        //var isSameTag = tagname in querySuggestionResults[keywordid].results;
                        var isSameTag = true;
                        var text = isSameTag?'':seocopySettings.language.wrong_tag;
                        if(isSameTag || !hasOneSameTag) {
                            var completedLength = true;
                            if(!$('.seocopy-query-suggestion-resultitem-dens',querySuggestionResults[keywordid].resource._tagnames[tagname]).length){
                                $('.seocopy-query-suggestion-resultitem-name',querySuggestionResults[keywordid].resource._tagnames[tagname]).after($('<div>').addClass('seocopy-query-suggestion-resultitem-dens'));
                            }
                            if(tagname === 'P') {
                                var densCurrent = (tagname in querySuggestionResults[keywordid].results ? querySuggestionResults[keywordid].results[tagname] : 0);
                                var densTotal = querySuggestionResults[keywordid].resource.dens;
                                $('.seocopy-query-suggestion-resultitem-dens', querySuggestionResults[keywordid].resource._tagnames[tagname])
                                    .addClass('seocopy-query-suggestion-resultitem-denscustom')
                                    .text(densCurrent + '/' + densTotal);
                                if(densCurrent < densTotal){
                                    completedLength = false;
                                }
                            }
                            $(querySuggestionResults[keywordid].resource._tagnames[tagname]).addClass('seocopy-query-suggestion-resultitem-found').append(
                                $('<div>').addClass('seocopy-query-suggestion-resultitem-founddata')
                                    .addClass('seocopy-query-suggestion-resultitem-founddata-' + (isSameTag ? 'sametag' : 'wrongtag'))
                                    .addClass('seocopy-query-suggestion-resultitem-founddata-' + (completedLength ? 'completedlength' : 'notcompletedlength'))
                                    .text(text)
                            );
                        }
                    });
                });
                this.data.currentQuerySuggestionAnalyzeResults = querySuggestionResults;
                this.computeQuerySuggestionResultsCounters();
                this.fillDensityNoQuerySuggestionResults();
            }
        },
        computeResultsCounters: function(){
            console.log( 'computeResultsCounters' );
            $('.seocopy-keyword-resultgroup-tagwrap').each(function(){
               $('.seocopy-keyword-resultgroup-counter',this).text(
                    $('.seocopy-keyword-resultitem-founddata-sametag',this).length + '/' + $('ul.seocopy-keyword-results-group-container li',this).length
               );
            });
        },
        computeQuerySuggestionResultsCounters: function(){
            console.log( 'computeQuerySuggestionResultsCounters' );
            $('.seocopy-query-suggestion-resultgroup-tagwrap').each(function(){
                $('.seocopy-query-suggestion-resultgroup-counter',this).text(
                    $('.seocopy-query-suggestion-resultitem-founddata-sametag',this).length + '/' + $('ul.seocopy-query-suggestion-results-group-container li',this).length
                );
            });
        },
        /**
         * Fill density for those keywords in p without results
         */
        fillDensityNoResults: function(){
            console.log( 'fillDensityNoResults' );
            this.data.currentSearchKeywords.forEach(function(keyword){
               if(keyword._tagnames && 'P' in keyword._tagnames){
                   var node = keyword._tagnames['P'];
                   if(!$('.seocopy-keyword-resultitem-dens',node).length){
                       $('.seocopy-keyword-resultitem-name',node).after($('<div>').addClass('seocopy-keyword-resultitem-dens')
                           .text('0'+'/'+keyword.dens));
                   }
               }
            });
        },
        fillDensityNoQuerySuggestionResults: function(){
            console.log( 'fillDensityNoQuerySuggestionResults' );
            this.data.currentQuerySuggestionKeywords.forEach(function(keyword){
                if(keyword._tagnames && 'P' in keyword._tagnames){
                    var node = keyword._tagnames['P'];
                    if(!$('.seocopy-query-suggestion-resultitem-dens',node).length){
                        $('.seocopy-query-suggestion-resultitem-name',node).after($('<div>').addClass('seocopy-query-suggestion-resultitem-dens')
                            .text('0'+'/'+keyword.dens));
                    }
                }
            });
        },
        analyzeTextDebounce: function () {
            console.log('analyzeTextDebounce');
            var that = this;
            var element = this.data.debouncingAnalyzeData[0];
            var getcontent = this.data.debouncingAnalyzeData[1];
            var event = this.data.debouncingAnalyzeData[2];
            this.data.debouncingTimeout = null;
            this.analyzeTextFinal(element, getcontent, event);
            if(this.data.debouncingAnalyzeChanged){
                this.data.debouncingAnalyzeChanged = false;
                this.data.debouncingTimeout = setTimeout(that.analyzeTextDebounce.bind(that), 1000);
            }
        },
        analyzeText: function (element, getcontent, event) {
            console.log('analyzeText');
            var that = this;
            if(this.data.debouncingTimeout){
                this.data.debouncingAnalyzeChanged = true;
            }else{
                this.data.debouncingTimeout = setTimeout(that.analyzeTextDebounce.bind(that), 1000);
            }
            this.data.debouncingAnalyzeData = [element, getcontent, event];
        },
        textutils: {

            escapeRegExp: function (s) {
                return String(s).replace(/([.*+?^=!:${}()|[\]\/\\])/g, '\\$1');
            },
            findString: function (node, string) {
                var parents = [];
                seocopy_findAndReplaceDOMText(node, {
                    preset: 'prose',
                    find: RegExp('\\b'+this.escapeRegExp(string)+'\\b', 'giu'),
                    replace: function (portion, match) {
                        if(portion.indexInNode >= 0) { // TODO CHECK IF NEEDED
                            parents.push(portion.node.parentElement);
                        }
                        return match;
                        // callback(portion,match);
                        // return '[[' + portion.index + ']]';
                    }
                });
                return parents;
            }

        }

    };


    $(window).ready(function () {
        window.seocopy = seocopy;
        seocopy.onLoad();

        // Enable for testing
        // $('#seocopy-keyword-searching-div').show();
        // $('#seocopy-keyword-results-div').show();
        // $('#seocopy-keyword-error-div').show();
        // seocopy.data.currentSearchKeywords =JSON.parse('');
        // seocopy.data.currentSearchKeywords = seocopy.data.currentSearchKeywords.map(function(x){
        //     x.dens = Math.ceil(x.count * 0.42);
        //     return x;
        // });
        // seocopy.printFoundKeywords(seocopy.data.currentSearchKeywords);
    });

})(jQuery);
