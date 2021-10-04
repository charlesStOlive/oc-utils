/*
 * Global helpers
 */
function showFncSettings(id) {
    var $control = $('[data-fnc-id=' + id + ']').closest('[data-control="rulefncs"]')

    $control.ruleFncs('onShowNewFncSettings', id)
}

/*
 * Plugin definition
 */
+function ($) {
    "use strict";
    var Base = $.wn.foundation.base,
        BaseProto = Base.prototype

    var RuleFncs = function (element, options) {
        this.$el = $(element)
        this.options = options || {}

        $.wn.foundation.controlUtils.markDisposable(element)
        Base.call(this)
        this.init()
    }

    RuleFncs.prototype = Object.create(BaseProto)
    RuleFncs.prototype.constructor = RuleFncs

    RuleFncs.prototype.init = function () {
        this.$el.on('click', '[data-fncs-settings]', this.proxy(this.onShowSettings))
        this.$el.on('click', '[data-fncs-delete]', this.proxy(this.onDeleteFnc))
        this.$el.one('dispose-control', this.proxy(this.dispose))
    }

    RuleFncs.prototype.dispose = function () {
        this.$el.off('click', '[data-fncs-settings]', this.proxy(this.onShowSettings))
        this.$el.off('click', '[data-fncs-delete]', this.proxy(this.onDeleteFnc))
        this.$el.off('dispose-control', this.proxy(this.dispose))
        this.$el.removeData('oc.ruleFncs')

        this.$el = null

        // In some cases options could contain callbacks, 
        // so it's better to clean them up too.
        this.options = null

        BaseProto.dispose.call(this)
    }

    RuleFncs.prototype.onDeleteFnc = function (event) {
        var $el = $(event.target),
            fncId = getFncIdFromElement($el)

        $el.request(this.options.deleteHandler, {
            data: { current_fnc_id: fncId },
            confirm: 'Do you really want to delete this fnc?'
        })
    }

    RuleFncs.prototype.onShowNewFncSettings = function (fncId) {
        var $el = $('[data-fnc-id=' + fncId + ']')

        // Fnc does not use settings
        if ($el.hasClass('no-form')) {
            return
        }

        $el.popup({
            handler: this.options.settingsHandler,
            extraData: { current_fnc_id: fncId },
            size: 'giant'
        })

        // This will not fire on successful save because the target element
        // is replaced by the time the popup loader has finished to call it
        $el.one('hide.oc.popup', this.proxy(this.onCancelFnc))
    }

    RuleFncs.prototype.onCancelFnc = function (event) {
        var $el = $(event.target),
            fncId = getFncIdFromElement($el)

        $el.request(this.options.cancelHandler, {
            data: { new_fnc_id: fncId }
        })

        return false
    }

    RuleFncs.prototype.onShowSettings = function (event) {
        var $el = $(event.target),
            fncId = getFncIdFromElement($el)

        // Fnc does not use settings
        if ($el.closest('li.fnc-item').hasClass('no-form')) {
            return
        }

        $el.popup({
            handler: this.options.settingsHandler,
            extraData: { current_fnc_id: fncId },
            size: 'giant'
        })

        return false
    }

    function getFncIdFromElement($el) {
        var $item = $el.closest('li.fnc-item')

        return $item.data('fnc-id')
    }

    RuleFncs.DEFAULTS = {
        settingsHandler: null,
        deleteHandler: null,
        cancelHandler: null,
        createHandler: null
    }

    // PLUGIN DEFINITION
    // ============================

    var old = $.fn.ruleFncs

    $.fn.ruleFncs = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), items, result

        items = this.each(function () {
            var $this = $(this)
            var data = $this.data('oc.ruleFncs')
            var options = $.extend({}, RuleFncs.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('oc.ruleFncs', (data = new RuleFncs(this, options)))
            if (typeof option == 'string') result = data[option].apply(data, args)
            if (typeof result != 'undefined') return false
        })

        return result ? result : items
    }

    $.fn.ruleFncs.Constructor = RuleFncs

    $.fn.ruleFncs.noConflict = function () {
        $.fn.ruleFncs = old
        return this
    }

    // Add this only if required
    $(document).render(function () {
        $('[data-control="rulefncs"]').ruleFncs()
    })

}(window.jQuery);
