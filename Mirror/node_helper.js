/* Magic Mirror
 * Module: MMM-LeagueRank
 *
 * By Kevin
 *
 */
const NodeHelper = require('node_helper');
const request = require('request');

module.exports = NodeHelper.create({

    start: function() {
        console.log("Starting node_helper for: " + this.name);
    },

    getRank: function(url) {
        request({
            url: url,
            method: 'GET'
        }, (error, response, body) => {
            if (!error && response.statusCode == 200) {
                var result = JSON.parse(body);
                if(typeof result.nickname !== 'undefined'){
                    this.sendSocketNotification('RANK_RESULT', result);
                }                
            }
        });
    },

    socketNotificationReceived: function(notification, payload) {
        if (notification === 'GET_RANK') {
            this.getRank(payload);
        }
    }
});
