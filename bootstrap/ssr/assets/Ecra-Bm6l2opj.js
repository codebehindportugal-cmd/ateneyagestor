import { ref, computed, onMounted, onBeforeUnmount, mergeProps, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrInterpolate, ssrRenderClass, ssrRenderList } from "vue/server-renderer";
import { router } from "@inertiajs/vue3";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main = {
  __name: "Ecra",
  __ssrInlineRender: true,
  props: {
    chamadas: {
      type: Array,
      default: () => []
    }
  },
  setup(__props) {
    const props = __props;
    const agora = ref(/* @__PURE__ */ new Date());
    let relogio = null;
    let refresh = null;
    const principal = computed(() => props.chamadas[0] ?? null);
    const emEspera = computed(() => props.chamadas.slice(1));
    const horaReserva = (r) => r.hora?.slice(0, 5) ?? "--:--";
    const horaChamada = (r) => {
      if (!r.chamada_em) return "";
      return new Date(r.chamada_em).toLocaleTimeString("pt-PT", { hour: "2-digit", minute: "2-digit" });
    };
    const horaAtual = computed(
      () => agora.value.toLocaleTimeString("pt-PT", { hour: "2-digit", minute: "2-digit", second: "2-digit" })
    );
    onMounted(() => {
      relogio = setInterval(() => agora.value = /* @__PURE__ */ new Date(), 1e3);
      refresh = setInterval(() => router.reload({ preserveScroll: true }), 5e3);
    });
    onBeforeUnmount(() => {
      clearInterval(relogio);
      clearInterval(refresh);
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "ecra-chamadas flex min-h-screen flex-col items-center justify-between bg-gray-950 p-6 text-white" }, _attrs))} data-v-971af08a><header class="w-full max-w-5xl" data-v-971af08a><div class="flex items-center justify-between" data-v-971af08a><div data-v-971af08a><h1 class="text-3xl font-black tracking-widest text-amber-400 uppercase" data-v-971af08a>ARDC Santana</h1><p class="text-sm font-bold text-gray-500 uppercase tracking-widest" data-v-971af08a>Sistema de chamadas</p></div><div class="text-right" data-v-971af08a><p class="text-4xl font-black tabular-nums text-white" data-v-971af08a>${ssrInterpolate(horaAtual.value)}</p></div></div></header><section class="flex w-full max-w-5xl flex-1 flex-col items-center justify-center py-10" data-v-971af08a>`);
      if (!principal.value) {
        _push(`<div class="flex flex-col items-center gap-6 opacity-30" data-v-971af08a><svg class="h-24 w-24 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" data-v-971af08a><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" data-v-971af08a></path></svg><p class="text-4xl font-black text-gray-600 uppercase tracking-widest" data-v-971af08a>Aguardando chamadas...</p></div>`);
      } else {
        _push(`<!--[--><p class="mb-4 text-2xl font-black uppercase tracking-[0.3em] text-amber-400" data-v-971af08a>A CHAMAR</p><div class="mb-6 w-full rounded-3xl border-4 border-amber-400 bg-amber-400/10 px-10 py-10 text-center shadow-[0_0_80px_rgba(251,191,36,0.25)]" data-v-971af08a><p class="text-[clamp(3rem,10vw,7rem)] font-black uppercase leading-none tracking-tight text-white" data-v-971af08a>${ssrInterpolate(principal.value.nome)}</p><div class="mt-6 flex items-center justify-center gap-8 text-amber-300" data-v-971af08a><span class="text-4xl font-black" data-v-971af08a>${ssrInterpolate(principal.value.pessoas)} PESSOAS</span><span class="text-4xl font-black" data-v-971af08a>·</span><span class="text-4xl font-black" data-v-971af08a>${ssrInterpolate(horaReserva(principal.value))}</span></div><p class="mt-4 text-xl font-bold text-amber-500/70" data-v-971af08a>Chamada às ${ssrInterpolate(horaChamada(principal.value))}</p></div>`);
        if (emEspera.value.length) {
          _push(`<div class="w-full" data-v-971af08a><p class="mb-3 text-center text-lg font-black uppercase tracking-widest text-gray-500" data-v-971af08a>Também em espera</p><div class="${ssrRenderClass([emEspera.value.length === 1 ? "grid-cols-1 max-w-md mx-auto" : "grid-cols-2 sm:grid-cols-3", "grid gap-3"])}" data-v-971af08a><!--[-->`);
          ssrRenderList(emEspera.value, (r) => {
            _push(`<div class="rounded-2xl border-2 border-gray-700 bg-gray-900 px-5 py-4 text-center" data-v-971af08a><p class="truncate text-2xl font-black text-white" data-v-971af08a>${ssrInterpolate(r.nome)}</p><p class="mt-1 text-base font-bold text-gray-400" data-v-971af08a>${ssrInterpolate(r.pessoas)} PESSOAS · ${ssrInterpolate(horaReserva(r))}</p></div>`);
          });
          _push(`<!--]--></div></div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`<!--]-->`);
      }
      _push(`</section><footer class="w-full max-w-5xl" data-v-971af08a><p class="text-center text-sm font-bold text-gray-700 uppercase tracking-widest" data-v-971af08a> Dirija-se ao gestor de sala ao entrar </p></footer></main>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/PosReservas/Ecra.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const Ecra = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-971af08a"]]);
export {
  Ecra as default
};
