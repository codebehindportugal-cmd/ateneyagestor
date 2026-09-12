import { ref, computed, unref, withCtx, createTextVNode, createVNode, withModifiers, withDirectives, vModelText, vModelSelect, vModelCheckbox, toDisplayString, openBlock, createBlock, createCommentVNode, Fragment, renderList, useSSRContext } from "vue";
import { ssrRenderComponent, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrInterpolate, ssrRenderList } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import { useForm, Head, Link, router } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Index",
  __ssrInlineRender: true,
  props: {
    eventos: Array
  },
  setup(__props) {
    const props = __props;
    const novo = useForm({
      titulo: "",
      subtitulo: "",
      data_inicio: "",
      data_fim: "",
      periodo: "",
      localizacao: "",
      badge: "Evento",
      descricao: "",
      estado: "publicado",
      destaque: false,
      ordem: 0,
      programa_texto: "",
      cartaz: null
    });
    const cartazNovo = ref(null);
    const eventos = computed(() => props.eventos ?? []);
    const guardarNovo = () => {
      novo.post(route("eventos.store"), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
          novo.reset();
          novo.estado = "publicado";
          novo.badge = "Evento";
          if (cartazNovo.value) cartazNovo.value.value = "";
        }
      });
    };
    const apagar = (evento) => {
      if (!confirm(`Apagar o evento "${evento.titulo}"?`)) return;
      router.delete(route("eventos.destroy", evento.id), { preserveScroll: true });
    };
    const dataEvento = (evento) => {
      if (!evento.data_inicio) return evento.periodo || "Sem data definida";
      if (evento.data_fim && evento.data_fim !== evento.data_inicio) return `${evento.data_inicio} a ${evento.data_fim}`;
      return evento.data_inicio;
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), { title: "Eventos" }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$1, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mb-6 flex flex-wrap items-end justify-between gap-3"${_scopeId}><div${_scopeId}><h1 class="text-2xl font-black"${_scopeId}>Eventos da associacao</h1><p class="mt-1 text-sm text-slate-500"${_scopeId}>Cria eventos, abre a ficha do evento e edita cartazes, programa, fotos e videos.</p></div><a${ssrRenderAttr("href", _ctx.route("home"))} target="_blank" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-bold hover:bg-white"${_scopeId}>Ver home</a></div><form class="mb-8 rounded-lg bg-white p-5 shadow-sm"${_scopeId}><h2 class="mb-4 font-black"${_scopeId}>Novo evento</h2><div class="grid gap-3 md:grid-cols-4"${_scopeId}><input${ssrRenderAttr("value", unref(novo).titulo)} required placeholder="Titulo" class="rounded-md border-slate-300 md:col-span-2"${_scopeId}><input${ssrRenderAttr("value", unref(novo).subtitulo)} placeholder="Subtitulo" class="rounded-md border-slate-300"${_scopeId}><input${ssrRenderAttr("value", unref(novo).localizacao)} placeholder="Local" class="rounded-md border-slate-300"${_scopeId}><input${ssrRenderAttr("value", unref(novo).data_inicio)} type="date" class="rounded-md border-slate-300"${_scopeId}><input${ssrRenderAttr("value", unref(novo).data_fim)} type="date" class="rounded-md border-slate-300"${_scopeId}><input${ssrRenderAttr("value", unref(novo).periodo)} placeholder="Periodo, ex: Julho 2026" class="rounded-md border-slate-300"${_scopeId}><input${ssrRenderAttr("value", unref(novo).badge)} placeholder="Etiqueta" class="rounded-md border-slate-300"${_scopeId}><select class="rounded-md border-slate-300"${_scopeId}><option value="publicado"${ssrIncludeBooleanAttr(Array.isArray(unref(novo).estado) ? ssrLooseContain(unref(novo).estado, "publicado") : ssrLooseEqual(unref(novo).estado, "publicado")) ? " selected" : ""}${_scopeId}>Publicado</option><option value="rascunho"${ssrIncludeBooleanAttr(Array.isArray(unref(novo).estado) ? ssrLooseContain(unref(novo).estado, "rascunho") : ssrLooseEqual(unref(novo).estado, "rascunho")) ? " selected" : ""}${_scopeId}>Rascunho</option></select><label class="flex items-center gap-2 rounded-md border border-slate-300 px-3 py-2 text-sm font-bold"${_scopeId}><input${ssrIncludeBooleanAttr(Array.isArray(unref(novo).destaque) ? ssrLooseContain(unref(novo).destaque, null) : unref(novo).destaque) ? " checked" : ""} type="checkbox" class="rounded border-slate-300"${_scopeId}> Destaque </label><input${ssrRenderAttr("value", unref(novo).ordem)} type="number" min="0" placeholder="Ordem" class="rounded-md border-slate-300"${_scopeId}><input type="file" accept="image/*" class="rounded-md border border-slate-300 p-2 text-sm"${_scopeId}><textarea placeholder="Descricao" rows="3" class="rounded-md border-slate-300 md:col-span-2"${_scopeId}>${ssrInterpolate(unref(novo).descricao)}</textarea><textarea placeholder="Programa: uma linha por item" rows="3" class="rounded-md border-slate-300 md:col-span-2"${_scopeId}>${ssrInterpolate(unref(novo).programa_texto)}</textarea></div><button class="mt-4 rounded-md bg-slate-900 px-5 py-3 font-black text-white disabled:opacity-60"${ssrIncludeBooleanAttr(unref(novo).processing) ? " disabled" : ""}${_scopeId}>Criar evento</button></form><div class="mb-4 flex items-center justify-between gap-3"${_scopeId}><div${_scopeId}><h2 class="text-xl font-black"${_scopeId}>Eventos existentes</h2><p class="text-sm text-slate-500"${_scopeId}>Usa Abrir para ver como correu o evento, ou Editar para alterar informacao.</p></div><span class="rounded bg-white px-3 py-1 text-sm font-black text-slate-600 shadow-sm"${_scopeId}>${ssrInterpolate(eventos.value.length)} eventos</span></div>`);
            if (!eventos.value.length) {
              _push2(`<div class="rounded-lg bg-white p-6 text-center font-bold text-slate-500 shadow-sm"${_scopeId}> Ainda nao existem eventos na base de dados. Corre o EventoSeeder ou cria um novo evento acima. </div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="grid gap-5 lg:grid-cols-2"${_scopeId}><!--[-->`);
            ssrRenderList(eventos.value, (evento) => {
              _push2(`<article class="grid overflow-hidden rounded-lg bg-white shadow-sm sm:grid-cols-[170px_1fr]"${_scopeId}>`);
              if (evento.cartaz) {
                _push2(`<img${ssrRenderAttr("src", evento.cartaz)}${ssrRenderAttr("alt", evento.titulo)} class="h-full min-h-56 w-full bg-slate-100 object-cover"${_scopeId}>`);
              } else {
                _push2(`<div class="grid min-h-56 place-items-center bg-slate-100 text-sm font-bold text-slate-400"${_scopeId}>Sem cartaz</div>`);
              }
              _push2(`<div class="flex min-w-0 flex-col p-5"${_scopeId}><div class="flex flex-wrap items-center gap-2"${_scopeId}><span class="rounded bg-slate-900 px-2 py-1 text-xs font-black uppercase text-white"${_scopeId}>${ssrInterpolate(evento.estado)}</span>`);
              if (evento.destaque) {
                _push2(`<span class="rounded bg-amber-100 px-2 py-1 text-xs font-black uppercase text-amber-800"${_scopeId}>Destaque</span>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`<span class="text-sm font-bold text-slate-500"${_scopeId}>${ssrInterpolate(dataEvento(evento))}</span></div><h3 class="mt-3 truncate text-xl font-black"${_scopeId}>${ssrInterpolate(evento.titulo)}</h3><p class="mt-1 text-sm font-bold text-slate-500"${_scopeId}>${ssrInterpolate(evento.subtitulo || evento.localizacao || "Sem subtitulo")}</p><p class="mt-3 line-clamp-3 text-sm leading-relaxed text-slate-600"${_scopeId}>${ssrInterpolate(evento.descricao || "Sem descricao ainda.")}</p><div class="mt-4 flex flex-wrap gap-2 text-xs font-bold text-slate-500"${_scopeId}><span class="rounded bg-slate-100 px-2 py-1"${_scopeId}>${ssrInterpolate(evento.media?.length || 0)} fotos/videos</span><span class="rounded bg-slate-100 px-2 py-1"${_scopeId}>Ordem ${ssrInterpolate(evento.ordem)}</span>`);
              if (evento.updated_at) {
                _push2(`<span class="rounded bg-slate-100 px-2 py-1"${_scopeId}>Atualizado ${ssrInterpolate(evento.updated_at)}</span>`);
              } else {
                _push2(`<!---->`);
              }
              _push2(`</div><div class="mt-auto flex flex-wrap gap-2 pt-5"${_scopeId}>`);
              _push2(ssrRenderComponent(unref(Link), {
                href: _ctx.route("eventos.show", evento.id),
                class: "rounded-md bg-slate-900 px-4 py-2 text-sm font-bold text-white"
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`Abrir`);
                  } else {
                    return [
                      createTextVNode("Abrir")
                    ];
                  }
                }),
                _: 2
              }, _parent2, _scopeId));
              _push2(ssrRenderComponent(unref(Link), {
                href: _ctx.route("eventos.edit", evento.id),
                class: "rounded-md border border-slate-300 px-4 py-2 text-sm font-bold hover:bg-slate-50"
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`Editar`);
                  } else {
                    return [
                      createTextVNode("Editar")
                    ];
                  }
                }),
                _: 2
              }, _parent2, _scopeId));
              _push2(`<button type="button" class="rounded-md border border-rose-200 px-4 py-2 text-sm font-bold text-rose-700 hover:bg-rose-50"${_scopeId}>Apagar</button></div></div></article>`);
            });
            _push2(`<!--]--></div>`);
          } else {
            return [
              createVNode("div", { class: "mb-6 flex flex-wrap items-end justify-between gap-3" }, [
                createVNode("div", null, [
                  createVNode("h1", { class: "text-2xl font-black" }, "Eventos da associacao"),
                  createVNode("p", { class: "mt-1 text-sm text-slate-500" }, "Cria eventos, abre a ficha do evento e edita cartazes, programa, fotos e videos.")
                ]),
                createVNode("a", {
                  href: _ctx.route("home"),
                  target: "_blank",
                  class: "rounded-md border border-slate-300 px-4 py-2 text-sm font-bold hover:bg-white"
                }, "Ver home", 8, ["href"])
              ]),
              createVNode("form", {
                class: "mb-8 rounded-lg bg-white p-5 shadow-sm",
                onSubmit: withModifiers(guardarNovo, ["prevent"])
              }, [
                createVNode("h2", { class: "mb-4 font-black" }, "Novo evento"),
                createVNode("div", { class: "grid gap-3 md:grid-cols-4" }, [
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(novo).titulo = $event,
                    required: "",
                    placeholder: "Titulo",
                    class: "rounded-md border-slate-300 md:col-span-2"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(novo).titulo]
                  ]),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(novo).subtitulo = $event,
                    placeholder: "Subtitulo",
                    class: "rounded-md border-slate-300"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(novo).subtitulo]
                  ]),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(novo).localizacao = $event,
                    placeholder: "Local",
                    class: "rounded-md border-slate-300"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(novo).localizacao]
                  ]),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(novo).data_inicio = $event,
                    type: "date",
                    class: "rounded-md border-slate-300"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(novo).data_inicio]
                  ]),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(novo).data_fim = $event,
                    type: "date",
                    class: "rounded-md border-slate-300"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(novo).data_fim]
                  ]),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(novo).periodo = $event,
                    placeholder: "Periodo, ex: Julho 2026",
                    class: "rounded-md border-slate-300"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(novo).periodo]
                  ]),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(novo).badge = $event,
                    placeholder: "Etiqueta",
                    class: "rounded-md border-slate-300"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(novo).badge]
                  ]),
                  withDirectives(createVNode("select", {
                    "onUpdate:modelValue": ($event) => unref(novo).estado = $event,
                    class: "rounded-md border-slate-300"
                  }, [
                    createVNode("option", { value: "publicado" }, "Publicado"),
                    createVNode("option", { value: "rascunho" }, "Rascunho")
                  ], 8, ["onUpdate:modelValue"]), [
                    [vModelSelect, unref(novo).estado]
                  ]),
                  createVNode("label", { class: "flex items-center gap-2 rounded-md border border-slate-300 px-3 py-2 text-sm font-bold" }, [
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(novo).destaque = $event,
                      type: "checkbox",
                      class: "rounded border-slate-300"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelCheckbox, unref(novo).destaque]
                    ]),
                    createTextVNode(" Destaque ")
                  ]),
                  withDirectives(createVNode("input", {
                    "onUpdate:modelValue": ($event) => unref(novo).ordem = $event,
                    type: "number",
                    min: "0",
                    placeholder: "Ordem",
                    class: "rounded-md border-slate-300"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [
                      vModelText,
                      unref(novo).ordem,
                      void 0,
                      { number: true }
                    ]
                  ]),
                  createVNode("input", {
                    ref_key: "cartazNovo",
                    ref: cartazNovo,
                    type: "file",
                    accept: "image/*",
                    class: "rounded-md border border-slate-300 p-2 text-sm",
                    onChange: ($event) => unref(novo).cartaz = $event.target.files[0]
                  }, null, 40, ["onChange"]),
                  withDirectives(createVNode("textarea", {
                    "onUpdate:modelValue": ($event) => unref(novo).descricao = $event,
                    placeholder: "Descricao",
                    rows: "3",
                    class: "rounded-md border-slate-300 md:col-span-2"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(novo).descricao]
                  ]),
                  withDirectives(createVNode("textarea", {
                    "onUpdate:modelValue": ($event) => unref(novo).programa_texto = $event,
                    placeholder: "Programa: uma linha por item",
                    rows: "3",
                    class: "rounded-md border-slate-300 md:col-span-2"
                  }, null, 8, ["onUpdate:modelValue"]), [
                    [vModelText, unref(novo).programa_texto]
                  ])
                ]),
                createVNode("button", {
                  class: "mt-4 rounded-md bg-slate-900 px-5 py-3 font-black text-white disabled:opacity-60",
                  disabled: unref(novo).processing
                }, "Criar evento", 8, ["disabled"])
              ], 32),
              createVNode("div", { class: "mb-4 flex items-center justify-between gap-3" }, [
                createVNode("div", null, [
                  createVNode("h2", { class: "text-xl font-black" }, "Eventos existentes"),
                  createVNode("p", { class: "text-sm text-slate-500" }, "Usa Abrir para ver como correu o evento, ou Editar para alterar informacao.")
                ]),
                createVNode("span", { class: "rounded bg-white px-3 py-1 text-sm font-black text-slate-600 shadow-sm" }, toDisplayString(eventos.value.length) + " eventos", 1)
              ]),
              !eventos.value.length ? (openBlock(), createBlock("div", {
                key: 0,
                class: "rounded-lg bg-white p-6 text-center font-bold text-slate-500 shadow-sm"
              }, " Ainda nao existem eventos na base de dados. Corre o EventoSeeder ou cria um novo evento acima. ")) : createCommentVNode("", true),
              createVNode("div", { class: "grid gap-5 lg:grid-cols-2" }, [
                (openBlock(true), createBlock(Fragment, null, renderList(eventos.value, (evento) => {
                  return openBlock(), createBlock("article", {
                    key: evento.id,
                    class: "grid overflow-hidden rounded-lg bg-white shadow-sm sm:grid-cols-[170px_1fr]"
                  }, [
                    evento.cartaz ? (openBlock(), createBlock("img", {
                      key: 0,
                      src: evento.cartaz,
                      alt: evento.titulo,
                      class: "h-full min-h-56 w-full bg-slate-100 object-cover"
                    }, null, 8, ["src", "alt"])) : (openBlock(), createBlock("div", {
                      key: 1,
                      class: "grid min-h-56 place-items-center bg-slate-100 text-sm font-bold text-slate-400"
                    }, "Sem cartaz")),
                    createVNode("div", { class: "flex min-w-0 flex-col p-5" }, [
                      createVNode("div", { class: "flex flex-wrap items-center gap-2" }, [
                        createVNode("span", { class: "rounded bg-slate-900 px-2 py-1 text-xs font-black uppercase text-white" }, toDisplayString(evento.estado), 1),
                        evento.destaque ? (openBlock(), createBlock("span", {
                          key: 0,
                          class: "rounded bg-amber-100 px-2 py-1 text-xs font-black uppercase text-amber-800"
                        }, "Destaque")) : createCommentVNode("", true),
                        createVNode("span", { class: "text-sm font-bold text-slate-500" }, toDisplayString(dataEvento(evento)), 1)
                      ]),
                      createVNode("h3", { class: "mt-3 truncate text-xl font-black" }, toDisplayString(evento.titulo), 1),
                      createVNode("p", { class: "mt-1 text-sm font-bold text-slate-500" }, toDisplayString(evento.subtitulo || evento.localizacao || "Sem subtitulo"), 1),
                      createVNode("p", { class: "mt-3 line-clamp-3 text-sm leading-relaxed text-slate-600" }, toDisplayString(evento.descricao || "Sem descricao ainda."), 1),
                      createVNode("div", { class: "mt-4 flex flex-wrap gap-2 text-xs font-bold text-slate-500" }, [
                        createVNode("span", { class: "rounded bg-slate-100 px-2 py-1" }, toDisplayString(evento.media?.length || 0) + " fotos/videos", 1),
                        createVNode("span", { class: "rounded bg-slate-100 px-2 py-1" }, "Ordem " + toDisplayString(evento.ordem), 1),
                        evento.updated_at ? (openBlock(), createBlock("span", {
                          key: 0,
                          class: "rounded bg-slate-100 px-2 py-1"
                        }, "Atualizado " + toDisplayString(evento.updated_at), 1)) : createCommentVNode("", true)
                      ]),
                      createVNode("div", { class: "mt-auto flex flex-wrap gap-2 pt-5" }, [
                        createVNode(unref(Link), {
                          href: _ctx.route("eventos.show", evento.id),
                          class: "rounded-md bg-slate-900 px-4 py-2 text-sm font-bold text-white"
                        }, {
                          default: withCtx(() => [
                            createTextVNode("Abrir")
                          ]),
                          _: 1
                        }, 8, ["href"]),
                        createVNode(unref(Link), {
                          href: _ctx.route("eventos.edit", evento.id),
                          class: "rounded-md border border-slate-300 px-4 py-2 text-sm font-bold hover:bg-slate-50"
                        }, {
                          default: withCtx(() => [
                            createTextVNode("Editar")
                          ]),
                          _: 1
                        }, 8, ["href"]),
                        createVNode("button", {
                          type: "button",
                          class: "rounded-md border border-rose-200 px-4 py-2 text-sm font-bold text-rose-700 hover:bg-rose-50",
                          onClick: ($event) => apagar(evento)
                        }, "Apagar", 8, ["onClick"])
                      ])
                    ])
                  ]);
                }), 128))
              ])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Eventos/Index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
