/**
 * @file JS helpers for handling options in ACP.
 *
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

jQuery(document).ready(function() {
    'use strict';

    ReceiveCallback.init();
});

/**
 * ReceiveCallback - Handling the option threema_gateway_receivecallback.
 *
 * @param  {object} window
 * @param  {object} document
 * @return {object} Methods: update
 */
var ReceiveCallback = (function (window, document) {
    'use strict';
    var inputElem = '.threemagw_receivecallback_input';
    var hiddenElem = '.threemagw_receivecallback_hiddeninput';
    var me = {};

    /**
     * getOrgData - Returns the input data of the field the user can see.
     *
     * @private
     * @return {string}
     */
    function getOrgData() {
        return $(inputElem).text();
    }

    /**
     * setOrgData - Sets the value of the input field the user can see.
     *
     * @private
     * @param  {string} data The data to set.
     * @return {string}
     */
    function setOrgData(data) {
        if ($(inputElem).text() !== data) {
            return $(inputElem).text(data);
        }
    }

    /**
     * setHiddenData - Change data of the hidden input.
     *
     * @private
     * @param  {string} data The data to set.
     * @return {string}
     */
    function setHiddenData(data) {
        return $(hiddenElem).val(data);
    }

    /**
     * filterData - Filter the input data.
     *
     * @private
     * @param  {string} data The data to filter.
     * @return {string}
     */
    function filterData(data) {
        // remove all bad characters
        // https://regex101.com/r/g4Dkb7/1
        return data.replace(/[^\w_-]+/ig, '');
    }

    /**
     * init - Initialize handler.
     *
     */
    me.init = function init() {
        $(inputElem).on('input', me.update);
    };

    /**
     * update - Update the output field (and, if neccessary, also the output
     * field)
     *
     * @param {object} event jQuery event
     */
    me.update = function update(event) {
        var data;

        data = filterData(getOrgData());
        setOrgData(data);
        setHiddenData(data);
    };

    return me;
})(window, document);
