const seoRender = () => {
    const el = wp.element.createElement;
    const data = wp.data.useSelect(function(e) {
        return {
            meta: e("core/editor").getEditedPostAttribute("meta") || {},
            title: e("core/editor").getEditedPostAttribute("title") || "",
            excerpt: e("core/editor").getEditedPostAttribute("excerpt") || "",
            meta: e("core/editor").getEditedPostAttribute("meta") || {}
        };
    });
    const o = wp.data.useDispatch("core/editor").editPost;
    const m = data.meta;
    const opengraph_title = wp.element.useState(m.seo.opengraph_title);
    const opengraph_description = wp.element.useState(m.seo.opengraph_description);
    const opengraph_media_id = wp.element.useState(m.seo.opengraph_media_id);
    const opengraph_media_url = wp.element.useState(m.seo.opengraph_media_url);
    const meta_keywords = wp.element.useState(m.seo.meta_keywords);
    const meta_description = wp.element.useState(m.seo.meta_description);
    return wp.element.useEffect(function() {
        o({
            meta: {
                seo :{
                    opengraph_title: opengraph_title[0],
                    opengraph_description: opengraph_description[0],
                    opengraph_media_id: opengraph_media_id[0],
                    opengraph_media_url: opengraph_media_url[0],
                    meta_description: meta_description[0],
                    meta_keywords: meta_keywords[0]
                }
            }
        });
    }, [ opengraph_title[0], opengraph_description[0], opengraph_media_url[0], opengraph_media_id[0], meta_description[0], meta_keywords[0] ]), 
    el(wp.editPost.PluginDocumentSettingPanel, {
        name: "my-plugin-seo-card",
        title: wp.i18n.__("SEO настройки")
    }, el(wp.components.PanelRow, {}, el(wp.components.TextareaControl, {
        label: wp.i18n.__("Описание в Meta"),
        placeholder: opengraph_description[0] ? opengraph_description[0] : data.excerpt,
        onChange: meta_description[1],
        value: meta_description[0],
        help: wp.i18n.__("По умолчанию будет the_excerpt")
    })), el(wp.components.PanelRow, {}, el(wp.components.TextControl, {
        label: wp.i18n.__("Ключевые слова в Meta"),
        onChange: meta_keywords[1],
        value: meta_keywords[0]
    })), el("h5", null, wp.i18n.__("Opengraph")), el(wp.components.PanelRow, {}, el(wp.components.TextControl, {
        label: wp.i18n.__("Заголовок в Opengraph"),
        placeholder: data.title,
        onChange: opengraph_title[1],
        value: opengraph_title[0],
        help: wp.i18n.__("По умолчанию будет the_title")
    })), el(wp.components.PanelRow, {}, el(wp.components.TextareaControl, {
        label: wp.i18n.__("Описание в Opengraph"),
        placeholder: meta_description[0] ? meta_description[0] : data.excerpt,
        onChange: opengraph_description[1],
        value: opengraph_description[0],
        help: wp.i18n.__("По умолчанию будет the_excerpt")
    })), el(wp.components.PanelRow, {}, el(wp.blockEditor.MediaUpload, {
        onSelect: obj => {
            opengraph_media_id[1](obj.id),
            opengraph_media_url[1](obj.url);
        },
        value: opengraph_media_id[0],
        allowedTypes: [ "image" ],
        render: obj => {
            return el("div", null,
                    el("p", null,
                        el(wp.components.Button, {
                className: "is-secondary",
                onClick: obj.open
            }, !opengraph_media_id[0]? wp.i18n.__("Выбрать изображение") : wp.i18n.__("Выбрать другое изображение"))), opengraph_media_url[0] ? el("p", null, el(wp.components.Button, {
                className: "is-link is-destructive",
                onClick: obj => {
                   opengraph_media_id[1](0),
                   opengraph_media_url[1]('');
                }
            }, wp.i18n.__("Убрать изображение"))) : null,
            el("p", null, wp.i18n.__("Не забывайте, изображение для фона должно быть 1200×630px")),
            el("figure", {}, el("img", { src: opengraph_media_url[0] ? opengraph_media_url[0] : null}))
            
        );
        }
    })));
};

("work" === window.pagenow || "page" === window.pagenow  || "post" === window.pagenow) && wp.plugins.registerPlugin("seo", {
    render: seoRender,
    icon: null
});