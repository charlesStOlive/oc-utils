/*
 * WakaFinder plugin
 *
 * Data attributes:
 * - data-control="wakafinder" - enables the plugin on an element
 * - data-option="value" - an option with a value
 *
 * JavaScript API:
 * $('a#someElement').wakaFinder({ option: 'value' })
 *
 * Dependences:
 * - Some other plugin (filename.js)
 */

+function ($) { "use strict";

    var Base = $.wn.foundation.base,
        BaseProto = Base.prototype

    // RECORDFINDER CLASS DEFINITION
    // ============================

    var WakaFinder = function(element, options) {
        this.$el       = $(element)
        this.options   = options || {}

        $.wn.foundation.controlUtils.markDisposable(element)
        Base.call(this)
        this.init()
    }

    WakaFinder.prototype = Object.create(BaseProto)
    WakaFinder.prototype.constructor = WakaFinder

    WakaFinder.prototype.init = function() {
        this.$el.on('dblclick', this.proxy(this.onDoubleClick))
        this.$el.one('dispose-control', this.proxy(this.dispose))
    }

    WakaFinder.prototype.dispose = function() {
        this.$el.off('dblclick', this.proxy(this.onDoubleClick))
        this.$el.off('dispose-control', this.proxy(this.dispose))
        this.$el.removeData('oc.wakafinder')

        this.$el = null

        // In some cases options could contain callbacks,
        // so it's better to clean them up too.
        this.options = null

        BaseProto.dispose.call(this)
    }

    WakaFinder.DEFAULTS = {
        refreshHandler: null,
        dataLocker: null
    }

    WakaFinder.prototype.onDoubleClick = function(linkEl, recordId) {
        $('.btn.find-record', this.$el).trigger('click')
    }

    WakaFinder.prototype.updateRecord = function(linkEl, recordId) {
        if (!this.options.dataLocker) return

        // Selector name must be used because by the time success runs
        // - this.options will be disposed
        // - $locker element will be replaced
        var locker = this.options.dataLocker

        $(locker).val(recordId)

        this.$el.loadIndicator({ opaque: true })
        this.$el.request(this.options.refreshHandler, {
            success: function(data) {
                this.success(data)
                $(locker).trigger('change')
            }
        })

        $(linkEl).closest('.wakafinder-popup').popup('hide')
    }

    // RECORDFINDER PLUGIN DEFINITION
    // ============================

    var old = $.fn.wakaFinder

    $.fn.wakaFinder = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), result
        this.each(function () {
            var $this   = $(this)
            var data    = $this.data('oc.wakafinder')
            var options = $.extend({}, WakaFinder.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('oc.wakafinder', (data = new WakaFinder(this, options)))
            if (typeof option == 'string') result = data[option].apply(data, args)
            if (typeof result != 'undefined') return false
        })

        return result ? result : this
    }

    $.fn.wakaFinder.Constructor = WakaFinder

    // RECORDFINDER NO CONFLICT
    // =================

    $.fn.wakaFinder.noConflict = function () {
        $.fn.wakaFinder = old
        return this
    }

    // RECORDFINDER DATA-API
    // ===============
    $(document).render(function () {
        $('[data-control="wakafinder"]').wakaFinder()
    })

}(window.jQuery);
