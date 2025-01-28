!function() {
    function e(e) {
        if ("string" == typeof e) {
            var s = e.substring(0, 1);
            if ("[" === s || "{" === s)
                return !0
        }
        return !1
    }
    var s = document.getElementById("init-data");
    if (s) {
        var n = s.getAttribute("value");
        if ("string" == typeof n) {
            var t = JSON.parse(n);
            for (var r in t) {
                var i = t[r];
                window[r] = e(i) ? JSON.parse(i) : i
            }
        }
    }
}();
var BrowserSniffing = {
    init: function() {
        __browser.version = parseInt(__browser.version);
        __browser.platform = __browser.platform ? __browser.platform : '';
        this.isIE11OrLess() && this.addIEclasses(),
        this._hasClassList() && (document.documentElement.classList.add(__browser.platform),
        document.documentElement.classList.add(this.getBrowserClassname()))
    },
    _hasClassList: function() {
        return document.documentElement && document.documentElement.classList
    },
    isIE11OrLess: function() {
        return "ie" === __browser.name && 1 * __browser.version <= 11
    },
    getBrowserClassname: function() {
        return __browser.name + __browser.version
    },
    addIEclasses: function() {
        document.documentElement.className += " " + this.getBrowserClassname()
    }
};
try {
    BrowserSniffing.init();
} catch (e) {}
