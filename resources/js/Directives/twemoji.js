import twemoji from "twemoji";

twemoji.base = "https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/";

export default {
    mounted(el) {
        twemoji.parse(el, {
            className: "twemoji",
            folder: "svg",
            ext: ".svg",
        });
    },
    updated(el) {
        twemoji.parse(el, {
            className: "twemoji",
            folder: "svg",
            ext: ".svg",
        });
    },
};
