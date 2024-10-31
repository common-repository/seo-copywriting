/**
 * Classic editor helper
 */
(function (root, factory) {
    if (typeof module === 'object' && module.exports) {
        // Node/CommonJS
        module.exports = factory();
    } else if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(factory);
    } else {
        // Browser globals
        root.seocopy_tinyMce = factory();
    }
}(this, function factory() {
    var tmceId = "content";
    return {
        addEventHandler: function (editorId, events, callback) {
            if (typeof tinyMCE === "undefined" || typeof tinyMCE.on !== "function") {
                return;
            }
            var editor = tinymce.get('content');
            if (editor) {
                events.forEach(function (eventName) {
                    editor.on(eventName, function (event) {
                        callback(event, function () {
                            return editor.getContent()
                        });
                    });
                });
            } else {

                tinyMCE.on("addEditor", function (evt) {
                    var editor = evt.editor;

                    if (editor.id !== editorId) {
                        return;
                    }

                    events.forEach(function (eventName) {
                        editor.on(eventName, function (event) {
                            callback(event, function () {
                                return editor.getContent()
                            });
                        });
                    });
                });
            }
        },
        tinyMceEventBinder: function (refreshAnalysis) {
            this.addEventHandler(tmceId, ["input", "change", "cut", "paste"], refreshAnalysis);
        },
        getContent: function(){
            if(tinymce && tinymce.editors && tinymce.editors.content) {
                return tinymce.editors.content.getContent()
            }
            return '';
        }
    };
}));
