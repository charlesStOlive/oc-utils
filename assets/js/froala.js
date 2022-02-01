+function ($) {
    "use strict";
    $(document).render(function () {
        if ($.FroalaEditor) {
            $.FroalaEditor.DEFAULTS = $.extend($.FroalaEditor.DEFAULTS, {
                htmlUntouched: true,
                inlineStyles: {
                    'error': 'color: red;',
                    'danger': 'color: orange;',
                    'success': 'color: green',
                    'info': 'color: blue',
                }
            });
        }
    })
}(window.jQuery);