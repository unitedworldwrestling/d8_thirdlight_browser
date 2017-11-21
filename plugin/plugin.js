// Register the plugin within the editor. The first arg MUST match
// the button name given in ThirdLightButton.php else it'll silently fail.
/* globals CKEDITOR: false, Drupal: false */
CKEDITOR.plugins.add('thirdlight_browser', {

    // The plugin initialization logic goes inside this method.
    init: function(editor) {

        var thirdlightConfig = editor.config.thirdlightBrowser;
        if (thirdlightConfig === undefined || !thirdlightConfig.enabled) {
            return;
        }

        // check that the browser has been configured
        if (!thirdlightConfig.configured) {
            editor.ui.addButton('thirdlight_browser', {
                label : Drupal.t('Third Light Browser (disabled)'),
                icon : this.path + 'icons/thirdlight_browser_disabled.png',
                command : 'thirdlight_browser'
            });
            editor.addCommand('thirdlight_browser', {
                exec : function() {
                    if(thirdlightConfig.reason) {
                        alert(thirdlightConfig.reason);
                    } else {
                        alert('The Third Light Browser has not been configured yet');
                    }
                    return;
                }
            });
            return;
        }

        // Register the toolbar buttons.
        editor.ui.addButton('thirdlight_browser', {
            label : Drupal.t('Third Light Browser'),
            icon : this.path + 'icons/thirdlight_browser.png',
            command : 'thirdlight_browser'
        });

        // load in the required JS:
        loadJsFile(this.path + "IMS.IframeApp.js");
        loadJsFile(this.path + "IMS.IframeAppOverlay.js");

        // command to launch the browser
        editor.addCommand('thirdlight_browser', {
            exec : function() {

                var app = new IMS.IframeAppOverlay(thirdlightConfig.browserUrl, {
                    top:"80px",
                    right:"20px",
                    bottom:"20px",
                    left:"20px",
                    options: thirdlightConfig.options
                });

                // update the image
                app.on("cropChosen", function(cropDetails) {
                    var img = editor.document.createElement( 'img' );
                    img.setAttribute('src', cropDetails.urlDetails.url);
                    img.setAttribute('width', cropDetails.urlDetails.width);
                    img.setAttribute('height', cropDetails.urlDetails.height);

                    if(cropDetails.cropClass && thirdlightConfig.options.cropClasses) {
                        forEach(thirdlightConfig.options.cropClasses, function(curClass) {
                            if(curClass.key == cropDetails.cropClass) {
                                if(curClass.className) {
                                    img.setAttribute("class", curClass.className);
                                }
                                return false;
                            }
                        });
                    }
                    if(thirdlightConfig.titleMode && cropDetails.metadata) {
                        img.setAttribute("title", cropDetails.metadata[thirdlightConfig.titleMode] || "");
                        img.setAttribute("alt", cropDetails.metadata[thirdlightConfig.titleMode] || "");
                    }
                    editor.insertElement( img );
                });

            }
        });
    }
});

//
// A couple of utils:
//
function loadJsFile(filename){
    var scriptTag = document.createElement('script')
    scriptTag.setAttribute("type","text/javascript")
    scriptTag.setAttribute("src", filename)
    document.getElementsByTagName("head")[0].appendChild(scriptTag)
}
function forEach(arr, fn){
    for(var i = 0; i < arr.length; i++){
        var res = fn(arr[i], i);
        if(res === false) break;
    }
}
