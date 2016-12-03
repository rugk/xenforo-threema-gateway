/**
 * @file JS functions used for Threema Gateway 2FA login methods.
 *
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

jQuery(document).ready(function() {
    'use strict';

    QrCodeCreator.createQr();
});

/**
 * QrCodeCreator - Creates QR code in all specified areas.
 *
 * Needs jquery.qrcode. (tested with v0.12.0)
 *
 * @param  {object} window
 * @param  {object} document
 * @return {object} Methods: update
 */
var QrCodeCreator = (function (window, document) {
    'use strict';
    var qrCodeElem = '.threemagw_createqr';
    var me = {};

    /**
     * createQr - Create the QR codes out of the given data.
     *
     * @param {object} event jQuery event
     */
    me.createQr = function createQr(event) {
        var $el = $(qrCodeElem);

    	$el.qrcode({
    		text: $el.data('qrcode')
    	});
    };

    return me;
})(window, document);
