/*
 * Global helpers
 */
function showRuleSettings(id) {
    var $control = $('[data-rule-id=' + id + ']').closest('[data-control="rulerules"]')
    $control.ruleRules('onShowNewRuleSettings', id)
}

/*
 * Plugin definition
 */
+function ($) {
    "use strict";
    var Base = $.wn.foundation.base,
        BaseProto = Base.prototype

    var RuleRules = function (element, options) {
        this.$el = $(element)
        this.options = options || {}

        $.wn.foundation.controlUtils.markDisposable(element)
        Base.call(this)
        this.init()
    }

    RuleRules.prototype = Object.create(BaseProto)
    RuleRules.prototype.constructor = RuleRules

    RuleRules.prototype.init = function () {
        
        this.$el.on('click', '[data-rules-settings]', this.proxy(this.onShowSettings))
        this.$el.on('click', '[data-rules-delete]', this.proxy(this.onDeleteRule))
        this.$el.on('click', '[data-rules-reorderup]', this.proxy(this.onReorderUpRule))
        this.$el.on('click', '[data-rules-reorderdown]', this.proxy(this.onReorderDownRule))
        this.$el.on('focus', '> .row > .toolbar .loading-indicator-container [data-track-input]', this.proxy(this.onSearchInputFocus));
        this.$el.on('ajaxDone', '> .row > .toolbar  .loading-indicator-container [data-track-input]', this.proxy(this.onSearchResultsRefreshForm));
        this.$el.on('focus', '> .row > .toolbar .loading-indicator-container .clear-input-text', this.proxy(this.onSearchInputClearButtonFocus));
        this.$el.one('dispose-control', this.proxy(this.dispose))
    }

    RuleRules.prototype.dispose = function () {
        this.$el.off('click', '[data-rules-settings]', this.proxy(this.onShowSettings))
        this.$el.off('click', '[data-rules-delete]', this.proxy(this.onDeleteRule))
        this.$el.off('click', '[data-rules-reorderup]', this.proxy(this.onReorderUpRule))
        this.$el.off('click', '[data-rules-reorderdown]', this.proxy(this.onReorderDownRule))
        this.$el.off('focus', '> .row > .toolbar .loading-indicator-container [data-track-input]', this.proxy(this.onSearchInputFocus));
        this.$el.off('ajaxDone', '> .row > .toolbar  .loading-indicator-container [data-track-input]', this.proxy(this.onSearchResultsRefreshForm));
        this.$el.off('focus', '> .row > .toolbar .loading-indicator-container .clear-input-text', this.proxy(this.onSearchInputClearButtonFocus));
        this.$el.off('dispose-control', this.proxy(this.dispose))
        this.$el.removeData('oc.ruleRules')

        this.$el = null

        // In some cases options could contain callbacks, 
        // so it's better to clean them up too.
        this.options = null

        BaseProto.dispose.call(this)
    }

    RuleRules.prototype.onSearchInputFocus = function(ev) {
        //console.log.log('onSearchInputFocus')
        
        return false;
    }
    RuleRules.prototype.onSearchInputClearButtonFocus = function(ev){
        //console.log.log('onSearchInputClearButtonFocus')
        
        return false;
    }
    RuleRules.prototype.onSearchResultsRefreshForm = function() {
       //console.log.log('onSearchResultsRefreshForm')
    }

    RuleRules.prototype.onDeleteRule = function (event) {
        var $el = $(event.target),
            ruleId = getRuleIdFromElement($el)

        $el.request(this.options.deleteHandler, {
            data: { current_rule_id: ruleId },
            confirm: 'Do you really want to delete this rule?'
        })
    }

    RuleRules.prototype.onReorderUpRule = function (event) {
        var $el = $(event.target),
            ruleId = getRuleIdFromElement($el)
        $el.request(this.options.reorderupHandler, {
            data: { current_rule_id: ruleId },
        })
    }
    RuleRules.prototype.onReorderDownRule = function (event) {
        var $el = $(event.target),
            ruleId = getRuleIdFromElement($el)
        $el.request(this.options.reorderdownHandler, {
            data: { current_rule_id: ruleId },
        })
    }

    RuleRules.prototype.onApplyFilter = function (event) {
        var $el = $(event.target)
        //console.log.log('filtre asked')
        $el.request(this.options.filterHandler, {
            data: { foo: 'bar' },
        })
    }

    

    RuleRules.prototype.onShowNewRuleSettings = function (ruleId) {
        var $el = $('[data-rule-id=' + ruleId + ']')

        // Rule does not use settings
        if ($el.hasClass('no-form')) {
            return
        }

        $el.popup({
            handler: this.options.settingsHandler,
            extraData: { current_rule_id: ruleId },
            size: 'giant'
        })

        // This will not fire on successful save because the target element
        // is replaced by the time the popup loader has finished to call it
        $el.one('hide.oc.popup', this.proxy(this.onCancelRule))
    }

    RuleRules.prototype.onCancelRule = function (event) {
        var $el = $(event.target),
            ruleId = getRuleIdFromElement($el)

        $el.request(this.options.cancelHandler, {
            data: { new_rule_id: ruleId }
        })

        return false
    }

    RuleRules.prototype.onShowSettings = function (event) {
        var $el = $(event.target),
            ruleId = getRuleIdFromElement($el)

        // Rule does not use settings
        if ($el.closest('li.rule-item').hasClass('no-form')) {
            return
        }

        $el.popup({
            handler: this.options.settingsHandler,
            extraData: { current_rule_id: ruleId },
            size: 'giant'
        })

        return false
    }

    function getRuleIdFromElement($el) {
        var $item = $el.closest('li.rule-item')

        return $item.data('rule-id')
    }

    RuleRules.DEFAULTS = {
        settingsHandler: null,
        deleteHandler: null,
        reorderupHandler: null,
        reorderdownHandler: null,
        cancelHandler: null,
        createHandler: null,
        filterHandler: null
    }

    // PLUGIN DEFINITION
    // ============================

    var old = $.fn.ruleRules

    $.fn.ruleRules = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), items, result

        items = this.each(function () {
            var $this = $(this)
            var data = $this.data('oc.ruleRules')
            var options = $.extend({}, RuleRules.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('oc.ruleRules', (data = new RuleRules(this, options)))
            if (typeof option == 'string') result = data[option].apply(data, args)
            if (typeof result != 'undefined') return false
        })

        return result ? result : items
    }

    $.fn.ruleRules.Constructor = RuleRules

    $.fn.ruleRules.noConflict = function () {
        $.fn.ruleRules = old
        return this
    }



    // Add this only if required
    $(document).render(function () {
        $('[data-control="rulerules"]').ruleRules()
    })

}(window.jQuery);


