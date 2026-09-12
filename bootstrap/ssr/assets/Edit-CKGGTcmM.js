import { unref, withCtx, createTextVNode, createVNode, toDisplayString, withModifiers, withDirectives, vModelText, openBlock, createBlock, createCommentVNode, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate, ssrRenderAttr, ssrIncludeBooleanAttr } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import { useForm, Head, Link } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Edit",
  __ssrInlineRender: true,
  props: {
    pagina: Object
  },
  setup(__props) {
    const props = __props;
    const c = props.pagina.conteudo || {};
    const form = useForm({
      titulo: props.pagina.titulo || "",
      hero_titulo: c.hero_titulo || "",
      hero_subtitulo: c.hero_subtitulo || "",
      introducao: c.introducao || "",
      corpo: c.corpo || "",
      extra: c.extra || ""
    });
    const guardar = () => {
      form.put(route("paginas.update", props.pagina.id), {
        preserveScroll: true
      });
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), {
        title: `Editar ${__props.pagina.titulo}`
      }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$1, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mb-6 flex flex-wrap items-end justify-between gap-3"${_scopeId}><div${_scopeId}><p class="text-sm font-black uppercase tracking-[0.14em] text-slate-400"${_scopeId}>Página ${ssrInterpolate(__props.pagina.slug)}</p><h1 class="text-2xl font-black"${_scopeId}>${ssrInterpolate(__props.pagina.titulo)}</h1></div>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("paginas.index"),
              class: "rounded-md border border-slate-300 px-4 py-2 text-sm font-bold hover:bg-white"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Voltar`);
                } else {
                  return [
                    createTextVNode("Voltar")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div><form class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><div class="grid gap-4"${_scopeId}><label class="grid gap-1 text-sm font-bold text-slate-700"${_scopeId}> Nome no backoffice <input${ssrRenderAttr("value", unref(form).titulo)} required class="rounded-md border-slate-300"${_scopeId}>`);
            if (unref(form).errors.titulo) {
              _push2(`<span class="text-xs text-rose-600"${_scopeId}>${ssrInterpolate(unref(form).errors.titulo)}</span>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</label><label class="grid gap-1 text-sm font-bold text-slate-700"${_scopeId}> Título principal <input${ssrRenderAttr("value", unref(form).hero_titulo)} class="rounded-md border-slate-300"${_scopeId}></label><label class="grid gap-1 text-sm font-bold text-slate-700"${_scopeId}> Subtítulo / data <textarea rows="2" class="rounded-md border-slate-300"${_scopeId}>${ssrInterpolate(unref(form).hero_subtitulo)}</textarea></label><label class="grid gap-1 text-sm font-bold text-slate-700"${_scopeId}> Introdução <textarea rows="3" class="rounded-md border-slate-300"${_scopeId}>${ssrInterpolate(unref(form).introducao)}</textarea></label><label class="grid gap-1 text-sm font-bold text-slate-700"${_scopeId}> Corpo do texto <textarea rows="12" class="rounded-md border-slate-300"${_scopeId}>${ssrInterpolate(unref(form).corpo)}</textarea><span class="text-xs font-medium text-slate-500"${_scopeId}>Usa uma linha em branco para separar parágrafos.</span></label><label class="grid gap-1 text-sm font-bold text-slate-700"${_scopeId}> Conteúdo extra <textarea rows="6" class="rounded-md border-slate-300"${_scopeId}>${ssrInterpolate(unref(form).extra)}</textarea><span class="text-xs font-medium text-slate-500"${_scopeId}>Para blocos em lista, usa o formato: Título|Descrição, uma linha por item.</span></label></div><div class="mt-5 flex flex-wrap gap-2"${_scopeId}><button class="rounded-md bg-emerald-700 px-5 py-3 font-black text-white disabled:opacity-60"${ssrIncludeBooleanAttr(unref(form).processing) ? " disabled" : ""}${_scopeId}>Guardar alterações</button>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("paginas.index"),
              class: "rounded-md border border-slate-300 px-5 py-3 font-black hover:bg-slate-50"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Cancelar`);
                } else {
                  return [
                    createTextVNode("Cancelar")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div></form>`);
          } else {
            return [
              createVNode("div", { class: "mb-6 flex flex-wrap items-end justify-between gap-3" }, [
                createVNode("div", null, [
                  createVNode("p", { class: "text-sm font-black uppercase tracking-[0.14em] text-slate-400" }, "Página " + toDisplayString(__props.pagina.slug), 1),
                  createVNode("h1", { class: "text-2xl font-black" }, toDisplayString(__props.pagina.titulo), 1)
                ]),
                createVNode(unref(Link), {
                  href: _ctx.route("paginas.index"),
                  class: "rounded-md border border-slate-300 px-4 py-2 text-sm font-bold hover:bg-white"
                }, {
                  default: withCtx(() => [
                    createTextVNode("Voltar")
                  ]),
                  _: 1
                }, 8, ["href"])
              ]),
              createVNode("form", {
                class: "rounded-lg bg-white p-5 shadow-sm",
                onSubmit: withModifiers(guardar, ["prevent"])
              }, [
                createVNode("div", { class: "grid gap-4" }, [
                  createVNode("label", { class: "grid gap-1 text-sm font-bold text-slate-700" }, [
                    createTextVNode(" Nome no backoffice "),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(form).titulo = $event,
                      required: "",
                      class: "rounded-md border-slate-300"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(form).titulo]
                    ]),
                    unref(form).errors.titulo ? (openBlock(), createBlock("span", {
                      key: 0,
                      class: "text-xs text-rose-600"
                    }, toDisplayString(unref(form).errors.titulo), 1)) : createCommentVNode("", true)
                  ]),
                  createVNode("label", { class: "grid gap-1 text-sm font-bold text-slate-700" }, [
                    createTextVNode(" Título principal "),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(form).hero_titulo = $event,
                      class: "rounded-md border-slate-300"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(form).hero_titulo]
                    ])
                  ]),
                  createVNode("label", { class: "grid gap-1 text-sm font-bold text-slate-700" }, [
                    createTextVNode(" Subtítulo / data "),
                    withDirectives(createVNode("textarea", {
                      "onUpdate:modelValue": ($event) => unref(form).hero_subtitulo = $event,
                      rows: "2",
                      class: "rounded-md border-slate-300"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(form).hero_subtitulo]
                    ])
                  ]),
                  createVNode("label", { class: "grid gap-1 text-sm font-bold text-slate-700" }, [
                    createTextVNode(" Introdução "),
                    withDirectives(createVNode("textarea", {
                      "onUpdate:modelValue": ($event) => unref(form).introducao = $event,
                      rows: "3",
                      class: "rounded-md border-slate-300"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(form).introducao]
                    ])
                  ]),
                  createVNode("label", { class: "grid gap-1 text-sm font-bold text-slate-700" }, [
                    createTextVNode(" Corpo do texto "),
                    withDirectives(createVNode("textarea", {
                      "onUpdate:modelValue": ($event) => unref(form).corpo = $event,
                      rows: "12",
                      class: "rounded-md border-slate-300"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(form).corpo]
                    ]),
                    createVNode("span", { class: "text-xs font-medium text-slate-500" }, "Usa uma linha em branco para separar parágrafos.")
                  ]),
                  createVNode("label", { class: "grid gap-1 text-sm font-bold text-slate-700" }, [
                    createTextVNode(" Conteúdo extra "),
                    withDirectives(createVNode("textarea", {
                      "onUpdate:modelValue": ($event) => unref(form).extra = $event,
                      rows: "6",
                      class: "rounded-md border-slate-300"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(form).extra]
                    ]),
                    createVNode("span", { class: "text-xs font-medium text-slate-500" }, "Para blocos em lista, usa o formato: Título|Descrição, uma linha por item.")
                  ])
                ]),
                createVNode("div", { class: "mt-5 flex flex-wrap gap-2" }, [
                  createVNode("button", {
                    class: "rounded-md bg-emerald-700 px-5 py-3 font-black text-white disabled:opacity-60",
                    disabled: unref(form).processing
                  }, "Guardar alterações", 8, ["disabled"]),
                  createVNode(unref(Link), {
                    href: _ctx.route("paginas.index"),
                    class: "rounded-md border border-slate-300 px-5 py-3 font-black hover:bg-slate-50"
                  }, {
                    default: withCtx(() => [
                      createTextVNode("Cancelar")
                    ]),
                    _: 1
                  }, 8, ["href"])
                ])
              ], 32)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<!--]-->`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Paginas/Edit.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
