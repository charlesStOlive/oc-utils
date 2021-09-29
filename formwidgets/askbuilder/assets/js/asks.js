/*
 * Global helpers
 */
function showAskSettings(id) {
    var $control = $('[data-ask-id=' + id + ']').closest('[data-control="ruleasks"]')

    $control.ruleAsks('onShowNewAskSettings', id)
}

/*
 * Plugin definition
 */
+function ($) {
    "use strict";
    var Base = $.wn.foundation.base,
        BaseProto = Base.prototype

    var RuleAsks = function (element, options) {
        this.$el = $(element)
        this.options = options || {}

        $.wn.foundation.controlUtils.markDisposable(element)
        Base.call(this)
        this.init()
    }

    RuleAsks.prototype = Object.create(BaseProto)
    RuleAsks.prototype.constructor = RuleAsks

    RuleAsks.prototype.init = function () {
        this.$el.on('click', '[data-asks-settings]', this.proxy(this.onShowSettings))
        this.$el.on('click', '[data-asks-delete]', this.proxy(this.onDeleteAsk))
        this.$el.one('dispose-control', this.proxy(this.dispose))
    }

    RuleAsks.prototype.dispose = function () {
        this.$el.off('click', '[data-asks-settings]', this.proxy(this.onShowSettings))
        this.$el.off('click', '[data-asks-delete]', this.proxy(this.onDeleteAsk))
        this.$el.off('dispose-control', this.proxy(this.dispose))
        this.$el.removeData('oc.ruleAsks')

        this.$el = null

        // In some cases options could contain callbacks, 
        // so it's better to clean them up too.
        this.options = null

        BaseProto.dispose.call(this)
    }

    RuleAsks.prototype.onDeleteAsk = function (event) {
        var $el = $(event.target),
            askId = getAskIdFromElement($el)

        $el.request(this.options.deleteHandler, {
            data: { current_ask_id: askId },
            confirm: 'Do you really want to delete this ask?'
        })
    }

    RuleAsks.prototype.onShowNewAskSettings = function (askId) {
        var $el = $('[data-ask-id=' + askId + ']')

        // Ask does not use settings
        if ($el.hasClass('no-form')) {
            return
        }

        $el.popup({
            handler: this.options.settingsHandler,
            extraData: { current_ask_id: askId },
            size: 'giant'
        })

        // This will not fire on successful save because the target element
        // is replaced by the time the popup loader has finished to call it
        $el.one('hide.oc.popup', this.proxy(this.onCancelAsk))
    }

    RuleAsks.prototype.onCancelAsk = function (event) {
        var $el = $(event.target),
            askId = getAskIdFromElement($el)

        $el.request(this.options.cancelHandler, {
            data: { new_ask_id: askId }
        })

        return false
    }

    RuleAsks.prototype.onShowSettings = function (event) {
        var $el = $(event.target),
            askId = getAskIdFromElement($el)

        // Ask does not use settings
        if ($el.closest('li.ask-item').hasClass('no-form')) {
            return
        }

        $el.popup({
            handler: this.options.settingsHandler,
            extraData: { current_ask_id: askId },
            size: 'giant'
        })

        return false
    }

    function getAskIdFromElement($el) {
        var $item = $el.closest('li.ask-item')

        return $item.data('ask-id')
    }

    RuleAsks.DEFAULTS = {
        settingsHandler: null,
        deleteHandler: null,
        cancelHandler: null,
        createHandler: null
    }

    // PLUGIN DEFINITION
    // ============================

    var old = $.fn.ruleAsks

    $.fn.ruleAsks = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), items, result

        items = this.each(function () {
            var $this = $(this)
            var data = $this.data('oc.ruleAsks')
            var options = $.extend({}, RuleAsks.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('oc.ruleAsks', (data = new RuleAsks(this, options)))
            if (typeof option == 'string') result = data[option].apply(data, args)
            if (typeof result != 'undefined') return false
        })

        return result ? result : items
    }

    $.fn.ruleAsks.Constructor = RuleAsks

    $.fn.ruleAsks.noConflict = function () {
        $.fn.ruleAsks = old
        return this
    }

    // Add this only if required
    $(document).render(function () {
        $('[data-control="ruleasks"]').ruleAsks()
    })

}(window.jQuery);
