var bloyalElements = document.querySelectorAll('script[data-bloyal-login-domain]');
if (!bloyalElements || bloyalElements.length === 0)
	alert('No elements found. Be sure that your script includes the data-bloyal-login-domain attribute')

var domain = bloyalElements[0].getAttribute('data-bloyal-login-domain');
if (!domain) 
	alert('You must include your domain in the attribute data-bloyal-login-domain')
// this API use for fetch the bLoyal service urls info
fetch('https://domain.bloyal.io/api/v4/serviceurls/' + domain).then(response => response.json())
	.then(urlsResponse => {
		const serviceUrls = urlsResponse.data;
		let webSnippetsUrl = serviceUrls.WebSnippetsApiUrl.toLowerCase().replace('https://websnippets', 'https://snippets');
		
		fetch(`${webSnippetsUrl}/meta.json`,{
			headers : {
				'Content-Type': 'application/json',
				'Accept': 'application/json'
			},
			cache: 'no-store'
		}).then(response => response.json())
			.then(meta => {
				var cssScript = document.createElement('link');
				cssScript.href = meta.cssUrl;
				cssScript.rel = 'stylesheet';
				document.head.appendChild(cssScript);

				var compileScript = document.createElement('script');
				var inlineScript = document.createTextNode('!function (e) { function t(t) { for (var n, p, i = t[0], l = t[1], f = t[2], c = 0, s = []; c < i.length; c++)p = i[c], Object.prototype.hasOwnProperty.call(o, p) && o[p] && s.push(o[p][0]), o[p] = 0; for (n in l) Object.prototype.hasOwnProperty.call(l, n) && (e[n] = l[n]); for (a && a(t); s.length;)s.shift()(); return u.push.apply(u, f || []), r() } function r() { for (var e, t = 0; t < u.length; t++) { for (var r = u[t], n = !0, i = 1; i < r.length; i++) { var l = r[i]; 0 !== o[l] && (n = !1) } n && (u.splice(t--, 1), e = p(p.s = r[0])) } return e } var n = {}, o = { 1: 0 }, u = []; function p(t) { if (n[t]) return n[t].exports; var r = n[t] = { i: t, l: !1, exports: {} }; return e[t].call(r.exports, r, r.exports, p), r.l = !0, r.exports } p.m = e, p.c = n, p.d = function (e, t, r) { p.o(e, t) || Object.defineProperty(e, t, { enumerable: !0, get: r }) }, p.r = function (e) { "undefined" != typeof Symbol && Symbol.toStringTag && Object.defineProperty(e, Symbol.toStringTag, { value: "Module" }), Object.defineProperty(e, "__esModule", { value: !0 }) }, p.t = function (e, t) { if (1 & t && (e = p(e)), 8 & t) return e; if (4 & t && "object" == typeof e && e && e.__esModule) return e; var r = Object.create(null); if (p.r(r), Object.defineProperty(r, "default", { enumerable: !0, value: e }), 2 & t && "string" != typeof e) for (var n in e) p.d(r, n, function (t) { return e[t] }.bind(null, n)); return r }, p.n = function (e) { var t = e && e.__esModule ? function () { return e.default } : function () { return e }; return p.d(t, "a", t), t }, p.o = function (e, t) { return Object.prototype.hasOwnProperty.call(e, t) }, p.p = "/"; var i = this["webpackJsonpweb-snippets"] = this["webpackJsonpweb-snippets"] || [], l = i.push.bind(i); i.push = t, i = i.slice(); for (var f = 0; f < i.length; f++)t(i[f]); var a = l; r() }([])');
				compileScript.appendChild(inlineScript);
				document.body.appendChild(compileScript);

				var mainScript = document.createElement('script');
				mainScript.src = meta.mainUrl;
				document.body.appendChild(mainScript);


				var secondScript = document.createElement('script');
				secondScript.src = meta.secondUrl;
				document.body.appendChild(secondScript);
			})
	})
