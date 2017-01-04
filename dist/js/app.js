var apiURL = 'api/v1/nick';
var fiTime = new Intl.DateTimeFormat(
    'fi-FI', { year: 'numeric', month: 'numeric', day: 'numeric',
	       hour: 'numeric', minute: 'numeric' }
);

var app = new Vue({
    el: '#app',
    data: {
	user: {
	    error: 'Ladataan...',
	},
    },
    
    filters: {
	formatDate: function (ts) {
	    return typeof ts === 'number' ? fiTime.format(1000*ts) : ''
	},
	formatMac: function (s) {
	    return typeof s === 'string' ? s.match(/.{1,2}/g).join(':') : ''
	}
    },

    created: function () {
	this.fetchData()
    },

    methods: {
	fetchData: function () {
	    var xhr = new XMLHttpRequest()
	    var self = this
	    xhr.open('GET', apiURL)
	    xhr.onload = function () {
		self.user = JSON.parse(xhr.responseText)
	    }
	    xhr.send()
	},

	setNick: function (nick) {
	    var xhr = new XMLHttpRequest()
	    var self = this
	    xhr.open('PUT', apiURL + '?nick=' + encodeURIComponent(nick === null ? '' : nick))
	    xhr.onload = function () {
		var out = JSON.parse(xhr.responseText)
		if (out.error) {
		    window.alert(out.error)
		}
		self.fetchData()
	    }
	    xhr.send()
	},
	
	delNick: function () {
	    var xhr = new XMLHttpRequest()
	    var self = this
	    xhr.open('DELETE', apiURL)
	    xhr.onload = function () {
		var out = JSON.parse(xhr.responseText)
		if (out.error) {
		    window.alert(out.error)
		}
		self.fetchData()
	    }
	    xhr.send()
	}
    }
})

