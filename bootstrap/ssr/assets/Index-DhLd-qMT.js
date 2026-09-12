import { withCtx, unref, createVNode, withModifiers, withDirectives, vModelText, toDisplayString, openBlock, createBlock, Fragment, renderList, createCommentVNode, useSSRContext } from "vue";
import { ssrRenderComponent, ssrRenderAttr, ssrIncludeBooleanAttr, ssrInterpolate, ssrRenderList, ssrRenderClass } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import { useForm, router } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Index",
  __ssrInlineRender: true,
  props: { reservas: Object },
  setup(__props) {
    const hoje = (/* @__PURE__ */ new Date()).toISOString().slice(0, 10);
    const form = useForm({
      nome: "",
      data_reserva: hoje,
      hora: "20:00",
      pessoas: 2,
      estado: "confirmada"
    });
    const criarReserva = () => {
      form.transform((dados) => ({
        ...dados,
        data: dados.data_reserva
      })).post(route("reservas.store"), {
        preserveScroll: true,
        onSuccess: () => {
          form.reset("nome");
          form.pessoas = 2;
        },
        onFinish: () => form.transform((dados) => dados)
      });
    };
    const formatarDia = (data) => (/* @__PURE__ */ new Date(`${data}T00:00:00`)).toLocaleDateString("pt-PT", {
      weekday: "short",
      day: "2-digit",
      month: "2-digit"
    });
    const formatarHoraData = (data) => {
      if (!data) {
        return "Nao";
      }
      return new Date(data).toLocaleTimeString("pt-PT", {
        hour: "2-digit",
        minute: "2-digit"
      });
    };
    const marcarChamada = (reserva) => {
      router.patch(route("reservas.chamar", reserva.id), {}, { preserveScroll: true });
    };
    const marcarSentada = (reserva) => {
      if (!confirm(`Marcar ${reserva.nome} como sentada?`)) {
        return;
      }
      router.patch(route("reservas.sentar", reserva.id), {}, { preserveScroll: true });
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(_sfc_main$1, _attrs, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<h1 class="mb-6 text-2xl font-bold"${_scopeId}>Reservas</h1><form class="mb-6 rounded-lg bg-white p-5 shadow-sm"${_scopeId}><div class="grid gap-3 md:grid-cols-[1fr_130px_130px_120px_auto]"${_scopeId}><input${ssrRenderAttr("value", unref(form).nome)} class="rounded-md border-slate-300" placeholder="Nome"${_scopeId}><input${ssrRenderAttr("value", unref(form).pessoas)} type="number" min="1" class="rounded-md border-slate-300" placeholder="Pessoas"${_scopeId}><input${ssrRenderAttr("value", unref(form).data_reserva)} type="date" class="rounded-md border-slate-300"${_scopeId}><input${ssrRenderAttr("value", unref(form).hora)} type="time" class="rounded-md border-slate-300"${_scopeId}><button type="submit" class="rounded-md bg-slate-900 px-4 py-2 font-semibold text-white disabled:opacity-60"${ssrIncludeBooleanAttr(unref(form).processing) ? " disabled" : ""}${_scopeId}>${ssrInterpolate(unref(form).processing ? "A criar..." : "Criar reserva")}</button></div>`);
            if (Object.keys(unref(form).errors).length) {
              _push2(`<div class="mt-3 rounded-md bg-red-50 p-3 text-sm text-red-700"${_scopeId}><!--[-->`);
              ssrRenderList(unref(form).errors, (erro) => {
                _push2(`<div${_scopeId}>${ssrInterpolate(erro)}</div>`);
              });
              _push2(`<!--]--></div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</form><div class="overflow-x-auto rounded-lg bg-white shadow-sm"${_scopeId}><table class="w-full min-w-[860px] text-left text-sm"${_scopeId}><thead class="bg-slate-50"${_scopeId}><tr${_scopeId}><th class="p-3"${_scopeId}>Hora</th><th${_scopeId}>Dia</th><th${_scopeId}>Nome</th><th${_scopeId}>Pessoas</th><th${_scopeId}>Chamada</th><th${_scopeId}>Estado</th><th${_scopeId}></th></tr></thead><tbody${_scopeId}><!--[-->`);
            ssrRenderList(__props.reservas.data, (reserva) => {
              _push2(`<tr class="border-t"${_scopeId}><td class="p-3 text-lg font-bold"${_scopeId}>${ssrInterpolate(reserva.hora?.slice(0, 5))}</td><td class="font-semibold"${_scopeId}>${ssrInterpolate(formatarDia(reserva.data))}</td><td${_scopeId}>${ssrInterpolate(reserva.nome)}</td><td${_scopeId}>${ssrInterpolate(reserva.pessoas)}</td><td${_scopeId}><span class="${ssrRenderClass([reserva.chamada_em ? "bg-amber-100 text-amber-800" : "bg-slate-100 text-slate-600", "inline-flex min-w-16 justify-center rounded-full px-3 py-1 text-xs font-bold"])}"${_scopeId}>${ssrInterpolate(formatarHoraData(reserva.chamada_em))}</span></td><td${_scopeId}><span class="${ssrRenderClass([reserva.estado === "sentada" ? "bg-emerald-100 text-emerald-800" : "bg-blue-100 text-blue-800", "inline-flex min-w-20 justify-center rounded-full px-3 py-1 text-xs font-bold capitalize"])}"${_scopeId}>${ssrInterpolate(reserva.estado)}</span></td><td class="pr-3 text-right"${_scopeId}><div class="flex justify-end gap-2"${_scopeId}><button type="button" class="rounded-md border border-amber-300 px-3 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-50 disabled:opacity-40"${ssrIncludeBooleanAttr(reserva.estado === "sentada") ? " disabled" : ""}${_scopeId}> Chamada </button><button type="button" class="rounded-md border border-emerald-300 px-3 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50 disabled:opacity-40"${ssrIncludeBooleanAttr(reserva.estado === "sentada") ? " disabled" : ""}${_scopeId}> Sentada </button></div></td></tr>`);
            });
            _push2(`<!--]-->`);
            if (!__props.reservas.data.length) {
              _push2(`<tr${_scopeId}><td colspan="7" class="p-8 text-center text-slate-500"${_scopeId}>Ainda nao ha reservas.</td></tr>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</tbody></table></div>`);
          } else {
            return [
              createVNode("h1", { class: "mb-6 text-2xl font-bold" }, "Reservas"),
              createVNode("form", {
                class: "mb-6 rounded-lg bg-white p-5 shadow-sm",
                onSubmit: withModifiers(criarReserva, ["prevent"])
              }, [
                createVNode("div", { class: "grid gap-3 md:grid-cols-[1fr_130px_130px_120px_auto]" }, [
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(form).nome = $event,
                    class: "rounded-md border-slate-300",
                    placeholder: "Nome"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(form).nome]
                  ]),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(form).pessoas = $event,
                    type: "number",
                    min: "1",
                    class: "rounded-md border-slate-300",
                    placeholder: "Pessoas"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(form).pessoas]
                  ]),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(form).data_reserva = $event,
                    type: "date",
                    class: "rounded-md border-slate-300"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(form).data_reserva]
                  ]),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(form).hora = $event,
                    type: "time",
                    class: "rounded-md border-slate-300"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(form).hora]
                  ]),
                  createVNode("button", {
                    type: "submit",
                    class: "rounded-md bg-slate-900 px-4 py-2 font-semibold text-white disabled:opacity-60",
                    disabled: unref(form).processing
                  }, toDisplayString(unref(form).processing ? "A criar..." : "Criar reserva"), 9, ["disabled"])
                ]),
                Object.keys(unref(form).errors).length ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "mt-3 rounded-md bg-red-50 p-3 text-sm text-red-700"
                }, [
                  (openBlock(true), createBlock(Fragment, null, renderList(unref(form).errors, (erro) => {
                    return openBlock(), createBlock("div", { key: erro }, toDisplayString(erro), 1);
                  }), 128))
                ])) : createCommentVNode("", true)
              ], 32),
              createVNode("div", { class: "overflow-x-auto rounded-lg bg-white shadow-sm" }, [
                createVNode("table", { class: "w-full min-w-[860px] text-left text-sm" }, [
                  createVNode("thead", { class: "bg-slate-50" }, [
                    createVNode("tr", null, [
                      createVNode("th", { class: "p-3" }, "Hora"),
                      createVNode("th", null, "Dia"),
                      createVNode("th", null, "Nome"),
                      createVNode("th", null, "Pessoas"),
                      createVNode("th", null, "Chamada"),
                      createVNode("th", null, "Estado"),
                      createVNode("th")
                    ])
                  ]),
                  createVNode("tbody", null, [
                    (openBlock(true), createBlock(Fragment, null, renderList(__props.reservas.data, (reserva) => {
                      return openBlock(), createBlock("tr", {
                        key: reserva.id,
                        class: "border-t"
                      }, [
                        createVNode("td", { class: "p-3 text-lg font-bold" }, toDisplayString(reserva.hora?.slice(0, 5)), 1),
                        createVNode("td", { class: "font-semibold" }, toDisplayString(formatarDia(reserva.data)), 1),
                        createVNode("td", null, toDisplayString(reserva.nome), 1),
                        createVNode("td", null, toDisplayString(reserva.pessoas), 1),
                        createVNode("td", null, [
                          createVNode("span", {
                            class: ["inline-flex min-w-16 justify-center rounded-full px-3 py-1 text-xs font-bold", reserva.chamada_em ? "bg-amber-100 text-amber-800" : "bg-slate-100 text-slate-600"]
                          }, toDisplayString(formatarHoraData(reserva.chamada_em)), 3)
                        ]),
                        createVNode("td", null, [
                          createVNode("span", {
                            class: ["inline-flex min-w-20 justify-center rounded-full px-3 py-1 text-xs font-bold capitalize", reserva.estado === "sentada" ? "bg-emerald-100 text-emerald-800" : "bg-blue-100 text-blue-800"]
                          }, toDisplayString(reserva.estado), 3)
                        ]),
                        createVNode("td", { class: "pr-3 text-right" }, [
                          createVNode("div", { class: "flex justify-end gap-2" }, [
                            createVNode("button", {
                              type: "button",
                              class: "rounded-md border border-amber-300 px-3 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-50 disabled:opacity-40",
                              disabled: reserva.estado === "sentada",
                              onClick: ($event) => marcarChamada(reserva)
                            }, " Chamada ", 8, ["disabled", "onClick"]),
                            createVNode("button", {
                              type: "button",
                              class: "rounded-md border border-emerald-300 px-3 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50 disabled:opacity-40",
                              disabled: reserva.estado === "sentada",
                              onClick: ($event) => marcarSentada(reserva)
                            }, " Sentada ", 8, ["disabled", "onClick"])
                          ])
                        ])
                      ]);
                    }), 128)),
                    !__props.reservas.data.length ? (openBlock(), createBlock("tr", { key: 0 }, [
                      createVNode("td", {
                        colspan: "7",
                        class: "p-8 text-center text-slate-500"
                      }, "Ainda nao ha reservas.")
                    ])) : createCommentVNode("", true)
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Reservas/Index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
