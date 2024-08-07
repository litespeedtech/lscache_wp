(() => {
    const lsc_triggerEvents = [
        "keydown",
        "mousedown",
        "mousemove",
        "touchmove",
        "touchstart",
        "touchend",
        "wheel",
    ];
    let lsc_everythingLoaded = false;
    let lsc_interceptedClicks = [];
    let lsc_interceptedClickListeners = [];
    let lsc_delayedScripts = { normal: [], async: [], defer: [] };
    let lsc_trash = [];
    let lsc_allJQueries = [];
    let lsc_windowLoaded = false;
    let lsc_domReadyFired = false;
    let lsc_persisted = false;
    let lsc_throttleActive = false;
    let lsc_lastBreath = Date.now();

    const eventHandlers = {
        userInteractionHandler: null,
        touchStartHandler: null,
        touchMoveHandler: null,
        touchEndHandler: null,
        clickHandler: null,
    };

    // Setup event listeners based on specified trigger events
    function lscSetupEventListeners() {
        lsc_triggerEvents.forEach((event) =>
            window.addEventListener(
                event,
                eventHandlers.userInteractionHandler,
                { passive: true }
            )
        );
        window.addEventListener("touchstart", eventHandlers.touchStartHandler, {
            passive: true,
        });
        window.addEventListener("mousedown", eventHandlers.touchStartHandler);
        document.addEventListener(
            "visibilitychange",
            eventHandlers.userInteractionHandler
        );
    }

    // Remove all event listeners to prepare for the next action
    function lscRemoveEventListeners() {
        lsc_triggerEvents.forEach((event) =>
            window.removeEventListener(
                event,
                eventHandlers.userInteractionHandler,
                { passive: true }
            )
        );
        document.removeEventListener(
            "visibilitychange",
            eventHandlers.userInteractionHandler
        );
    }

    // Main function to handle user interaction events
    function lscHandleUserInteraction(e) {
        lscRemoveEventListeners();
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", lscConDocumentReady);
        } else {
            lscConDocumentReady();
        }
    }

    // Main function to handle user interaction events
    function lscInitiateTouchStart(e) {
        if (e.target.tagName !== "HTML") {
            window.addEventListener("touchend", eventHandlers.touchEndHandler);
            window.addEventListener("mouseup", eventHandlers.touchEndHandler);
            window.addEventListener(
                "touchmove",
                eventHandlers.touchMoveHandler,
                { passive: true }
            );
            window.addEventListener(
                "mousemove",
                eventHandlers.touchMoveHandler
            );
            e.target.addEventListener("click", eventHandlers.clickHandler);
            lscToggleInterceptedClickListeners(e.target, true);
            lscMoveAttribute(e.target, "onclick", "onclick-lsc");
            lscSetThrottleStatus(true);
        }
    }

    // Event handler for touchmove events
    function lscInitiateTouchMove(e) {
        window.removeEventListener("touchend", eventHandlers.touchEndHandler);
        window.removeEventListener("mouseup", eventHandlers.touchEndHandler);
        window.removeEventListener(
            "touchmove",
            eventHandlers.touchMoveHandler,
            { passive: true }
        );
        window.removeEventListener("mousemove", eventHandlers.touchMoveHandler);
        e.target.removeEventListener("click", eventHandlers.clickHandler);
        lscToggleInterceptedClickListeners(e.target, false);
        lscMoveAttribute(e.target, "onclick-lsc", "onclick");
        lscSetThrottleStatus(false);
    }

    // Event handler for touchend events
    function lscInitiateTouchEnd() {
        window.removeEventListener("touchend", eventHandlers.touchEndHandler);
        window.removeEventListener("mouseup", eventHandlers.touchEndHandler);
        window.removeEventListener(
            "touchmove",
            eventHandlers.touchMoveHandler,
            { passive: true }
        );
        window.removeEventListener("mousemove", eventHandlers.touchMoveHandler);
    }

    // Event handler to manage intercepted clicks
    function lscHandleInterceptedClick(e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        if (e.target) {
            const targetElement = e.target;
            lscToggleInterceptedClickListeners(targetElement, false);
            targetElement.removeEventListener(
                "click",
                eventHandlers.clickHandler
            );
            lscMoveAttribute(targetElement, "onclick-lsc", "onclick");

            // Trigger the click event programmatically
            const clickEvent = new MouseEvent("click", {
                view: window,
                bubbles: true,
                cancelable: true,
            });
            targetElement.dispatchEvent(clickEvent);

            // Store the intercepted click for future reference if needed
            lsc_interceptedClicks.push(e);
        }
        lscSetThrottleStatus(false);
    }

    // Dispatch intercepted clicks events
    function lscDispatchInterceptedClicks() {
        window.removeEventListener(
            "touchstart",
            eventHandlers.touchStartHandler,
            { passive: true }
        );
        window.removeEventListener(
            "mousedown",
            eventHandlers.touchStartHandler
        );
        lsc_interceptedClicks.forEach((e) => {
            e.target.dispatchEvent(
                new MouseEvent("click", {
                    view: e.view,
                    bubbles: true,
                    cancelable: true,
                })
            );
        });
    }

    // Toggle intercepted click event listeners
    function lscToggleInterceptedClickListeners(target, bind) {
        lsc_interceptedClickListeners.forEach((listener) => {
            if (listener.target === target) {
                if (bind) {
                    target.removeEventListener(
                        "click",
                        listener.func,
                        listener.options
                    );
                } else {
                    target.addEventListener(
                        "click",
                        listener.func,
                        listener.options
                    );
                }
            }
        });

        if (target.parentNode !== document.documentElement) {
            lscToggleInterceptedClickListeners(target.parentNode, bind);
        }
    }

    // Move attributes from old attribute to new attribute in DOM elements
    function lscMoveAttribute(target, oldAttr, newAttr) {
        if (target.hasAttribute(oldAttr)) {
            target.setAttribute(newAttr, target.getAttribute(oldAttr));
            target.removeAttribute(oldAttr);
        }
    }

    // Set the throttle status for handling script execution
    function lscSetThrottleStatus(status) {
        lsc_throttleActive = status;
    }

    // Process document ready event and start loading delayed scripts
    async function lscConDocumentReady() {
        console.log("[LiteSpeed] Start Load JS Delayed");
        lsc_lastBreath = Date.now();
        lscRewriteEventListeners();
        lscRewritejQuery();
        lscInterceptDocumentWrite();
        lscCollectDelayedScripts();
        lscPreloadScripts();
        await lscExecuteScripts(lsc_delayedScripts.normal);
        await lscExecuteScripts(lsc_delayedScripts.defer);
        await lscExecuteScripts(lsc_delayedScripts.async);
        try {
            await lscCompleteScriptExecution();
            await lscRewriteWebpackScript();
            await lscBroadcastEvents();
        } catch (error) {
            console.error(error);
        }
        window.dispatchEvent(new Event("lsc-allScriptsLoaded"));
        lsc_everythingLoaded = true;
        lscDispatchInterceptedClicks();
        lscCleanup();
    }

    // Process DOMContentLoaded event
    function lscProcessDOMContentLoaded() {
        let scriptsToPreconnect = [];
        document
            .querySelectorAll('script[type^="litespeed/javascript"][data-src]')
            .forEach((script) => {
                let src = script.getAttribute("data-src");
                if (src && src.indexOf("data:") !== 0) {
                    if (src.indexOf("//") === 0) {
                        src = location.protocol + src;
                    }
                    try {
                        const origin = new URL(src).origin;
                        if (origin !== location.origin) {
                            scriptsToPreconnect.push({
                                src: origin,
                                crossOrigin:
                                    script.crossOrigin ||
                                    script.getAttribute("data-lsc-type") ===
                                        "module",
                            });
                        }
                    } catch (error) {}
                }
            });
        scriptsToPreconnect = [
            ...new Map(
                scriptsToPreconnect.map((item) => [JSON.stringify(item), item])
            ).values(),
        ];
        lscPreconnectResources(scriptsToPreconnect, "preconnect");
    }

    // Collect delayed scripts based on type for execution
    function lscCollectDelayedScripts() {
        document
            .querySelectorAll('script[type^="litespeed/javascript"]')
            .forEach((script) => {
                if (script.hasAttribute("data-src")) {
                    if (
                        script.hasAttribute("async") &&
                        script.async !== false
                    ) {
                        lsc_delayedScripts.async.push(script);
                    } else if (
                        (script.hasAttribute("defer") &&
                            script.defer !== false) ||
                        script.getAttribute("data-lsc-type") === "module"
                    ) {
                        lsc_delayedScripts.defer.push(script);
                    } else {
                        lsc_delayedScripts.normal.push(script);
                    }
                } else {
                    lsc_delayedScripts.normal.push(script);
                }
            });
    }

    // Execute a single script with additional functionality
    async function lscExecuteScript(script) {
        if (
            (await lscThrottleExecution(),
            script.noModule && !("noModule" in HTMLScriptElement.prototype))
        ) {
            return;
        }

        return new Promise((resolve) => {
            let newScript;

            function onError() {
                (newScript || script).setAttribute("data-lsc-status", "failed");
                resolve();
            }

            function onload() {
                (newScript || script).setAttribute(
                    "data-lsc-status",
                    "executed"
                );
                resolve();
            }

            try {
                // if (navigator.userAgent.indexOf("Firefox/") > 0 || navigator.vendor === "") {
                //     newScript = document.createElement("script");
                //     [...script.attributes].forEach((attr) => {
                //         let name = attr.nodeName;
                //         if (name !== "type") {
                //             if (name === "data-lsc-type") {
                //                 name = "type";
                //             }
                //             if (name === "data-src") {
                //                 name = "src";
                //             }
                //             newScript.setAttribute(name, attr.nodeValue);
                //         }
                //     });
                //     if (script.text) {
                //         newScript.text = script.text;
                //     }

                //     if (newScript.hasAttribute("src")) {
                //         console.log('[LiteSpeed] Load ', newScript.attribute("src"));
                //         newScript.addEventListener("load", onload);
                //         newScript.addEventListener("error", onError);
                //         setTimeout(() => {
                //             if (!newScript.isConnected) {
                //                 resolve();
                //             }
                //         }, 1);
                //     } else {
                //         newScript.text = script.text;
                //         onload();
                //     }
                //     script.parentNode.replaceChild(newScript, script);
                // } else {
                const scriptType = script.getAttribute("data-lsc-type");
                const scriptSrc = script.getAttribute("data-src");
                if (scriptType) {
                    script.type = scriptType;
                    script.removeAttribute("data-lsc-type");
                } else {
                    script.removeAttribute("type");
                }
                script.addEventListener("load", onload);
                script.addEventListener("error", onError);

                if (scriptSrc) {
                    console.log("[LiteSpeed] Load ", scriptSrc);
                    script.removeAttribute("data-src");
                    script.src = scriptSrc;
                } else {
                    console.log(
                        "[LiteSpeed] Load inline JS" +
                            (script.getAttribute("id")
                                ? " #" + script.getAttribute("id")
                                : " No id added")
                    );
                    script.src =
                        "data:text/javascript;base64," +
                        window.btoa(unescape(encodeURIComponent(script.text)));
                }
                // }
            } catch (error) {
                onError();
            }
        });
    }

    // Execute multiple scripts in sequence
    async function lscExecuteScripts(arr) {
        const script = arr.shift();
        if (script && script.isConnected) {
            await lscExecuteScript(script);
            await lscExecuteScripts(arr);
        } else {
            return Promise.resolve();
        }
    }

    // Preload scripts for faster load times
    function lscPreloadScripts() {
        lscPreconnectResources(
            [
                ...lsc_delayedScripts.normal,
                ...lsc_delayedScripts.defer,
                ...lsc_delayedScripts.async,
            ],
            "preload"
        );
    }

    // Preconnect resources for improved performance
    function lscPreconnectResources(scripts, rel) {
        const fragment = document.createDocumentFragment();
        scripts.forEach((script) => {
            const src =
                (script.getAttribute && script.getAttribute("data-src")) ||
                script.src;
            if (src) {
                const link = document.createElement("link");
                link.href = src;
                link.rel = rel;
                if (rel !== "preconnect") {
                    link.as = "script";
                }
                if (
                    script.getAttribute &&
                    script.getAttribute("data-lsc-type") === "module"
                ) {
                    link.crossOrigin = true;
                }
                if (script.crossOrigin) {
                    link.crossOrigin = script.crossOrigin;
                }
                if (script.integrity) {
                    link.integrity = script.integrity;
                }
                fragment.appendChild(link);
                lsc_trash.push(link);
            }
        });
        document.head.appendChild(fragment);
    }

    // Rewrite event listeners for specific elements
    function lscRewriteEventListeners() {
        const elements = [document, window];
        const eventPrefix = "lsc-";

        function wrap(event, element) {
            if (!element.eventsToRewrite) {
                return event;
            }
            if (
                element.eventsToRewrite.includes(event) &&
                !lsc_everythingLoaded
            ) {
                return eventPrefix + event;
            }

            return event;
        }

        function interceptAddEventListener(element, event) {
            if (!element.eventsToRewrite) {
                element.eventsToRewrite = [];
                const originalAddEventListener = element.addEventListener;
                element.addEventListener = function (eventType, listener) {
                    eventType = wrap(eventType, element);
                    originalAddEventListener.apply(this, [eventType, listener]);
                };
            }
            element.eventsToRewrite.push(event);
        }

        function interceptProperty(element, property) {
            let original = element[property];
            element[property] = null;
            if (!element.hasOwnProperty(property)) {
                Object.defineProperty(element, property, {
                    get: () => original || function () {},
                    set(fn) {
                        if (lsc_everythingLoaded) {
                            original = fn;
                        } else {
                            element[eventPrefix + property] = original = fn;
                        }
                    },
                });
            }
        }

        elements.forEach((element) => {
            interceptAddEventListener(element, "DOMContentLoaded");
            interceptAddEventListener(window, "DOMContentLoaded");
            interceptAddEventListener(window, "load");
            interceptAddEventListener(window, "pageshow");
            interceptAddEventListener(document, "readystatechange");
            interceptProperty(document, "onreadystatechange");
            interceptProperty(window, "onload");
            interceptProperty(window, "onpageshow");
        });
    }

    // Rewrite jQuery events and behavior
    function lscRewritejQuery() {
        function wrapJQueryEvents(eventType) {
            if (lsc_everythingLoaded) {
                return eventType;
            }

            return eventType
                .split(" ")
                .map((name) =>
                    name.startsWith("load") ? "lsc-jquery-load" : name
                )
                .join(" ");
        }

        function interceptjQuery($) {
            if ($ && $.fn && !lsc_allJQueries.includes($)) {
                $.fn.ready = $.fn.init.prototype.ready = function (fn) {
                    if (lsc_domReadyFired) {
                        fn.bind(document)($);
                    } else {
                        document.addEventListener(
                            "DOMContentLiteSpeedLoaded",
                            () => fn.bind(document)($)
                        );
                    }
                    return $([]);
                };
                const originalOn = $.fn.on;
                $.fn.on = $.fn.init.prototype.on = function (...args) {
                    if (this[0] === window) {
                        if (typeof args[0] === "string") {
                            args[0] = wrapJQueryEvents(args[0]);
                        } else if (typeof args[0] === "object") {
                            Object.keys(args[0]).forEach((key) => {
                                const handler = args[0][key];
                                delete args[0][key];
                                args[0][wrapJQueryEvents(key)] = handler;
                            });
                        }
                    }
                    return originalOn.apply(this, args);
                };
                lsc_allJQueries.push($);
            }
        }

        // Check if jQuery is already loaded
        if (typeof jQuery !== "undefined") {
            Promise.resolve().then(() => interceptjQuery(jQuery));
        } else {
            const checkJQuery = () => {
                if (typeof jQuery !== "undefined") {
                    interceptjQuery(jQuery);
                } else {
                    setTimeout(checkJQuery, 50);
                }
            };
            checkJQuery();
        }
    }

    // Rewrite the behavior of webpack script loading
    async function lscRewriteWebpackScript() {
        const webpackScript = document.querySelector("script[data-webpack]");
        if (webpackScript) {
            await new Promise((resolve) => {
                webpackScript.addEventListener("load", resolve);
                webpackScript.addEventListener("error", resolve);
            });
            await lscThrottleExecution();
            await lscRewriteWebpackScript();
        }
    }

    // Complete script execution, dispatch events and trigger callbacks
    async function lscCompleteScriptExecution() {
        lsc_domReadyFired = true;
        await lscThrottleExecution();
        document.dispatchEvent(new Event("lsc-readystatechange"));
        await lscThrottleExecution();
        document.lsconreadystatechange && document.lsconreadystatechange();
        await lscThrottleExecution();
        document.dispatchEvent(new Event("DOMContentLiteSpeedLoaded"));
        await lscThrottleExecution();
        window.dispatchEvent(new Event("DOMContentLiteSpeedLoaded"));
    }

    // Broadcast events to trigger actions based on script execution
    async function lscBroadcastEvents() {
        await lscThrottleExecution();
        document.dispatchEvent(new Event("lsc-readystatechange"));
        await lscThrottleExecution();
        document.lsconreadystatechange && document.lsconreadystatechange();
        await lscThrottleExecution();
        window.dispatchEvent(new Event("lsc-load"));
        await lscThrottleExecution();
        window.lscOnload && window.lscOnload();
        await lscThrottleExecution();
        lsc_allJQueries.forEach(($) => $(window).trigger("lsc-jquery-load"));
        await lscThrottleExecution();
        const pageshowEvent = new Event("lsc-pageshow");
        pageshowEvent.persisted = lsc_persisted;
        window.dispatchEvent(pageshowEvent);
        await lscThrottleExecution();
        window.lscOnpageshow &&
            window.lscOnpageshow({ persisted: lsc_persisted });
        lsc_windowLoaded = true;
    }

    // Run persisted callbacks to handle specific events
    function lscRunPersistedCallbacks() {
        if (document.lsconreadystatechange) document.lsconreadystatechange();
        if (window.lscOnload) window.lscOnload();
        if (window.lscOnpageshow)
            window.lscOnpageshow({ persisted: lsc_persisted });
    }

    // Intercept document write calls to manage dynamic content
    function lscInterceptDocumentWrite() {
        const originalScripts = new Map();
        document.write = document.writeln = function (content) {
            const currentScript = document.currentScript;
            if (!currentScript) {
                console.error("LSC unable to document.write this: " + content);
                return;
            }
            const range = document.createRange();
            const parent = currentScript.parentElement;
            let nextSibling = originalScripts.get(currentScript);
            if (nextSibling === undefined) {
                nextSibling = currentScript.nextSibling;
                originalScripts.set(currentScript, nextSibling);
            }
            const fragment = document.createDocumentFragment();
            range.setStart(fragment, 0);
            fragment.appendChild(range.createContextualFragment(content));
            parent.insertBefore(fragment, nextSibling);
        };
    }

    // Throttle script execution to prevent performance issues
    async function lscThrottleExecution() {
        if (Date.now() - lsc_lastBreath > 100) {
            await lscWait();
            lsc_lastBreath = Date.now();
        }
    }

    // Async wait function for certain conditions before moving forward
    async function lscWait() {
        return document.hidden
            ? new Promise((resolve) => setTimeout(resolve))
            : new Promise((resolve) => requestAnimationFrame(resolve));
    }

    // Clean up resources and elements after execution
    function lscCleanup() {
        lsc_trash.forEach((item) => item.remove());
    }

    // Initialization function to set up event handlers and listeners
    function lscInit() {
        eventHandlers.userInteractionHandler = lscHandleUserInteraction;
        eventHandlers.touchStartHandler = lscInitiateTouchStart;
        eventHandlers.touchMoveHandler = lscInitiateTouchMove;
        eventHandlers.touchEndHandler = lscInitiateTouchEnd;
        eventHandlers.clickHandler = lscHandleInterceptedClick;
        lscSetupEventListeners();
        window.addEventListener("pageshow", (e) => {
            lsc_persisted = e.persisted;
            if (lsc_everythingLoaded) {
                lscRunPersistedCallbacks();
            }
        });
        document.addEventListener(
            "DOMContentLoaded",
            lscProcessDOMContentLoaded
        );
    }

    lscInit();
})();
