// tOdO:Rename this file
$(document).ready(function () {

    //region Form Fields
    // Form Field CK Editor
    $('.form-element-ckeditor').each(function () {

        CKEDITOR.replace(this.id, {
            filebrowserBrowseUrl : '/elFinder/ckeditor'
        });
    });

    // Form Field Date
    $('.form-element-date').datepicker({
        'dateFormat':'yy-mm-dd'
    });

    // Form Field elFinder
    var elf = $('.form-element-elfinder').elfinder({
        // language (OPTIONAL)
        lang: 'ru',
        // connector URL (REQUIRED)
        url : '/elFinder/connector'  // connector URL (REQUIRED)
    }).elfinder('instance');

    //region Form Field uploader
    var $uploader = $('.field-uploader');
    $.each($uploader, function(i, uploaderWidget) {
        var $uploaderWidget = $(uploaderWidget);

        var dataId = $uploaderWidget.find('.form-element-uploader').attr('id');
        var $field_uploader = $('#'+dataId);
        var $elfinder_widget = $('#elfinder-'+dataId);

        $elfinder_widget.hide(); var flagElfinderHidden = true;
        ALINA.body.on('click', $field_uploader.selector, function (event) {
            if (flagElfinderHidden) {
                $elfinder_widget.show();
                flagElfinderHidden = false;
            }
            else {
                $elfinder_widget.hide();
                flagElfinderHidden = true;
            }
        });
        ALINA.body.on('focusout', $field_uploader.selector, function (event) {
            showUploaderPreview();
        });

        $elfinder_widget.elfinder({
            url : '/elFinder/connector',
            lang: 'ru',
            getFileCallback : function(file) {
                $field_uploader.val(file.url);
                $elfinder_widget.hide(); flagElfinderHidden = true;
                showUploaderPreview();
            },
            resizable: true
        }).elfinder('instance');

        var showUploaderPreview = function() {
            var $preview = $uploaderWidget.find('.preview').first();
            var src = $field_uploader.val();
            var $img = $('<img/>', {
                'src': src,
                'width': '100'

            });
            if (src) {
                $preview.html($img);
            }
            else {
                $preview.html('');
            }
        };
        showUploaderPreview();
    });
    //endregion Form Field uploader

    //endregion Form Fields
});