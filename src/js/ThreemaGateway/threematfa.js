/**
 * @file JS functions used for Threema Gateway 2FA login methods.
 *
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

jQuery(document).ready(function() {
    'use strict';

    // global variable
    window.DEBUG = true;

    QrCodeCreator.createQr();
    AutoTriggerer.init();
    AutoTriggerer.triggerStart();
});

/**
 * QrCodeCreator - Creates QR code in all specified areas.
 *
 * Needs jquery.qrcode. (tested with v0.12.0)
 *
 * @param  {object} window
 * @param  {object} document
 * @return {object} Methods: createQr
 */
var QrCodeCreator = (function (window, document) {
    'use strict';
    var me = {};
    var qrCodeElem = '.threemagw_createqr';

    /**
     * createQr - Create the QR codes out of the given data.
     *
     * @param {object} event jQuery event
     */
    me.createQr = function createQr(event) {
        var $el = $(qrCodeElem);

        // do not continue if library is not loaded
        if (!jQuery().qrcode) {
            return;
        }

    	$el.qrcode({
    		text: $el.data('qrcode')
    	});
    };

    return me;
})(window, document);

/**
 * AutoTriggerer - Automatically submits the form and hides
 * any errors, which may be occuring.
 *
 * @param  {object} window
 * @param  {object} document
 * @return {object} Methods: init, triggerStart, triggerStop
 */
var AutoTriggerer = (function (window, document) {
    'use strict';
    var me = {};
    var indicatorElem = '#threemagw_auto_trigger';
    var period = 2000;
    var expectedError;

    var active;

    var $indicator;
    var $form;
    var $submitUnit;
    var $ajaxProgressWrapper = null;

    /**
     * triggerCheck - triggers a new form check
     */
    function triggerCheck() {
        if (!active) {
            return;
        }

        // hide AJAX loading indicator
        hideAjaxLoading();
        // submit form to trigger AJAX request
        $form.submit();
        // reregister timeout
        setTimeout(triggerCheck, period);
    };

    /**
     * errorHandler - handles errors when verifying the 2FA mode
     *
     * @this form, which triggered the error
     * @param {object} event jQuery event
     */
    function errorHandler(event) {
        // allow display of AJAX loading indicator again
        showAjaxLoading();

        // only handle error if this is indeed our form
        if (!$form.is(event.target)) { // comparing against this would be possible too
            return;
        }

        var error = event.ajaxData.error;

        // only handle the expected error
        if (error.length != 1 || error[0] != expectedError) {
            // when other unexpected error happens, stop whole module
            // so we fall back to the "traditional" input
            me.triggerStop();
            return;
        }

        // apart from logging the event, just ignore it
        if (window.DEBUG) console.log(event);
        console.log('The automatic form submission failed: ' + error);

        // prevent error overlay from appearing
        event.preventDefault();
    };

    /**
     * hideAjaxLoadingInit - wraps ajax loader, so it can be hidden later
     *
     * @param {object} event jQuery event
     */
    function hideAjaxLoadingInit(event) {
        // unregister myself
        $(document).off('ajaxStart', hideAjaxLoadingInit)

        // wrap loading indicator into div
        if (window.DEBUG) console.log('Wrapping AJAX Loading indicator…');
        $ajaxProgressWrapper = $('#AjaxProgress').wrap('<div></div>').parent();

        // as an immediate measure we need to hide the progress indicator right
        // now as it might otherwise be shown one time
        $('#AjaxProgress').hide();

        // and finally hide
        hideAjaxLoading();
    };

    /**
     * hideAjaxLoading - hides the AJAX loading overlay
     *
     */
    function hideAjaxLoading() {
        // let XenForo create element if needed
        if ($ajaxProgressWrapper === null || !$ajaxProgressWrapper.length) {
            // as it is only created when an ajax call starts, we need to wait for it and then wrap the indicator
            $(document).on('ajaxStart', hideAjaxLoadingInit)
        } else {
            if (window.DEBUG) console.log('hide ajax loading indicator', $ajaxProgressWrapper);
            $ajaxProgressWrapper.hide();
        }
    };

    /**
     * hideAjaxLoading - shows the AJAX loading overlay
     *
     */
    function showAjaxLoading() {
        // ignore this, if element does not exist
        if ($ajaxProgressWrapper === null || !$ajaxProgressWrapper.length) {
            return;
        }

        if (window.DEBUG) console.log('show ajax loading indicator', $ajaxProgressWrapper);
        $ajaxProgressWrapper.show();
    };

    /**
     * init - initialize everything
     */
    me.init = function init() {
        // get indicator
        $indicator = $(indicatorElem);

        // set variables
        $form = $('form.xenForm.AutoValidator');
        $submitUnit = $form.find('.submitUnit .button.primary').parents().eq(1);
        expectedError = $indicator.data('expectederror');

        // hide button/unit as it is useless with autoTriggering enabled
        // replace elements
        $submitUnit.after($indicator);
    };

    /**
     * triggerStart - starts auto triggering and hides the usual input via
     * button
     */
    me.triggerStart = function start() {
        // prevent multiple starts
        if (active) {
            return;
        }

        // if indicator is missing. prevent acivation
        if (!$indicator.length) {
            return;
        }

        // activate the module
        active = true;
        // enable the periodical check
        setTimeout(triggerCheck, period);

        // register/overwrite error handler
        $form.on('AutoValidationError', errorHandler);

        // finally show status & hide button as it is useless now
        $indicator.show();
        $submitUnit.children().hide();

        console.log('AutoTriggerer enabled.');
    };

    /**
     * triggerStop - stops auto triggering and offers the usual button input
     * method again
     */
    me.triggerStop = function stop() {
        // prevent next trigger
        active = false;

        // make sure the loading indicator is there again
        showAjaxLoading();

        // unregsiter error handler
        $form.off('AutoValidationError', errorHandler);

        // restore button and hide own indicator
        $indicator.hide();
        $submitUnit.children().show();

        console.log('AutoTriggerer disabled.');
    };

    return me;
})(window, document);
