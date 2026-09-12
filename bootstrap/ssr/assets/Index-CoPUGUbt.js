import { withCtx, unref, createVNode, toDisplayString, withModifiers, withDirectives, openBlock, createBlock, Fragment, renderList, vModelSelect, vModelText, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import { useForm } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Index",
  __ssrInlineRender: true,
  props: { cotas: Object, totais: Object, socios: Array, filters: Object },
  setup(__props) {
    const props = __props;
    const form = useForm({ socio_id: props.socios?.[0]?.id ?? "", ano: (/* @__PURE__ */ new Date()).getFullYear(), mes: (/* @__PURE__ */ new Date()).getMonth() + 1, tipo: "mensal", valor: 5, data_vencimento: (/* @__PURE__ */ new Date()).toISOString().slice(0, 10), estado: "pago", metodo_pagamento: "dinheiro" });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<h1 class="mb-6 text-2xl font-bold"${_scopeId}>Cotas</h1><div class="mb-6 grid gap-4 md:grid-cols-2"${_scopeId}><div class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><div class="text-sm text-slate-500"${_scopeId}>Cobrado</div><div class="text-3xl font-bold"${_scopeId}>${ssrInterpolate(Number(__props.totais?.cobrado ?? 0).toFixed(2))}€</div></div><div class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><div class="text-sm text-slate-500"${_scopeId}>Pendente</div><div class="text-3xl font-bold"${_scopeId}>${ssrInterpolate(Number(__props.totais?.pendente ?? 0).toFixed(2))}€</div></div></div><form class="mb-6 grid gap-3 rounded-lg bg-white p-5 shadow-sm md:grid-cols-4"${_scopeId}><select class="rounded-md border-slate-300"${_scopeId}><!--[-->`);
            ssrRenderList(__props.socios, (socio) => {
              _push2(`<option${ssrRenderAttr("value", socio.id)}${ssrIncludeBooleanAttr(Array.isArray(unref(form).socio_id) ? ssrLooseContain(unref(form).socio_id, socio.id) : ssrLooseEqual(unref(form).socio_id, socio.id)) ? " selected" : ""}${_scopeId}>${ssrInterpolate(socio.numero_socio)} · ${ssrInterpolate(socio.nome)}</option>`);
            });
            _push2(`<!--]--></select><input${ssrRenderAttr("value", unref(form).ano)} type="number" class="rounded-md border-slate-300"${_scopeId}><input${ssrRenderAttr("value", unref(form).mes)} type="number" class="rounded-md border-slate-300"${_scopeId}><input${ssrRenderAttr("value", unref(form).valor)} type="number" step="0.01" class="rounded-md border-slate-300"${_scopeId}><input${ssrRenderAttr("value", unref(form).data_vencimento)} type="date" class="rounded-md border-slate-300"${_scopeId}><select class="rounded-md border-slate-300"${_scopeId}><option${ssrIncludeBooleanAttr(Array.isArray(unref(form).estado) ? ssrLooseContain(unref(form).estado, null) : ssrLooseEqual(unref(form).estado, null)) ? " selected" : ""}${_scopeId}>pago</option><option${ssrIncludeBooleanAttr(Array.isArray(unref(form).estado) ? ssrLooseContain(unref(form).estado, null) : ssrLooseEqual(unref(form).estado, null)) ? " selected" : ""}${_scopeId}>pendente</option><option${ssrIncludeBooleanAttr(Array.isArray(unref(form).estado) ? ssrLooseContain(unref(form).estado, null) : ssrLooseEqual(unref(form).estado, null)) ? " selected" : ""}${_scopeId}>em_atraso</option></select><button class="rounded-md bg-slate-900 px-4 py-2 text-white"${_scopeId}>Registar</button></form><div class="overflow-x-auto rounded-lg bg-white shadow-sm"${_scopeId}><table class="w-full min-w-[400px] text-left text-sm"${_scopeId}><tbody${_scopeId}><!--[-->`);
            ssrRenderList(__props.cotas.data, (cota) => {
              _push2(`<tr class="border-t"${_scopeId}><td class="p-3"${_scopeId}>${ssrInterpolate(cota.socio?.nome)}</td><td${_scopeId}>${ssrInterpolate(cota.ano)}/${ssrInterpolate(cota.mes)}</td><td${_scopeId}>${ssrInterpolate(cota.valor)}€</td><td${_scopeId}>${ssrInterpolate(cota.estado)}</td></tr>`);
            });
            _push2(`<!--]--></tbody></table></div>`);
          } else {
            return [
              createVNode("h1", { class: "mb-6 text-2xl font-bold" }, "Cotas"),
              createVNode("div", { class: "mb-6 grid gap-4 md:grid-cols-2" }, [
                createVNode("div", { class: "rounded-lg bg-white p-5 shadow-sm" }, [
                  createVNode("div", { class: "text-sm text-slate-500" }, "Cobrado"),
                  createVNode("div", { class: "text-3xl font-bold" }, toDisplayString(Number(__props.totais?.cobrado ?? 0).toFixed(2)) + "€", 1)
                ]),
                createVNode("div", { class: "rounded-lg bg-white p-5 shadow-sm" }, [
                  createVNode("div", { class: "text-sm text-slate-500" }, "Pendente"),
                  createVNode("div", { class: "text-3xl font-bold" }, toDisplayString(Number(__props.totais?.pendente ?? 0).toFixed(2)) + "€", 1)
                ])
              ]),
              createVNode("form", {
                class: "mb-6 grid gap-3 rounded-lg bg-white p-5 shadow-sm md:grid-cols-4",
                onSubmit: withModifiers(($event) => unref(form).post(_ctx.route("cotas.store")), ["prevent"])
              }, [
                withDirectives(createVNode("select", {
                  "onUpdate:modelValue": ($event) => unref(form).socio_id = $event,
                  class: "rounded-md border-slate-300"
                }, [
                  (openBlock(true), createBlock(Fragment, null, renderList(__props.socios, (socio) => {
                    return openBlock(), createBlock("option", {
                      value: socio.id
                    }, toDisplayString(socio.numero_socio) + " · " + toDisplayString(socio.nome), 9, ["value"]);
                  }), 256))
                ], 8, ["onUpdate:modelValue"]), [
                  [vModelSelect, unref(form).socio_id]
                ]),
                withDirectives(createVNode("input", {
                  "onUpdate:modelValue": ($event) => unref(form).ano = $event,
                  type: "number",
                  class: "rounded-md border-slate-300"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(form).ano]
                ]),
                withDirectives(createVNode("input", {
                  "onUpdate:modelValue": ($event) => unref(form).mes = $event,
                  type: "number",
                  class: "rounded-md border-slate-300"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(form).mes]
                ]),
                withDirectives(createVNode("input", {
                  "onUpdate:modelValue": ($event) => unref(form).valor = $event,
                  type: "number",
                  step: "0.01",
                  class: "rounded-md border-slate-300"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(form).valor]
                ]),
                withDirectives(createVNode("input", {
                  "onUpdate:modelValue": ($event) => unref(form).data_vencimento = $event,
                  type: "date",
                  class: "rounded-md border-slate-300"
                }, null, 8, ["onUpdate:modelValue"]), [
                  [vModelText, unref(form).data_vencimento]
                ]),
                withDirectives(createVNode("select", {
                  "onUpdate:modelValue": ($event) => unref(form).estado = $event,
                  class: "rounded-md border-slate-300"
                }, [
                  createVNode("option", null, "pago"),
                  createVNode("option", null, "pendente"),
                  createVNode("option", null, "em_atraso")
                ], 8, ["onUpdate:modelValue"]), [
                  [vModelSelect, unref(form).estado]
                ]),
                createVNode("button", { class: "rounded-md bg-slate-900 px-4 py-2 text-white" }, "Registar")
              ], 40, ["onSubmit"]),
              createVNode("div", { class: "overflow-x-auto rounded-lg bg-white shadow-sm" }, [
                createVNode("table", { class: "w-full min-w-[400px] text-left text-sm" }, [
                  createVNode("tbody", null, [
                    (openBlock(true), createBlock(Fragment, null, renderList(__props.cotas.data, (cota) => {
                      return openBlock(), createBlock("tr", {
                        key: cota.id,
                        class: "border-t"
                      }, [
                        createVNode("td", { class: "p-3" }, toDisplayString(cota.socio?.nome), 1),
                        createVNode("td", null, toDisplayString(cota.ano) + "/" + toDisplayString(cota.mes), 1),
                        createVNode("td", null, toDisplayString(cota.valor) + "€", 1),
                        createVNode("td", null, toDisplayString(cota.estado), 1)
                      ]);
                    }), 128))
                  ])
                ])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Cotas/Index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
