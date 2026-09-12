import { ref, computed, watch, onMounted, onBeforeUnmount, mergeProps, unref, withCtx, createTextVNode, useSSRContext } from "vue";
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderAttr, ssrRenderClass, ssrInterpolate, ssrRenderList } from "vue/server-renderer";
import { router, Link } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Index",
  __ssrInlineRender: true,
  props: { pedidos: Array, caixas: Array },
  setup(__props) {
    const props = __props;
    const pontosPadrao = ["Cafe", "Bar 1", "Bar 2"];
    const pontoBar = ref("");
    let intervalo = null;
    const contas = computed(() => (props.pedidos ?? []).filter((p) => p.tipo === "bar_conta" && !["entregue", "cancelado"].includes(p.estado)));
    const prepagos = computed(() => (props.pedidos ?? []).filter((p) => p.tipo === "bar_prepago"));
    const totaisPorPonto = computed(() => Object.entries((props.pedidos ?? []).reduce((acc, pedido) => {
      const ponto = pedido.ponto_bar || "Sem ponto definido";
      acc[ponto] = (acc[ponto] || 0) + Number(pedido.total ?? pedido.total_calculado ?? 0);
      return acc;
    }, {})).map(([ponto, total2]) => ({ ponto, total: total2 })));
    const total = (pedido) => Number(pedido.total ?? pedido.total_calculado ?? 0).toFixed(2) + "€";
    const euros = (valor) => Number(valor ?? 0).toFixed(2) + "€";
    const hora = (data) => new Date(data).toLocaleTimeString("pt-PT", { hour: "2-digit", minute: "2-digit" });
    const caixaAberta = computed(() => (props.caixas ?? []).some((caixa) => caixa.ponto === pontoBar.value && caixa.estado === "aberta"));
    const caixasPorPonto = computed(() => Object.fromEntries((props.caixas ?? []).map((caixa) => [caixa.ponto, caixa])));
    const pontoQuery = computed(() => pontoBar.value ? { ponto: pontoBar.value } : {});
    watch(pontoBar, (valor) => localStorage.setItem("santana_ponto_bar", valor || ""));
    onMounted(() => {
      const params = new URLSearchParams(window.location.search);
      pontoBar.value = params.get("ponto") || localStorage.getItem("santana_ponto_bar") || "";
      intervalo = setInterval(() => router.reload({ only: ["pedidos", "caixas"], preserveScroll: true }), 2e4);
    });
    onBeforeUnmount(() => clearInterval(intervalo));
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "min-h-screen bg-gradient-to-br from-amber-50 via-orange-50 to-emerald-50 p-4 text-slate-950 sm:p-6" }, _attrs))}><header class="mb-6 rounded-3xl bg-white/85 p-4 shadow-sm"><div class="flex flex-wrap items-center justify-between gap-3"><div><h1 class="text-3xl font-black">Caixas - Senhas</h1><p class="text-sm font-semibold text-slate-500">Bebidas por senha impressa e contas dos balcões.</p></div><div class="flex gap-2">`);
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("caixa.index"),
        class: "rounded-2xl bg-slate-900 px-5 py-4 font-black text-white"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Voltar às Caixas`);
          } else {
            return [
              createTextVNode("Voltar às Caixas")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("bar.nova-conta", pontoQuery.value),
        class: "rounded-2xl bg-blue-600 px-5 py-4 font-black text-white"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Nova Conta`);
          } else {
            return [
              createTextVNode("Nova Conta")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(unref(Link), {
        href: _ctx.route("bar.prepago", pontoQuery.value),
        class: "rounded-2xl bg-emerald-600 px-5 py-4 font-black text-white"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`Pré-Pago`);
          } else {
            return [
              createTextVNode("Pré-Pago")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div><div class="mt-4 grid gap-3 rounded-2xl bg-slate-50 p-3 md:grid-cols-[1fr_auto] md:items-end"><label class="block font-black">Nome deste ponto de venda <input${ssrRenderAttr("value", pontoBar.value)} list="pontos-bar" class="mt-1 w-full rounded-xl border-slate-300 text-lg font-black" placeholder="Ex: Café Dia, Café Noite, Bar 1, Bar 2"></label><div class="${ssrRenderClass([pontoBar.value && caixaAberta.value ? "text-emerald-700" : "text-amber-700", "rounded-xl bg-white px-4 py-3 text-sm font-bold"])}">`);
      if (pontoBar.value && caixaAberta.value) {
        _push(`<span>Caixa aberta · fundo ${ssrInterpolate(euros(caixasPorPonto.value[pontoBar.value]?.fundo_maneio))}</span>`);
      } else {
        _push(`<span>Abre a caixa deste ponto antes de vender.</span>`);
      }
      _push(`</div><datalist id="pontos-bar"><!--[-->`);
      ssrRenderList(pontosPadrao, (ponto) => {
        _push(`<option${ssrRenderAttr("value", ponto)}></option>`);
      });
      _push(`<!--]--></datalist></div></header><section class="mb-5 rounded-3xl bg-white p-4 shadow-sm"><h2 class="mb-3 text-xl font-black">Dinheiro por ponto hoje</h2><div class="grid gap-3 md:grid-cols-4"><!--[-->`);
      ssrRenderList(totaisPorPonto.value, (linha) => {
        _push(`<div class="rounded-2xl bg-amber-50 p-4"><div class="font-black">${ssrInterpolate(linha.ponto)}</div><div class="text-2xl font-black text-amber-700">${ssrInterpolate(euros(linha.total))}</div></div>`);
      });
      _push(`<!--]--></div></section><div class="grid gap-5 lg:grid-cols-2"><section class="rounded-3xl bg-white p-4 shadow-sm"><h2 class="mb-4 text-xl font-black">Contas Abertas</h2>`);
      if (!contas.value.length) {
        _push(`<div class="rounded-2xl bg-slate-50 p-6 text-center font-semibold text-slate-500">Sem contas abertas.</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<!--[-->`);
      ssrRenderList(contas.value, (pedido) => {
        _push(`<div class="mb-3 rounded-2xl border border-blue-100 bg-blue-50 p-4"><div class="flex items-center justify-between gap-3"><div><div class="text-lg font-black">Conta #${ssrInterpolate(pedido.id)}</div><div class="text-sm text-slate-600">${ssrInterpolate(hora(pedido.created_at))} · ${ssrInterpolate(pedido.ponto_bar || "Sem ponto")} · ${ssrInterpolate(pedido.observacoes || "Sem identificação")}</div></div><div class="text-2xl font-black">${ssrInterpolate(total(pedido))}</div></div>`);
        _push(ssrRenderComponent(unref(Link), {
          href: _ctx.route("bar.show", pedido.id),
          class: "mt-3 block rounded-xl bg-blue-600 px-4 py-3 text-center font-black text-white"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`Ver Conta`);
            } else {
              return [
                createTextVNode("Ver Conta")
              ];
            }
          }),
          _: 2
        }, _parent));
        _push(`</div>`);
      });
      _push(`<!--]--></section><section class="rounded-3xl bg-white p-4 shadow-sm"><h2 class="mb-4 text-xl font-black">Pré-Pagos Hoje</h2>`);
      if (!prepagos.value.length) {
        _push(`<div class="rounded-2xl bg-slate-50 p-6 text-center font-semibold text-slate-500">Sem pré-pagos emitidos.</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<!--[-->`);
      ssrRenderList(prepagos.value, (pedido) => {
        _push(`<div class="mb-3 rounded-2xl border border-emerald-100 bg-emerald-50 p-4"><div class="flex items-center justify-between gap-3"><div><div class="text-2xl font-black">Senha #${ssrInterpolate(pedido.numero_senha)}</div><div class="text-sm text-slate-600">${ssrInterpolate(hora(pedido.created_at))} · ${ssrInterpolate(pedido.ponto_bar || "Sem ponto")} · ${ssrInterpolate(pedido.estado)}</div></div><div class="text-2xl font-black">${ssrInterpolate(total(pedido))}</div></div>`);
        _push(ssrRenderComponent(unref(Link), {
          href: _ctx.route("bar.talao", pedido.id),
          class: "mt-3 block rounded-xl bg-emerald-600 px-4 py-3 text-center font-black text-white"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`Talão`);
            } else {
              return [
                createTextVNode("Talão")
              ];
            }
          }),
          _: 2
        }, _parent));
        _push(`</div>`);
      });
      _push(`<!--]--></section></div></main>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Bar/Index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
