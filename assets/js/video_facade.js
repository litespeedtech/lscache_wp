(function () {
	"use strict";

	var SEL = ".litespeed-video-facade";

	function decode(f) {
		try {
			return JSON.parse(atob(f.dataset.lsvfAttrs || "")) || {};
		} catch (e) {
			return {};
		}
	}

	function swap(f) {
		if (f.dataset.lsvfSwapped) return;
		f.dataset.lsvfSwapped = "1";

		var a = decode(f),
			i = document.createElement("iframe");

		Object.keys(a).forEach(function (k) {
			if (k === "src") return;
			i.setAttribute(k, a[k]);
		});

		i.src = f.dataset.lsvfSrc;

		if (!i.getAttribute("allow"))
			i.setAttribute("allow", "autoplay; encrypted-media; fullscreen; picture-in-picture");

		if (!i.hasAttribute("allowfullscreen"))
			i.setAttribute("allowfullscreen", "");

		f.parentNode.replaceChild(i, f);
	}

	// Vertical space (px) a pseudo-element reserves, or 0 if it isn't rendered.
	// WP core block embeds reserve the ratio via `::before{content:"";padding-top:56.25%}`.
	function pseudoReserved(p, pe) {
		var s = getComputedStyle(p, pe);
		if (!s || s.content === "none") return 0; // pseudo not generated -> reserves nothing
		return (parseFloat(s.paddingTop) || 0) + (parseFloat(s.paddingBottom) || 0) + (parseFloat(s.height) || 0);
	}

	// Many themes (incl. WP core / block themes like Twenty Twenty-Six) wrap a
	// video in a responsive container that reserves the aspect ratio with a
	// percentage spacer and positions the *iframe* absolutely to fill it. Our
	// facade replaces the iframe with a <div>, which the theme's `iframe` rule
	// no longer matches, so the facade falls into normal flow and stacks below
	// the reserved space -- leaving empty padding above/under the video. When we
	// detect such a parent, mirror what the theme does to its iframe: absolutely
	// fill the reserved box and drop our own intrinsic (aspect-ratio) sizing.
	function fitToWrapper(f) {
		var p = f.parentNode;
		if (!p || p.nodeType !== 1 || typeof getComputedStyle !== "function") return;

		var cs = getComputedStyle(p);
		if (cs.position === "static") return; // responsive ratio wrappers are positioned

		// Any video ratio reserves >= ~42% of the wrapper width (21:9); cosmetic
		// padding is far smaller. Gate on that so we never collapse a normal parent.
		var w = p.clientWidth || 0;
		var threshold = Math.max(40, w * 0.3);

		var reserved = Math.max(
			(parseFloat(cs.paddingTop) || 0) + (parseFloat(cs.paddingBottom) || 0),
			pseudoReserved(p, "::before"),
			pseudoReserved(p, "::after")
		);
		if (reserved < threshold) return;

		f.style.position = "absolute";
		f.style.top = "0";
		f.style.left = "0";
		f.style.right = "0";
		f.style.bottom = "0";
		f.style.width = "100%";
		f.style.height = "100%";
		f.style.maxWidth = "none";
		f.style.setProperty("aspect-ratio", "auto");
	}

	function init() {
		var fs = document.querySelectorAll(SEL);
		if (!fs.length) return;

		Array.prototype.forEach.call(fs, function (f) {
			fitToWrapper(f);
			f.addEventListener("click", function (e) {
				e.preventDefault();
				swap(f);
			});
		});
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", init);
	} else {
		init();
	}
})();
