import { onMounted, mergeProps, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderList, ssrRenderAttr } from "vue/server-renderer";
const _sfc_main = {
  __name: "SponsorsSlider",
  __ssrInlineRender: true,
  props: {
    patrocinadores: {
      type: Array,
      default: () => []
    }
  },
  setup(__props) {
    const props = __props;
    onMounted(() => {
      if (!props.patrocinadores.length || window.Swiper) {
        window.Swiper && new window.Swiper(".sponsors-swiper", {
          loop: props.patrocinadores.length > 4,
          autoplay: { delay: 3e3, disableOnInteraction: false },
          slidesPerView: 2,
          spaceBetween: 24,
          breakpoints: {
            640: { slidesPerView: 3 },
            1024: { slidesPerView: 5 }
          }
        });
        return;
      }
      const css = document.createElement("link");
      css.rel = "stylesheet";
      css.href = "https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css";
      document.head.appendChild(css);
      const script = document.createElement("script");
      script.src = "https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js";
      script.onload = () => new window.Swiper(".sponsors-swiper", {
        loop: props.patrocinadores.length > 4,
        autoplay: { delay: 3e3, disableOnInteraction: false },
        slidesPerView: 2,
        spaceBetween: 24,
        breakpoints: {
          640: { slidesPerView: 3 },
          1024: { slidesPerView: 5 }
        }
      });
      document.body.appendChild(script);
    });
    return (_ctx, _push, _parent, _attrs) => {
      if (__props.patrocinadores.length) {
        _push(`<section${ssrRenderAttrs(mergeProps({ class: "border-t border-amber-200 bg-amber-50 py-14" }, _attrs))}><div class="mx-auto max-w-6xl px-5 lg:px-8"><p class="mb-8 text-center text-xs font-bold uppercase tracking-[0.2em] text-amber-700">Os nossos patrocinadores</p><div class="swiper sponsors-swiper"><div class="swiper-wrapper items-center"><!--[-->`);
        ssrRenderList(__props.patrocinadores, (sponsor) => {
          _push(`<div class="swiper-slide flex justify-center"><a${ssrRenderAttr("href", sponsor.website || "#")} target="_blank" rel="noopener noreferrer"${ssrRenderAttr("title", sponsor.empresa)} class="block rounded-lg border border-amber-200 bg-white p-4 grayscale shadow-sm transition hover:grayscale-0 hover:border-amber-400 hover:shadow-md"><img${ssrRenderAttr("src", sponsor.logo_url)}${ssrRenderAttr("alt", sponsor.empresa)} class="max-h-20 max-w-[200px] object-contain" loading="lazy"></a></div>`);
        });
        _push(`<!--]--></div></div></div></section>`);
      } else {
        _push(`<!---->`);
      }
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Components/SponsorsSlider.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as _
};
