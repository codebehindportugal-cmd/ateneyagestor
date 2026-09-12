import { ref, computed, mergeProps, unref, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderList, ssrRenderAttr, ssrRenderClass, ssrInterpolate, ssrIncludeBooleanAttr } from "vue/server-renderer";
import { useForm } from "@inertiajs/vue3";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
const _sfc_main = {
  __name: "Login",
  __ssrInlineRender: true,
  props: { terminais: Array, tipoSelecionado: String },
  setup(__props) {
    const props = __props;
    const escolhido = ref(null);
    const form = useForm({ terminal_id: "", operador_nome: "", pin: "" });
    const posScreens = [
      { tipo: "restaurante", icon: "🍽️", label: "Restaurante" },
      { tipo: "reservas", icon: "📋", label: "Reservas" },
      { tipo: "bar", icon: "🍺", label: "Bares" },
      { tipo: "cafe", icon: "☕", label: "Café" },
      { tipo: "cotas", icon: "💳", label: "Cotas" }
    ];
    const ecras = [
      { href: route("ecra-reservas"), icon: "📺", label: "Ecrã Reservas", desc: "Ecrã de chamadas" },
      { href: route("patrocinios.ecra"), icon: "🏆", label: "Ecrã Patrocinadores", desc: "Painel de patrocinadores" },
      { href: route("precario"), icon: "📃", label: "Precário", desc: "Lista de preços" }
    ];
    const secoes = [
      { href: route("secao.bebidas"), label: "Bebidas" },
      { href: route("secao.frango"), label: "Frango" },
      { href: route("secao.comida"), label: "Comida" },
      { href: route("secao.cozinha"), label: "Cozinha" },
      { href: route("secao.sobremesas"), label: "Sobremesas" },
      { href: route("secao.acompanhamentos"), label: "Acompanhamentos" },
      { href: route("secao.servico"), label: "Serviço" },
      { href: route("secao.bar"), label: "Bar" }
    ];
    const tituloTipo = (tipo) => ({ bar: "Bar", cafe: "Café", restaurante: "Restaurante", reservas: "Reservas", cotas: "Cotas" })[tipo] || tipo;
    const terminal = computed(() => (props.terminais ?? []).find((item) => item.id === escolhido.value));
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "pos-hub min-h-screen text-white" }, _attrs))} data-v-174b12a4><header class="hub-header flex items-center justify-between border-b border-white/10 px-6 py-4" data-v-174b12a4><div class="flex items-center gap-3" data-v-174b12a4><img src="/images/santana-logo.png" alt="ARDC Santana" class="h-10 w-10 rounded-full border border-white/20 object-contain p-0.5" data-v-174b12a4><div data-v-174b12a4><div class="text-base font-black tracking-wide text-white" data-v-174b12a4>ARDC SANTANA</div><div class="text-xs font-bold uppercase tracking-widest text-white/50" data-v-174b12a4>Sistema de Gestão</div></div></div><div class="text-xs font-bold text-white/30" data-v-174b12a4>POS</div></header><div class="mx-auto max-w-5xl space-y-8 px-5 py-8" data-v-174b12a4><section data-v-174b12a4><h2 class="section-label" data-v-174b12a4>Terminais POS</h2><div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-5" data-v-174b12a4><!--[-->`);
      ssrRenderList(posScreens, (screen) => {
        _push(`<a${ssrRenderAttr("href", _ctx.route("pos.login", { tipo: screen.tipo }))} class="${ssrRenderClass([__props.tipoSelecionado === screen.tipo ? "hub-card-active" : "", "hub-card group flex flex-col items-center gap-2 rounded-xl p-5 text-center transition"])}" data-v-174b12a4><span class="text-3xl" data-v-174b12a4>${ssrInterpolate(screen.icon)}</span><span class="text-sm font-black uppercase tracking-wide" data-v-174b12a4>${ssrInterpolate(screen.label)}</span></a>`);
      });
      _push(`<!--]--></div></section>`);
      if (__props.terminais && __props.terminais.length) {
        _push(`<section data-v-174b12a4><h2 class="section-label" data-v-174b12a4>Selecionar Terminal — ${ssrInterpolate(tituloTipo(__props.tipoSelecionado))}</h2><div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3" data-v-174b12a4><!--[-->`);
        ssrRenderList(__props.terminais, (item) => {
          _push(`<button type="button" class="${ssrRenderClass([escolhido.value === item.id ? "hub-card-active" : "", "hub-card rounded-xl p-5 text-left transition"])}" data-v-174b12a4><div class="text-xl font-black" data-v-174b12a4>${ssrInterpolate(item.nome)}</div><div class="mt-1 text-sm font-bold text-white/60" data-v-174b12a4>${ssrInterpolate(item.localizacao)}</div></button>`);
        });
        _push(`<!--]--></div>`);
        if (terminal.value) {
          _push(`<form class="mx-auto mt-5 max-w-sm rounded-xl bg-white/5 p-6" data-v-174b12a4><h3 class="mb-4 text-center text-xl font-black" data-v-174b12a4>${ssrInterpolate(terminal.value.nome)}</h3><input${ssrRenderAttr("value", unref(form).operador_nome)} type="text" autocomplete="name" class="mb-3 w-full rounded-lg border-white/10 bg-white/5 p-4 text-center text-xl font-black text-white placeholder-white/30" placeholder="Nome de quem atende" data-v-174b12a4><input${ssrRenderAttr("value", unref(form).pin)} type="password" inputmode="numeric" autocomplete="off" autofocus class="w-full rounded-lg border-white/10 bg-white/5 p-4 text-center text-3xl font-black text-white placeholder-white/30" placeholder="PIN" data-v-174b12a4>`);
          if (unref(form).errors.operador_nome) {
            _push(`<div class="mt-3 rounded-lg bg-red-600/80 p-3 text-center font-bold" data-v-174b12a4>${ssrInterpolate(unref(form).errors.operador_nome)}</div>`);
          } else {
            _push(`<!---->`);
          }
          if (unref(form).errors.pin) {
            _push(`<div class="mt-3 rounded-lg bg-red-600/80 p-3 text-center font-bold" data-v-174b12a4>${ssrInterpolate(unref(form).errors.pin)}</div>`);
          } else {
            _push(`<!---->`);
          }
          _push(`<button class="mt-4 w-full rounded-xl bg-emerald-600 p-4 text-lg font-black disabled:opacity-50"${ssrIncludeBooleanAttr(unref(form).processing) ? " disabled" : ""} data-v-174b12a4>ENTRAR</button></form>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</section>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<section data-v-174b12a4><h2 class="section-label" data-v-174b12a4>Ecrãs</h2><div class="grid gap-3 sm:grid-cols-3" data-v-174b12a4><!--[-->`);
      ssrRenderList(ecras, (ecra) => {
        _push(`<a${ssrRenderAttr("href", ecra.href)} class="hub-card flex items-center gap-4 rounded-xl p-4 transition" data-v-174b12a4><span class="text-2xl" data-v-174b12a4>${ssrInterpolate(ecra.icon)}</span><div data-v-174b12a4><div class="font-black" data-v-174b12a4>${ssrInterpolate(ecra.label)}</div><div class="text-xs text-white/50" data-v-174b12a4>${ssrInterpolate(ecra.desc)}</div></div></a>`);
      });
      _push(`<!--]--></div></section><section data-v-174b12a4><h2 class="section-label" data-v-174b12a4>Secções</h2><div class="grid grid-cols-2 gap-2 sm:grid-cols-4" data-v-174b12a4><!--[-->`);
      ssrRenderList(secoes, (sec) => {
        _push(`<a${ssrRenderAttr("href", sec.href)} class="hub-card rounded-xl px-4 py-3 text-center text-sm font-black uppercase tracking-wide transition" data-v-174b12a4>${ssrInterpolate(sec.label)}</a>`);
      });
      _push(`<!--]--></div></section></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Pos/Login.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const Login = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-174b12a4"]]);
export {
  Login as default
};
