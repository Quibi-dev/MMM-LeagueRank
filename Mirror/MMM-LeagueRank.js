/* global Module */

/* Magic Mirror
 * Module: MMM-LeagueRank
 *
 * By 
 * Kevin
 */


Module.register("MMM-LeagueRank",{
	// Default module config.
	defaults: {
		text: "Kebab",
        nickname: "Miss Hippo",
        updateInterval: 30 * 1000,
	},
    getStyles: function() {
        return ["MMM-LeagueRank.css"];
    },

    start: function() {
        Log.info("Starting module: " + this.name);

        requiresVersion: "2.1.0",

        // Set locale.
        this.url = "http://lanhok.nl/API/league/smartMirrorApi.php?name="+this.config.nickname;
        this.rank = {};
        this.scheduleUpdate();
        this.loaded = false;
    },
    
	// Override dom generator.
	getDom: function() {
		var wrapper = document.createElement("div");
        
        if (!this.loaded) {
            wrapper.innerHTML = "Loading";
            return wrapper;
        }
        if(this.rank.tier == null){
            return;
        }
        wrapper.classList.add("bright", "light", "small");
        switch(this.rank.type){
            case "RANKED_FLEX_SR":
                wrapper.innerHTML = "Flex 5v5 Rank";
                break;
            case "RANKED_SOLO_5x5":
                wrapper.innerHTML = "Ranked Solo";
                break;
            case "RANKED_TFT":
                wrapper.innerHTML = "Ranked TFT";
                break;
            default:
                wrapper.innerHTML = this.rank.type;
                break;
        }
        
        var title = document.createElement("div");
        title.classList.add("small", "bright");
        var tier = this.rank.tier.toLowerCase();

        title.innerHTML = this.rank.nickname + " <br>" + capitalizeFirstLetter(tier) + ' ' +this.rank.rank + '(' + this.rank.LP + ' LP)';
        wrapper.appendChild(title);
        
        var rankDiv = document.createElement("div");
        var rankIcon = document.createElement("img");
        rankIcon.classList.add("photo");
        rankIcon.src = "./modules/MMM-LeagueRank/public/img/"+this.rank.tier+this.rank.rank+".png";
        rankDiv.appendChild(rankIcon);
        wrapper.appendChild(rankDiv);
        
        var stats = document.createElement("div");
        stats.classList.add("small", "bright");
        var totalGames = parseInt(this.rank.losses) + parseInt(this.rank.wins);
        var winrate = (parseInt(this.rank.wins) / totalGames) * 100;
        stats.innerHTML = Math.round(winrate) + "%(" + this.rank.wins + "W / " + this.rank.losses + "L)";
        wrapper.appendChild(stats);
        
		return wrapper;
	},
    
    scheduleUpdate: function() {
        setInterval(() => {
            this.getRank();
        }, this.config.updateInterval);
        this.getRank();
        var self = this;
    },
    
    getRank: function() {
        Log.log('[MMM-LeagueRank] Getting rank');
        this.sendSocketNotification('GET_RANK', this.url);
    },
    
    socketNotificationReceived: function(notification, payload) {
        if (notification === "RANK_RESULT") {
            Log.log('[MMM-LeagueRank] Updating Dom');
            this.updateDom();
            this.processInfo(payload);
        }
    },
    
    processInfo: function(data) {
        Log.log('[MMM-LeagueRank] Processing data');
        if(data.tier !== null){
            this.rank = data;
            this.loaded = true;
        }
    },
    
    loaded: function(callback) {
        this.finishLoading();
        Log.log(this.name + ' is loaded!');
        callback();
    }
});

function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}