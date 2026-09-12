import { ref, computed, withCtx, unref, createTextVNode, createVNode, toDisplayString, openBlock, createBlock, createCommentVNode, withModifiers, withDirectives, vModelText, Fragment, renderList, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate, ssrRenderClass, ssrRenderAttr, ssrIncludeBooleanAttr, ssrRenderList } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import { useForm, Link } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Index",
  __ssrInlineRender: true,
  props: {
    data: String,
    pontos_padrao: Array,
    caixas: Array
  },
  setup(__props) {
    const props = __props;
    const form = useForm({
      ponto: props.pontos_padrao?.[0] ?? "Restaurante",
      fundo_maneio: 0
    });
    const fecharForm = useForm({
      valor_contado: 0,
      observacoes_fecho: ""
    });
    const caixaAFechar = ref(null);
    const caixasPorPonto = computed(() => Object.fromEntries((props.caixas ?? []).map((caixa) => [caixa.ponto, caixa])));
    const restaurante = computed(() => caixasPorPonto.value.Restaurante ?? null);
    const pontosBar = computed(() => (props.pontos_padrao ?? []).filter((ponto) => ponto !== "Restaurante"));
    const totalFundo = computed(() => (props.caixas ?? []).reduce((total, caixa) => total + Number(caixa.fundo_maneio || 0), 0));
    const totalVendas = computed(() => (props.caixas ?? []).reduce((total, caixa) => total + Number(caixa.vendas || 0), 0));
    const totalEsperado = computed(() => (props.caixas ?? []).reduce((total, caixa) => total + Number(caixa.esperado_caixa || 0), 0));
    const totalContado = computed(() => (props.caixas ?? []).reduce((total, caixa) => total + Number(caixa.valor_contado || 0), 0));
    const euros = (valor) => Number(valor ?? 0).toFixed(2) + "€";
    const hora = (data) => data ? new Date(data).toLocaleTimeString("pt-PT", { hour: "2-digit", minute: "2-digit" }) : "";
    const diferencaClass = (valor) => Number(valor || 0) === 0 ? "text-slate-700" : Number(valor) > 0 ? "text-emerald-700" : "text-red-700";
    const estadoLabel = (caixa) => !caixa ? "FALTA ABRIR" : caixa.estado === "fechada" ? "FECHADA" : "ABERTA";
    const estadoClasses = (caixa) => !caixa ? "bg-amber-100 text-amber-800" : caixa.estado === "fechada" ? "bg-slate-200 text-slate-700" : "bg-emerald-100 text-emerald-800";
    const prepararAbertura = (ponto) => {
      form.ponto = ponto;
      form.fundo_maneio = caixasPorPonto.value[ponto]?.fundo_maneio ?? 0;
    };
    const abrirCaixa = () => {
      form.post(route("caixa.store"), {
        preserveScroll: true,
        onSuccess: () => form.reset("fundo_maneio")
      });
    };
    const prepararFecho = (caixa) => {
      caixaAFechar.value = caixa.id;
      fecharForm.valor_contado = Number(caixa.esperado_caixa || 0).toFixed(2);
      fecharForm.observacoes_fecho = "";
    };
    const cancelarFecho = () => {
      caixaAFechar.value = null;
      fecharForm.reset();
    };
    const fecharCaixa = (caixa) => {
      fecharForm.patch(route("caixa.fechar", caixa.id), {
        preserveScroll: true,
        onSuccess: cancelarFecho
      });
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mb-6 overflow-hidden rounded-[2rem] bg-slate-950 p-6 text-white shadow-sm lg:p-8"${_scopeId}><div class="flex flex-wrap items-start justify-between gap-4"${_scopeId}><div${_scopeId}><p class="text-sm font-black uppercase tracking-[0.3em] text-amber-300"${_scopeId}>${ssrInterpolate(__props.data)}</p><h1 class="mt-3 text-4xl font-black tracking-tight"${_scopeId}>Caixas</h1><p class="mt-2 max-w-2xl text-sm font-semibold text-slate-300"${_scopeId}>${ssrInterpolate(pontosBar.value.length ? "Abre o Restaurante para trabalhar contas de mesa. Abre os cafés/bares para vender por senha e controlar trocos." : "Abre o Restaurante para trabalhar contas de mesa e controlar o fecho de caixa.")}</p></div><div class="grid grid-cols-2 gap-2 text-right text-sm md:grid-cols-4"${_scopeId}><div class="rounded-2xl bg-white/10 p-3"${_scopeId}><div class="text-slate-300"${_scopeId}>Fundo</div><strong class="text-lg"${_scopeId}>${ssrInterpolate(euros(totalFundo.value))}</strong></div><div class="rounded-2xl bg-white/10 p-3"${_scopeId}><div class="text-slate-300"${_scopeId}>Vendas</div><strong class="text-lg"${_scopeId}>${ssrInterpolate(euros(totalVendas.value))}</strong></div><div class="rounded-2xl bg-emerald-400 p-3 text-emerald-950"${_scopeId}><div${_scopeId}>Esperado</div><strong class="text-lg"${_scopeId}>${ssrInterpolate(euros(totalEsperado.value))}</strong></div><div class="rounded-2xl bg-white p-3 text-slate-950"${_scopeId}><div${_scopeId}>Contado</div><strong class="text-lg"${_scopeId}>${ssrInterpolate(euros(totalContado.value))}</strong></div></div></div></div><div class="grid gap-6 xl:grid-cols-[1fr_360px]"${_scopeId}><div class="space-y-6"${_scopeId}><section class="overflow-hidden rounded-[2rem] bg-white shadow-sm ring-1 ring-slate-200"${_scopeId}><div class="bg-gradient-to-r from-slate-900 to-slate-700 p-5 text-white"${_scopeId}><div class="flex flex-wrap items-start justify-between gap-4"${_scopeId}><div${_scopeId}><p class="text-xs font-black uppercase tracking-[0.25em] text-amber-300"${_scopeId}>Restaurante</p><h2 class="mt-2 text-3xl font-black"${_scopeId}>Contas de mesa</h2><p class="mt-1 text-sm text-slate-300"${_scopeId}>Usa esta caixa para abrir mesas, receber contas e fechar o dia do restaurante.</p></div><span class="${ssrRenderClass([estadoClasses(restaurante.value), "rounded-full px-4 py-2 text-xs font-black"])}"${_scopeId}>${ssrInterpolate(estadoLabel(restaurante.value))}</span></div></div><div class="grid gap-4 p-5 lg:grid-cols-[1fr_280px]"${_scopeId}>`);
            if (restaurante.value) {
              _push2(`<div class="grid gap-3 sm:grid-cols-4"${_scopeId}><div class="rounded-2xl bg-slate-50 p-4"${_scopeId}><div class="text-xs font-bold uppercase text-slate-500"${_scopeId}>Fundo</div><strong class="text-2xl"${_scopeId}>${ssrInterpolate(euros(restaurante.value.fundo_maneio))}</strong></div><div class="rounded-2xl bg-slate-50 p-4"${_scopeId}><div class="text-xs font-bold uppercase text-slate-500"${_scopeId}>Vendas</div><strong class="text-2xl"${_scopeId}>${ssrInterpolate(euros(restaurante.value.vendas))}</strong></div><div class="rounded-2xl bg-emerald-50 p-4"${_scopeId}><div class="text-xs font-bold uppercase text-emerald-700"${_scopeId}>Esperado</div><strong class="text-2xl text-emerald-800"${_scopeId}>${ssrInterpolate(euros(restaurante.value.esperado_caixa))}</strong></div><div class="rounded-2xl bg-slate-50 p-4"${_scopeId}><div class="text-xs font-bold uppercase text-slate-500"${_scopeId}>Pedidos</div><strong class="text-2xl"${_scopeId}>${ssrInterpolate(restaurante.value.pedidos)}</strong></div></div>`);
            } else {
              _push2(`<div class="rounded-2xl bg-amber-50 p-5 font-bold text-amber-800"${_scopeId}>Abre primeiro a caixa do Restaurante para poderes abrir contas de mesa.</div>`);
            }
            _push2(`<div class="grid gap-2"${_scopeId}><button type="button" class="rounded-2xl border border-slate-300 px-4 py-3 font-black"${_scopeId}>${ssrInterpolate(restaurante.value ? "Reabrir / ajustar fundo" : "Abrir Restaurante")}</button>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("mesas.index"),
              class: "rounded-2xl bg-slate-900 px-4 py-3 text-center font-black text-white"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Ir para mesas`);
                } else {
                  return [
                    createTextVNode("Ir para mesas")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("pedidos.create", { para_levar: 1 }),
              class: "rounded-2xl bg-emerald-600 px-4 py-3 text-center font-black text-white"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Pedido para levar`);
                } else {
                  return [
                    createTextVNode("Pedido para levar")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("pedidos.index"),
              class: "rounded-2xl bg-white px-4 py-3 text-center font-black text-slate-900 ring-1 ring-slate-300"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Ver contas`);
                } else {
                  return [
                    createTextVNode("Ver contas")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div></div>`);
            if (restaurante.value?.estado === "fechada") {
              _push2(`<div class="mx-5 mb-5 rounded-2xl bg-slate-50 p-4 text-sm"${_scopeId}><div class="flex justify-between"${_scopeId}><span${_scopeId}>Contado</span><strong${_scopeId}>${ssrInterpolate(euros(restaurante.value.valor_contado))}</strong></div><div class="flex justify-between"${_scopeId}><span${_scopeId}>Diferença</span><strong class="${ssrRenderClass(diferencaClass(restaurante.value.diferenca))}"${_scopeId}>${ssrInterpolate(euros(restaurante.value.diferenca))}</strong></div></div>`);
            } else {
              _push2(`<!---->`);
            }
            if (restaurante.value?.estado === "aberta" && caixaAFechar.value !== restaurante.value.id) {
              _push2(`<div class="border-t border-slate-100 p-5"${_scopeId}><button type="button" class="w-full rounded-2xl bg-red-600 px-4 py-4 font-black text-white"${_scopeId}>Fechar Restaurante</button></div>`);
            } else {
              _push2(`<!---->`);
            }
            if (caixaAFechar.value === restaurante.value?.id) {
              _push2(`<form class="border-t border-slate-100 bg-slate-50 p-5"${_scopeId}><div class="grid gap-3 md:grid-cols-[180px_1fr_auto_auto] md:items-end"${_scopeId}><label class="block text-sm font-bold text-slate-600"${_scopeId}>Valor contado <input${ssrRenderAttr("value", unref(fecharForm).valor_contado)} type="number" min="0" step="0.01" class="mt-1 w-full rounded-xl border-slate-300 text-xl font-black"${_scopeId}></label><label class="block text-sm font-bold text-slate-600"${_scopeId}>Observações <input${ssrRenderAttr("value", unref(fecharForm).observacoes_fecho)} class="mt-1 w-full rounded-xl border-slate-300" placeholder="Opcional"${_scopeId}></label><button type="button" class="rounded-xl border border-slate-300 px-4 py-3 font-bold"${_scopeId}>Cancelar</button><button class="rounded-xl bg-red-600 px-4 py-3 font-black text-white disabled:opacity-50"${ssrIncludeBooleanAttr(unref(fecharForm).processing) ? " disabled" : ""}${_scopeId}>Confirmar fecho</button></div></form>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</section>`);
            if (pontosBar.value.length) {
              _push2(`<section class="rounded-[2rem] bg-white p-5 shadow-sm ring-1 ring-slate-200"${_scopeId}><div class="mb-4 flex flex-wrap items-center justify-between gap-3"${_scopeId}><div${_scopeId}><p class="text-xs font-black uppercase tracking-[0.25em] text-emerald-700"${_scopeId}>Senhas impressas</p><h2 class="text-2xl font-black"${_scopeId}>Cafés e bares</h2></div></div><div class="grid gap-4 md:grid-cols-2"${_scopeId}><!--[-->`);
              ssrRenderList(pontosBar.value, (ponto) => {
                _push2(`<article class="${ssrRenderClass([caixasPorPonto.value[ponto] ? caixasPorPonto.value[ponto].estado === "fechada" ? "border-slate-200 bg-slate-50" : "border-emerald-200 bg-emerald-50" : "border-amber-200 bg-amber-50", "rounded-3xl border p-4"])}"${_scopeId}><div class="flex items-start justify-between gap-3"${_scopeId}><div${_scopeId}><h3 class="text-xl font-black"${_scopeId}>${ssrInterpolate(ponto)}</h3>`);
                if (caixasPorPonto.value[ponto]?.estado === "fechada") {
                  _push2(`<p class="text-sm font-bold text-slate-600"${_scopeId}>Fechado às ${ssrInterpolate(hora(caixasPorPonto.value[ponto].fechado_as))}</p>`);
                } else if (caixasPorPonto.value[ponto]) {
                  _push2(`<p class="text-sm font-bold text-emerald-700"${_scopeId}>Aberto às ${ssrInterpolate(hora(caixasPorPonto.value[ponto].aberto_as))}</p>`);
                } else {
                  _push2(`<p class="text-sm font-bold text-amber-700"${_scopeId}>Ainda não aberto</p>`);
                }
                _push2(`</div><span class="${ssrRenderClass([estadoClasses(caixasPorPonto.value[ponto]), "rounded-full px-3 py-1 text-xs font-black"])}"${_scopeId}>${ssrInterpolate(estadoLabel(caixasPorPonto.value[ponto]))}</span></div>`);
                if (caixasPorPonto.value[ponto]) {
                  _push2(`<div class="mt-4 grid grid-cols-3 gap-2 text-sm"${_scopeId}><div class="rounded-xl bg-white/80 p-3"${_scopeId}><div class="text-slate-500"${_scopeId}>Fundo</div><strong${_scopeId}>${ssrInterpolate(euros(caixasPorPonto.value[ponto].fundo_maneio))}</strong></div><div class="rounded-xl bg-white/80 p-3"${_scopeId}><div class="text-slate-500"${_scopeId}>Vendas</div><strong${_scopeId}>${ssrInterpolate(euros(caixasPorPonto.value[ponto].vendas))}</strong></div><div class="rounded-xl bg-white/80 p-3"${_scopeId}><div class="text-slate-500"${_scopeId}>Esperado</div><strong${_scopeId}>${ssrInterpolate(euros(caixasPorPonto.value[ponto].esperado_caixa))}</strong></div></div>`);
                } else {
                  _push2(`<!---->`);
                }
                if (caixasPorPonto.value[ponto]?.estado === "fechada") {
                  _push2(`<div class="mt-3 rounded-xl bg-white/80 p-3 text-sm"${_scopeId}><div class="flex justify-between"${_scopeId}><span${_scopeId}>Contado</span><strong${_scopeId}>${ssrInterpolate(euros(caixasPorPonto.value[ponto].valor_contado))}</strong></div><div class="flex justify-between"${_scopeId}><span${_scopeId}>Diferença</span><strong class="${ssrRenderClass(diferencaClass(caixasPorPonto.value[ponto].diferenca))}"${_scopeId}>${ssrInterpolate(euros(caixasPorPonto.value[ponto].diferenca))}</strong></div></div>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`<div class="${ssrRenderClass([caixasPorPonto.value[ponto]?.estado === "aberta" ? "grid-cols-3" : "grid-cols-2", "mt-4 grid gap-2"])}"${_scopeId}><button type="button" class="rounded-xl border border-slate-300 px-3 py-2 font-bold"${_scopeId}>${ssrInterpolate(caixasPorPonto.value[ponto] ? "Ajustar" : "Abrir")}</button>`);
                if (caixasPorPonto.value[ponto]?.estado === "aberta") {
                  _push2(ssrRenderComponent(unref(Link), {
                    href: _ctx.route("bar.index", { ponto }),
                    class: "rounded-xl bg-emerald-600 px-3 py-2 text-center font-black text-white"
                  }, {
                    default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(`Vender senhas`);
                      } else {
                        return [
                          createTextVNode("Vender senhas")
                        ];
                      }
                    }),
                    _: 2
                  }, _parent2, _scopeId));
                } else {
                  _push2(`<!---->`);
                }
                if (caixasPorPonto.value[ponto]?.estado === "aberta" && caixaAFechar.value !== caixasPorPonto.value[ponto].id) {
                  _push2(`<button type="button" class="rounded-xl bg-slate-900 px-3 py-2 font-black text-white"${_scopeId}>Fechar</button>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</div>`);
                if (caixaAFechar.value === caixasPorPonto.value[ponto]?.id) {
                  _push2(`<form class="mt-4 rounded-xl bg-white p-3"${_scopeId}><label class="block text-sm font-bold text-slate-600"${_scopeId}>Valor contado <input${ssrRenderAttr("value", unref(fecharForm).valor_contado)} type="number" min="0" step="0.01" class="mt-1 w-full rounded-xl border-slate-300 text-xl font-black"${_scopeId}></label><label class="mt-3 block text-sm font-bold text-slate-600"${_scopeId}>Observações <textarea class="mt-1 w-full rounded-xl border-slate-300" rows="2" placeholder="Opcional"${_scopeId}>${ssrInterpolate(unref(fecharForm).observacoes_fecho)}</textarea></label><div class="mt-3 grid grid-cols-2 gap-2"${_scopeId}><button type="button" class="rounded-xl border border-slate-300 px-3 py-2 font-bold"${_scopeId}>Cancelar</button><button class="rounded-xl bg-red-600 px-3 py-2 font-black text-white disabled:opacity-50"${ssrIncludeBooleanAttr(unref(fecharForm).processing) ? " disabled" : ""}${_scopeId}>Confirmar</button></div></form>`);
                } else {
                  _push2(`<!---->`);
                }
                _push2(`</article>`);
              });
              _push2(`<!--]--></div></section>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div><form class="sticky top-6 self-start rounded-[2rem] bg-white p-5 shadow-sm ring-1 ring-slate-200"${_scopeId}><p class="text-xs font-black uppercase tracking-[0.25em] text-slate-500"${_scopeId}>Abertura</p><h2 class="mt-2 text-2xl font-black"${_scopeId}>Abrir / reabrir caixa</h2><label class="mt-5 block text-sm font-bold text-slate-600"${_scopeId}>Ponto <input${ssrRenderAttr("value", unref(form).ponto)} list="pontos-caixa" class="mt-1 w-full rounded-xl border-slate-300 text-lg font-black"${_scopeId}></label><datalist id="pontos-caixa"${_scopeId}><!--[-->`);
            ssrRenderList(__props.pontos_padrao, (ponto) => {
              _push2(`<option${ssrRenderAttr("value", ponto)}${_scopeId}></option>`);
            });
            _push2(`<!--]--></datalist><label class="mt-4 block text-sm font-bold text-slate-600"${_scopeId}>Fundo de maneio <input${ssrRenderAttr("value", unref(form).fundo_maneio)} type="number" min="0" step="0.01" class="mt-1 w-full rounded-xl border-slate-300 text-2xl font-black" placeholder="0.00"${_scopeId}></label>`);
            if (Object.keys(unref(form).errors).length) {
              _push2(`<div class="mt-4 rounded-xl bg-red-50 p-3 text-sm font-bold text-red-700"${_scopeId}><!--[-->`);
              ssrRenderList(unref(form).errors, (erro) => {
                _push2(`<div${_scopeId}>${ssrInterpolate(erro)}</div>`);
              });
              _push2(`<!--]--></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<button class="mt-5 w-full rounded-2xl bg-slate-900 p-4 font-black text-white disabled:opacity-50"${ssrIncludeBooleanAttr(unref(form).processing) ? " disabled" : ""}${_scopeId}>${ssrInterpolate(unref(form).processing ? "A guardar..." : "Guardar abertura")}</button><p class="mt-3 text-xs text-slate-500"${_scopeId}>Se uma caixa estiver fechada, reabrir limpa o fecho e permite continuar a trabalhar nesse ponto.</p></form></div>`);
          } else {
            return [
              createVNode("div", { class: "mb-6 overflow-hidden rounded-[2rem] bg-slate-950 p-6 text-white shadow-sm lg:p-8" }, [
                createVNode("div", { class: "flex flex-wrap items-start justify-between gap-4" }, [
                  createVNode("div", null, [
                    createVNode("p", { class: "text-sm font-black uppercase tracking-[0.3em] text-amber-300" }, toDisplayString(__props.data), 1),
                    createVNode("h1", { class: "mt-3 text-4xl font-black tracking-tight" }, "Caixas"),
                    createVNode("p", { class: "mt-2 max-w-2xl text-sm font-semibold text-slate-300" }, toDisplayString(pontosBar.value.length ? "Abre o Restaurante para trabalhar contas de mesa. Abre os cafés/bares para vender por senha e controlar trocos." : "Abre o Restaurante para trabalhar contas de mesa e controlar o fecho de caixa."), 1)
                  ]),
                  createVNode("div", { class: "grid grid-cols-2 gap-2 text-right text-sm md:grid-cols-4" }, [
                    createVNode("div", { class: "rounded-2xl bg-white/10 p-3" }, [
                      createVNode("div", { class: "text-slate-300" }, "Fundo"),
                      createVNode("strong", { class: "text-lg" }, toDisplayString(euros(totalFundo.value)), 1)
                    ]),
                    createVNode("div", { class: "rounded-2xl bg-white/10 p-3" }, [
                      createVNode("div", { class: "text-slate-300" }, "Vendas"),
                      createVNode("strong", { class: "text-lg" }, toDisplayString(euros(totalVendas.value)), 1)
                    ]),
                    createVNode("div", { class: "rounded-2xl bg-emerald-400 p-3 text-emerald-950" }, [
                      createVNode("div", null, "Esperado"),
                      createVNode("strong", { class: "text-lg" }, toDisplayString(euros(totalEsperado.value)), 1)
                    ]),
                    createVNode("div", { class: "rounded-2xl bg-white p-3 text-slate-950" }, [
                      createVNode("div", null, "Contado"),
                      createVNode("strong", { class: "text-lg" }, toDisplayString(euros(totalContado.value)), 1)
                    ])
                  ])
                ])
              ]),
              createVNode("div", { class: "grid gap-6 xl:grid-cols-[1fr_360px]" }, [
                createVNode("div", { class: "space-y-6" }, [
                  createVNode("section", { class: "overflow-hidden rounded-[2rem] bg-white shadow-sm ring-1 ring-slate-200" }, [
                    createVNode("div", { class: "bg-gradient-to-r from-slate-900 to-slate-700 p-5 text-white" }, [
                      createVNode("div", { class: "flex flex-wrap items-start justify-between gap-4" }, [
                        createVNode("div", null, [
                          createVNode("p", { class: "text-xs font-black uppercase tracking-[0.25em] text-amber-300" }, "Restaurante"),
                          createVNode("h2", { class: "mt-2 text-3xl font-black" }, "Contas de mesa"),
                          createVNode("p", { class: "mt-1 text-sm text-slate-300" }, "Usa esta caixa para abrir mesas, receber contas e fechar o dia do restaurante.")
                        ]),
                        createVNode("span", {
                          class: ["rounded-full px-4 py-2 text-xs font-black", estadoClasses(restaurante.value)]
                        }, toDisplayString(estadoLabel(restaurante.value)), 3)
                      ])
                    ]),
                    createVNode("div", { class: "grid gap-4 p-5 lg:grid-cols-[1fr_280px]" }, [
                      restaurante.value ? (openBlock(), createBlock("div", {
                        key: 0,
                        class: "grid gap-3 sm:grid-cols-4"
                      }, [
                        createVNode("div", { class: "rounded-2xl bg-slate-50 p-4" }, [
                          createVNode("div", { class: "text-xs font-bold uppercase text-slate-500" }, "Fundo"),
                          createVNode("strong", { class: "text-2xl" }, toDisplayString(euros(restaurante.value.fundo_maneio)), 1)
                        ]),
                        createVNode("div", { class: "rounded-2xl bg-slate-50 p-4" }, [
                          createVNode("div", { class: "text-xs font-bold uppercase text-slate-500" }, "Vendas"),
                          createVNode("strong", { class: "text-2xl" }, toDisplayString(euros(restaurante.value.vendas)), 1)
                        ]),
                        createVNode("div", { class: "rounded-2xl bg-emerald-50 p-4" }, [
                          createVNode("div", { class: "text-xs font-bold uppercase text-emerald-700" }, "Esperado"),
                          createVNode("strong", { class: "text-2xl text-emerald-800" }, toDisplayString(euros(restaurante.value.esperado_caixa)), 1)
                        ]),
                        createVNode("div", { class: "rounded-2xl bg-slate-50 p-4" }, [
                          createVNode("div", { class: "text-xs font-bold uppercase text-slate-500" }, "Pedidos"),
                          createVNode("strong", { class: "text-2xl" }, toDisplayString(restaurante.value.pedidos), 1)
                        ])
                      ])) : (openBlock(), createBlock("div", {
                        key: 1,
                        class: "rounded-2xl bg-amber-50 p-5 font-bold text-amber-800"
                      }, "Abre primeiro a caixa do Restaurante para poderes abrir contas de mesa.")),
                      createVNode("div", { class: "grid gap-2" }, [
                        createVNode("button", {
                          type: "button",
                          class: "rounded-2xl border border-slate-300 px-4 py-3 font-black",
                          onClick: ($event) => prepararAbertura("Restaurante")
                        }, toDisplayString(restaurante.value ? "Reabrir / ajustar fundo" : "Abrir Restaurante"), 9, ["onClick"]),
                        createVNode(unref(Link), {
                          href: _ctx.route("mesas.index"),
                          class: "rounded-2xl bg-slate-900 px-4 py-3 text-center font-black text-white"
                        }, {
                          default: withCtx(() => [
                            createTextVNode("Ir para mesas")
                          ]),
                          _: 1
                        }, 8, ["href"]),
                        createVNode(unref(Link), {
                          href: _ctx.route("pedidos.create", { para_levar: 1 }),
                          class: "rounded-2xl bg-emerald-600 px-4 py-3 text-center font-black text-white"
                        }, {
                          default: withCtx(() => [
                            createTextVNode("Pedido para levar")
                          ]),
                          _: 1
                        }, 8, ["href"]),
                        createVNode(unref(Link), {
                          href: _ctx.route("pedidos.index"),
                          class: "rounded-2xl bg-white px-4 py-3 text-center font-black text-slate-900 ring-1 ring-slate-300"
                        }, {
                          default: withCtx(() => [
                            createTextVNode("Ver contas")
                          ]),
                          _: 1
                        }, 8, ["href"])
                      ])
                    ]),
                    restaurante.value?.estado === "fechada" ? (openBlock(), createBlock("div", {
                      key: 0,
                      class: "mx-5 mb-5 rounded-2xl bg-slate-50 p-4 text-sm"
                    }, [
                      createVNode("div", { class: "flex justify-between" }, [
                        createVNode("span", null, "Contado"),
                        createVNode("strong", null, toDisplayString(euros(restaurante.value.valor_contado)), 1)
                      ]),
                      createVNode("div", { class: "flex justify-between" }, [
                        createVNode("span", null, "Diferença"),
                        createVNode("strong", {
                          class: diferencaClass(restaurante.value.diferenca)
                        }, toDisplayString(euros(restaurante.value.diferenca)), 3)
                      ])
                    ])) : createCommentVNode("", true),
                    restaurante.value?.estado === "aberta" && caixaAFechar.value !== restaurante.value.id ? (openBlock(), createBlock("div", {
                      key: 1,
                      class: "border-t border-slate-100 p-5"
                    }, [
                      createVNode("button", {
                        type: "button",
                        class: "w-full rounded-2xl bg-red-600 px-4 py-4 font-black text-white",
                        onClick: ($event) => prepararFecho(restaurante.value)
                      }, "Fechar Restaurante", 8, ["onClick"])
                    ])) : createCommentVNode("", true),
                    caixaAFechar.value === restaurante.value?.id ? (openBlock(), createBlock("form", {
                      key: 2,
                      class: "border-t border-slate-100 bg-slate-50 p-5",
                      onSubmit: withModifiers(($event) => fecharCaixa(restaurante.value), ["prevent"])
                    }, [
                      createVNode("div", { class: "grid gap-3 md:grid-cols-[180px_1fr_auto_auto] md:items-end" }, [
                        createVNode("label", { class: "block text-sm font-bold text-slate-600" }, [
                          createTextVNode("Valor contado "),
                          withDirectives(createVNode("input", {
                            "onUpdate:modelValue": ($event) => unref(fecharForm).valor_contado = $event,
                            type: "number",
                            min: "0",
                            step: "0.01",
                            class: "mt-1 w-full rounded-xl border-slate-300 text-xl font-black"
                          }, null, 8, ["onUpdate:modelValue"]), [
                            [
                              vModelText,
                              unref(fecharForm).valor_contado,
                              void 0,
                              { number: true }
                            ]
                          ])
                        ]),
                        createVNode("label", { class: "block text-sm font-bold text-slate-600" }, [
                          createTextVNode("Observações "),
                          withDirectives(createVNode("input", {
                            "onUpdate:modelValue": ($event) => unref(fecharForm).observacoes_fecho = $event,
                            class: "mt-1 w-full rounded-xl border-slate-300",
                            placeholder: "Opcional"
                          }, null, 8, ["onUpdate:modelValue"]), [
                            [vModelText, unref(fecharForm).observacoes_fecho]
                          ])
                        ]),
                        createVNode("button", {
                          type: "button",
                          class: "rounded-xl border border-slate-300 px-4 py-3 font-bold",
                          onClick: cancelarFecho
                        }, "Cancelar"),
                        createVNode("button", {
                          class: "rounded-xl bg-red-600 px-4 py-3 font-black text-white disabled:opacity-50",
                          disabled: unref(fecharForm).processing
                        }, "Confirmar fecho", 8, ["disabled"])
                      ])
                    ], 40, ["onSubmit"])) : createCommentVNode("", true)
                  ]),
                  pontosBar.value.length ? (openBlock(), createBlock("section", {
                    key: 0,
                    class: "rounded-[2rem] bg-white p-5 shadow-sm ring-1 ring-slate-200"
                  }, [
                    createVNode("div", { class: "mb-4 flex flex-wrap items-center justify-between gap-3" }, [
                      createVNode("div", null, [
                        createVNode("p", { class: "text-xs font-black uppercase tracking-[0.25em] text-emerald-700" }, "Senhas impressas"),
                        createVNode("h2", { class: "text-2xl font-black" }, "Cafés e bares")
                      ])
                    ]),
                    createVNode("div", { class: "grid gap-4 md:grid-cols-2" }, [
                      (openBlock(true), createBlock(Fragment, null, renderList(pontosBar.value, (ponto) => {
                        return openBlock(), createBlock("article", {
                          key: ponto,
                          class: ["rounded-3xl border p-4", caixasPorPonto.value[ponto] ? caixasPorPonto.value[ponto].estado === "fechada" ? "border-slate-200 bg-slate-50" : "border-emerald-200 bg-emerald-50" : "border-amber-200 bg-amber-50"]
                        }, [
                          createVNode("div", { class: "flex items-start justify-between gap-3" }, [
                            createVNode("div", null, [
                              createVNode("h3", { class: "text-xl font-black" }, toDisplayString(ponto), 1),
                              caixasPorPonto.value[ponto]?.estado === "fechada" ? (openBlock(), createBlock("p", {
                                key: 0,
                                class: "text-sm font-bold text-slate-600"
                              }, "Fechado às " + toDisplayString(hora(caixasPorPonto.value[ponto].fechado_as)), 1)) : caixasPorPonto.value[ponto] ? (openBlock(), createBlock("p", {
                                key: 1,
                                class: "text-sm font-bold text-emerald-700"
                              }, "Aberto às " + toDisplayString(hora(caixasPorPonto.value[ponto].aberto_as)), 1)) : (openBlock(), createBlock("p", {
                                key: 2,
                                class: "text-sm font-bold text-amber-700"
                              }, "Ainda não aberto"))
                            ]),
                            createVNode("span", {
                              class: ["rounded-full px-3 py-1 text-xs font-black", estadoClasses(caixasPorPonto.value[ponto])]
                            }, toDisplayString(estadoLabel(caixasPorPonto.value[ponto])), 3)
                          ]),
                          caixasPorPonto.value[ponto] ? (openBlock(), createBlock("div", {
                            key: 0,
                            class: "mt-4 grid grid-cols-3 gap-2 text-sm"
                          }, [
                            createVNode("div", { class: "rounded-xl bg-white/80 p-3" }, [
                              createVNode("div", { class: "text-slate-500" }, "Fundo"),
                              createVNode("strong", null, toDisplayString(euros(caixasPorPonto.value[ponto].fundo_maneio)), 1)
                            ]),
                            createVNode("div", { class: "rounded-xl bg-white/80 p-3" }, [
                              createVNode("div", { class: "text-slate-500" }, "Vendas"),
                              createVNode("strong", null, toDisplayString(euros(caixasPorPonto.value[ponto].vendas)), 1)
                            ]),
                            createVNode("div", { class: "rounded-xl bg-white/80 p-3" }, [
                              createVNode("div", { class: "text-slate-500" }, "Esperado"),
                              createVNode("strong", null, toDisplayString(euros(caixasPorPonto.value[ponto].esperado_caixa)), 1)
                            ])
                          ])) : createCommentVNode("", true),
                          caixasPorPonto.value[ponto]?.estado === "fechada" ? (openBlock(), createBlock("div", {
                            key: 1,
                            class: "mt-3 rounded-xl bg-white/80 p-3 text-sm"
                          }, [
                            createVNode("div", { class: "flex justify-between" }, [
                              createVNode("span", null, "Contado"),
                              createVNode("strong", null, toDisplayString(euros(caixasPorPonto.value[ponto].valor_contado)), 1)
                            ]),
                            createVNode("div", { class: "flex justify-between" }, [
                              createVNode("span", null, "Diferença"),
                              createVNode("strong", {
                                class: diferencaClass(caixasPorPonto.value[ponto].diferenca)
                              }, toDisplayString(euros(caixasPorPonto.value[ponto].diferenca)), 3)
                            ])
                          ])) : createCommentVNode("", true),
                          createVNode("div", {
                            class: ["mt-4 grid gap-2", caixasPorPonto.value[ponto]?.estado === "aberta" ? "grid-cols-3" : "grid-cols-2"]
                          }, [
                            createVNode("button", {
                              type: "button",
                              class: "rounded-xl border border-slate-300 px-3 py-2 font-bold",
                              onClick: ($event) => prepararAbertura(ponto)
                            }, toDisplayString(caixasPorPonto.value[ponto] ? "Ajustar" : "Abrir"), 9, ["onClick"]),
                            caixasPorPonto.value[ponto]?.estado === "aberta" ? (openBlock(), createBlock(unref(Link), {
                              key: 0,
                              href: _ctx.route("bar.index", { ponto }),
                              class: "rounded-xl bg-emerald-600 px-3 py-2 text-center font-black text-white"
                            }, {
                              default: withCtx(() => [
                                createTextVNode("Vender senhas")
                              ]),
                              _: 1
                            }, 8, ["href"])) : createCommentVNode("", true),
                            caixasPorPonto.value[ponto]?.estado === "aberta" && caixaAFechar.value !== caixasPorPonto.value[ponto].id ? (openBlock(), createBlock("button", {
                              key: 1,
                              type: "button",
                              class: "rounded-xl bg-slate-900 px-3 py-2 font-black text-white",
                              onClick: ($event) => prepararFecho(caixasPorPonto.value[ponto])
                            }, "Fechar", 8, ["onClick"])) : createCommentVNode("", true)
                          ], 2),
                          caixaAFechar.value === caixasPorPonto.value[ponto]?.id ? (openBlock(), createBlock("form", {
                            key: 2,
                            class: "mt-4 rounded-xl bg-white p-3",
                            onSubmit: withModifiers(($event) => fecharCaixa(caixasPorPonto.value[ponto]), ["prevent"])
                          }, [
                            createVNode("label", { class: "block text-sm font-bold text-slate-600" }, [
                              createTextVNode("Valor contado "),
                              withDirectives(createVNode("input", {
                                "onUpdate:modelValue": ($event) => unref(fecharForm).valor_contado = $event,
                                type: "number",
                                min: "0",
                                step: "0.01",
                                class: "mt-1 w-full rounded-xl border-slate-300 text-xl font-black"
                              }, null, 8, ["onUpdate:modelValue"]), [
                                [
                                  vModelText,
                                  unref(fecharForm).valor_contado,
                                  void 0,
                                  { number: true }
                                ]
                              ])
                            ]),
                            createVNode("label", { class: "mt-3 block text-sm font-bold text-slate-600" }, [
                              createTextVNode("Observações "),
                              withDirectives(createVNode("textarea", {
                                "onUpdate:modelValue": ($event) => unref(fecharForm).observacoes_fecho = $event,
                                class: "mt-1 w-full rounded-xl border-slate-300",
                                rows: "2",
                                placeholder: "Opcional"
                              }, null, 8, ["onUpdate:modelValue"]), [
                                [vModelText, unref(fecharForm).observacoes_fecho]
                              ])
                            ]),
                            createVNode("div", { class: "mt-3 grid grid-cols-2 gap-2" }, [
                              createVNode("button", {
                                type: "button",
                                class: "rounded-xl border border-slate-300 px-3 py-2 font-bold",
                                onClick: cancelarFecho
                              }, "Cancelar"),
                              createVNode("button", {
                                class: "rounded-xl bg-red-600 px-3 py-2 font-black text-white disabled:opacity-50",
                                disabled: unref(fecharForm).processing
                              }, "Confirmar", 8, ["disabled"])
                            ])
                          ], 40, ["onSubmit"])) : createCommentVNode("", true)
                        ], 2);
                      }), 128))
                    ])
                  ])) : createCommentVNode("", true)
                ]),
                createVNode("form", {
                  class: "sticky top-6 self-start rounded-[2rem] bg-white p-5 shadow-sm ring-1 ring-slate-200",
                  onSubmit: withModifiers(abrirCaixa, ["prevent"])
                }, [
                  createVNode("p", { class: "text-xs font-black uppercase tracking-[0.25em] text-slate-500" }, "Abertura"),
                  createVNode("h2", { class: "mt-2 text-2xl font-black" }, "Abrir / reabrir caixa"),
                  createVNode("label", { class: "mt-5 block text-sm font-bold text-slate-600" }, [
                    createTextVNode("Ponto "),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(form).ponto = $event,
                      list: "pontos-caixa",
                      class: "mt-1 w-full rounded-xl border-slate-300 text-lg font-black"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(form).ponto]
                    ])
                  ]),
                  createVNode("datalist", { id: "pontos-caixa" }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(__props.pontos_padrao, (ponto) => {
                      return openBlock(), createBlock("option", {
                        key: ponto,
                        value: ponto
                      }, null, 8, ["value"]);
                    }), 128))
                  ]),
                  createVNode("label", { class: "mt-4 block text-sm font-bold text-slate-600" }, [
                    createTextVNode("Fundo de maneio "),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(form).fundo_maneio = $event,
                      type: "number",
                      min: "0",
                      step: "0.01",
                      class: "mt-1 w-full rounded-xl border-slate-300 text-2xl font-black",
                      placeholder: "0.00"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [
                        vModelText,
                        unref(form).fundo_maneio,
                        void 0,
                        { number: true }
                      ]
                    ])
                  ]),
                  Object.keys(unref(form).errors).length ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "mt-4 rounded-xl bg-red-50 p-3 text-sm font-bold text-red-700"
                  }, [
                    (openBlock(true), createBlock(Fragment, null, renderList(unref(form).errors, (erro) => {
                      return openBlock(), createBlock("div", { key: erro }, toDisplayString(erro), 1);
                    }), 128))
                  ])) : createCommentVNode("", true),
                  createVNode("button", {
                    class: "mt-5 w-full rounded-2xl bg-slate-900 p-4 font-black text-white disabled:opacity-50",
                    disabled: unref(form).processing
                  }, toDisplayString(unref(form).processing ? "A guardar..." : "Guardar abertura"), 9, ["disabled"]),
                  createVNode("p", { class: "mt-3 text-xs text-slate-500" }, "Se uma caixa estiver fechada, reabrir limpa o fecho e permite continuar a trabalhar nesse ponto.")
                ], 32)
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Caixa/Index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
