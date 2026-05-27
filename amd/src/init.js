define(['jquery', 'core/ajax', 'core/notification', 'core/modal_factory', 'core/modal_events', 'core/url'],
    function ($, Ajax, Notification, ModalFactory, ModalEvents, Url) {
        var files = {};
        var activeFile = 'sketch.js';
        var cmid = null;
        var iframeId = '';
        var p5jsUrl = M.cfg.wwwroot + '/mod/p5js/amd/src/p5';

        return {
            init: function (targetIframeId, initialCodeJson, moduleid) {
                var self = this;
                this.iframeId = targetIframeId;
                this.cmid = moduleid;

                try {
                    files = JSON.parse(initialCodeJson);
                    if (!files || typeof files !== 'object') {
                        files = { 'sketch.js': initialCodeJson };
                    }
                } catch (e) {
                    files = { 'sketch.js': initialCodeJson };
                }

                // Ensure our 3 standard files exist if it's a new sketch
                if (!files['index.html']) {
                    files['index.html'] = '<!DOCTYPE html>\n<html>\n  <head>\n    <script src="p5.js"></script>\n    <link rel="stylesheet" type="text/css" href="style.css">\n    <meta charset="utf-8" />\n  </head>\n  <body>\n    <script src="sketch.js"></script>\n  </body>\n</html>';
                }
                if (!files['style.css']) {
                    files['style.css'] = 'html, body {\n  margin: 0;\n  padding: 0;\n}\ncanvas {\n  display: block;\n}';
                }

                this.renderFileList();
                this.updateEditor();
                this.runSketch();

                // Event Listeners
                $('#p5js_run').on('click', function () {
                    self.saveActiveFileData();
                    self.runSketch();
                });

                $('#p5js_stop').on('click', function () {
                    self.stopSketch();
                });

                $('#p5js_save').on('click', function () {
                    self.saveActiveFileData();
                    self.saveToMoodle();
                });

                $('#p5js_add_file').on('click', function () {
                    self.showAddFileDialog();
                });

                $(document).on('click', '.p5js-file-item', function (e) {
                    if ($(e.target).closest('.p5js-file-delete').length) return;
                    self.switchFile($(this).data('filename'));
                });

                $(document).on('click', '.p5js-file-delete', function () {
                    self.deleteFile($(this).parent().data('filename'));
                });

                // Sidebar Toggle Logic
                $('#p5js_toggle_sidebar, #p5js_expand_sidebar').on('click', function () {
                    $('#p5js_sidebar').toggleClass('sidebar-collapsed');
                    $('#p5js_expand_sidebar').toggleClass('d-none');
                });

                // Auto-refresh logic
                var timeout = null;
                $('#p5js_editor').on('input', function () {
                    if ($('#p5js_auto_refresh').is(':checked')) {
                        clearTimeout(timeout);
                        timeout = setTimeout(function () {
                            self.saveActiveFileData();
                            self.runSketch();
                        }, 1000);
                    }
                });
            },

            renderFileList: function () {
                var $list = $('#p5js_file_list');
                $list.empty();

                Object.keys(files).sort().forEach(function (filename) {
                    var isActive = (filename === activeFile) ? 'active' : '';
                    var isEssential = (filename === 'sketch.js' || filename === 'index.html' || filename === 'style.css');

                    var icon = 'fa-file-code-o';
                    if (filename.endsWith('.html')) icon = 'fa-html5';
                    if (filename.endsWith('.css')) icon = 'fa-css3';

                    var html = '<div class="p5js-file-item list-group-item list-group-item-action ' + isActive + '" data-filename="' + filename + '">' +
                        '<i class="fa ' + icon + ' mr-2 text-muted"></i>' +
                        '<span>' + filename + '</span>' +
                        (!isEssential ? ' <i class="fa fa-trash p5js-file-delete"></i>' : '') +
                        '</div>';
                    $list.append(html);
                });
            },

            updateEditor: function () {
                $('#p5js_active_filename').text(activeFile);
                $('#p5js_editor').val(files[activeFile]);
            },

            switchFile: function (filename) {
                this.saveActiveFileData();
                activeFile = filename;
                this.renderFileList();
                this.updateEditor();
            },

            saveActiveFileData: function () {
                files[activeFile] = $('#p5js_editor').val();
            },

            showAddFileDialog: function () {
                var self = this;
                ModalFactory.create({
                    type: ModalFactory.types.SAVE_CANCEL,
                    title: 'New File',
                    body: '<p class="small text-muted">Enter filename with extension (e.g. helper.js, data.json)</p>' +
                        '<input type="text" class="form-control p5js_new_filename_input" placeholder="filename.js">'
                }).done(function (modal) {
                    modal.setSaveButtonText('Create File');
                    modal.getRoot().on(ModalEvents.save, function () {
                        var newName = modal.getRoot().find('.p5js_new_filename_input').val().trim();
                        if (newName && !files[newName]) {
                            files[newName] = '';
                            activeFile = newName;
                            self.renderFileList();
                            self.updateEditor();
                        }
                    });
                    modal.getRoot().on(ModalEvents.hidden, function () {
                        modal.destroy();
                    });
                    modal.show();
                });
            },

            deleteFile: function (filename) {
                if (confirm('Are you sure you want to delete "' + filename + '"?')) {
                    delete files[filename];
                    if (activeFile === filename) activeFile = 'sketch.js';
                    this.renderFileList();
                    this.updateEditor();
                    if ($('#p5js_auto_refresh').is(':checked')) this.runSketch();
                }
            },

            runSketch: function () {
                var self = this;
                var $iframe = $('#' + this.iframeId);

                // 1. Prepare base HTML from user files
                var html = files['index.html'] || '';
                var doc = new DOMParser().parseFromString(html, 'text/html');
                if (!doc.head) doc.documentElement.appendChild(doc.createElement('head'));
                if (!doc.body) doc.documentElement.appendChild(doc.createElement('body'));

                // 2. Generate ObjectURLs for ALL files
                var fileUrls = {};
                Object.keys(files).forEach(function (name) {
                    var mime = 'text/plain';
                    if (name.endsWith('.html')) mime = 'text/html';
                    else if (name.endsWith('.css')) mime = 'text/css';
                    else if (name.endsWith('.js')) mime = 'application/javascript';
                    else if (name.endsWith('.json')) mime = 'application/json';
                    else if (name.endsWith('.csv')) mime = 'text/csv';
                    else if (name.endsWith('.xml')) mime = 'application/xml';
                    else if (name.endsWith('.svg')) mime = 'image/svg+xml';
                    var blob = new Blob([files[name]], { type: mime });
                    fileUrls[name] = URL.createObjectURL(blob);
                });

                // 3. Inject p5 file mappings into head
                var fileMapScript = doc.createElement('script');
                fileMapScript.textContent = `
                    window.__p5_file_urls = ${JSON.stringify(fileUrls)};
                    
                    var origFetch = window.fetch;
                    window.fetch = function(resource, init) {
                        var url = typeof resource === 'string' ? resource : (resource instanceof Request ? resource.url : '');
                        if (url && window.__p5_file_urls[url]) {
                            if (typeof resource === 'string') {
                                resource = window.__p5_file_urls[url];
                            } else if (resource instanceof Request) {
                                resource = new Request(window.__p5_file_urls[url], init || resource);
                            }
                        }
                        return origFetch.call(this, resource, init);
                    };

                    var origOpen = XMLHttpRequest.prototype.open;
                    XMLHttpRequest.prototype.open = function(method, url, async, user, password) {
                        if (window.__p5_file_urls[url]) {
                            url = window.__p5_file_urls[url];
                        }
                        return origOpen.call(this, method, url, async, user, password);
                    };

                    window.addEventListener('DOMContentLoaded', function() {
                        if (window.p5) {
                            var methods = ['loadImage', 'loadFont', 'loadModel', 'loadShader', 'loadJSON', 'loadStrings', 'loadTable', 'loadXML', 'loadBytes'];
                            methods.forEach(function(m) {
                                if (p5.prototype[m]) {
                                    var orig = p5.prototype[m];
                                    p5.prototype[m] = function(path, ...args) {
                                        if (typeof path === 'string' && window.__p5_file_urls[path]) {
                                            path = window.__p5_file_urls[path];
                                        }
                                        return orig.call(this, path, ...args);
                                    };
                                }
                            });
                        }
                    });
                `;
                doc.head.insertBefore(fileMapScript, doc.head.firstChild);

                var referencedFiles = new Set();
                var hasP5js = false;

                // 4. Update <script>, <link> and <img> tags in the HTML
                var scripts = doc.querySelectorAll('script');
                scripts.forEach(function (script) {
                    var src = script.getAttribute('src');
                    if (src) {
                        if (src === 'p5.js' || src === 'p5.min.js' || src.includes('p5.js')) {
                            script.src = p5jsUrl + '.js';
                            script.removeAttribute('integrity');
                            script.removeAttribute('crossorigin');
                            hasP5js = true;
                        } else if (fileUrls[src]) {
                            script.src = fileUrls[src];
                            referencedFiles.add(src);
                        }
                    }
                });

                var links = doc.querySelectorAll('link[rel="stylesheet"]');
                links.forEach(function (link) {
                    var href = link.getAttribute('href');
                    if (href && fileUrls[href]) {
                        link.href = fileUrls[href];
                        referencedFiles.add(href);
                    }
                });

                var imgs = doc.querySelectorAll('img');
                imgs.forEach(function (img) {
                    var src = img.getAttribute('src');
                    if (src && fileUrls[src]) {
                        img.src = fileUrls[src];
                        referencedFiles.add(src);
                    }
                });

                // Ensure p5.js is loaded if not explicitly in the HTML
                if (!hasP5js) {
                    var p5Script = doc.createElement('script');
                    p5Script.src = p5jsUrl + '.js';
                    doc.head.appendChild(p5Script);
                }

                // 5. Inject unreferenced css and js
                Object.keys(files).forEach(function (name) {
                    if (!referencedFiles.has(name) && name !== 'index.html') {
                        if (name.endsWith('.css')) {
                            var style = doc.createElement('link');
                            style.rel = 'stylesheet';
                            style.href = fileUrls[name];
                            doc.head.appendChild(style);
                        } else if (name.endsWith('.js') && name !== 'p5.js' && name !== 'p5.min.js') {
                            var script = doc.createElement('script');
                            script.src = fileUrls[name];
                            doc.body.appendChild(script);
                        }
                    }
                });

                // Bridge
                var bridge = doc.createElement('script');
                bridge.textContent = "window.p = window;";
                doc.head.insertBefore(bridge, doc.head.firstChild);

                // 6. Create a Blob URL to avoid same-origin/document.write errors
                var blob = new Blob([doc.documentElement.outerHTML], { type: 'text/html' });
                var url = URL.createObjectURL(blob);

                // 7. Update iframe src
                $iframe.attr('src', url);
            },

            stopSketch: function () {
                var $iframe = $('#' + this.iframeId);
                var iframeDoc = $iframe[0].contentDocument || $iframe[0].contentWindow.document;
                iframeDoc.open();
                iframeDoc.write('<html><body style="background:#fff;"></body></html>');
                iframeDoc.close();
            },

            saveToMoodle: function () {
                $('.alert-success .btn-close').click();
                var btn = $('#p5js_save');
                var originalText = btn.html();
                btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

                Ajax.call([{
                    methodname: 'mod_p5js_save_submission',
                    args: {
                        cmid: this.cmid,
                        jscode: JSON.stringify(files)
                    }
                }])[0].done(function (response) {
                    btn.prop('disabled', false).html(originalText);
                    if (response.status) {
                        Notification.addNotification({
                            message: 'Successfully saved all sketch files!',
                            type: 'success'
                        });
                    }
                }).fail(function (ex) {
                    btn.prop('disabled', false).html(originalText);
                    Notification.exception(ex);
                });
            }
        };
    });
