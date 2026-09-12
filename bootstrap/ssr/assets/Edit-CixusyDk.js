import { unref, withCtx, createTextVNode, createVNode, toDisplayString, openBlock, createBlock, withModifiers, withDirectives, vModelText, vModelSelect, vModelCheckbox, Fragment, renderList, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain, ssrLooseEqual, ssrRenderList } from "vue/server-renderer";
import { _ as _sfc_main$1 } from "./AppLayout-BWDcck4N.js";
import { useForm, Head, Link, router } from "@inertiajs/vue3";
const _sfc_main = {
  __name: "Edit",
  __ssrInlineRender: true,
  props: {
    evento: Object
  },
  setup(__props) {
    const props = __props;
    const linhasPrograma = (evento) => (evento.programa ?? []).flatMap((grupo) => grupo.items ?? []).join("\n");
    const form = useForm({
      titulo: props.evento.titulo ?? "",
      subtitulo: props.evento.subtitulo ?? "",
      data_inicio: props.evento.data_inicio ?? "",
      data_fim: props.evento.data_fim ?? "",
      periodo: props.evento.periodo ?? "",
      localizacao: props.evento.localizacao ?? "",
      badge: props.evento.badge ?? "",
      descricao: props.evento.descricao ?? "",
      facebook_post_url: props.evento.facebook_post_url ?? "",
      estado: props.evento.estado ?? "publicado",
      destaque: Boolean(props.evento.destaque),
      ordem: props.evento.ordem ?? 0,
      programa_texto: linhasPrograma(props.evento),
      cartaz: null
    });
    const guardar = () => {
      form.transform((data) => ({
        ...data,
        destaque: data.destaque ? 1 : 0,
        _method: "put"
      })).post(route("eventos.update", props.evento.id), {
        forceFormData: true,
        preserveScroll: true
      });
    };
    const uploadMedia = (event) => {
      const ficheiros = Array.from(event.target.files ?? []);
      if (!ficheiros.length) return;
      const data = new FormData();
      ficheiros.forEach((ficheiro) => data.append("ficheiros[]", ficheiro));
      router.post(route("eventos.media.store", props.evento.id), data, {
        preserveScroll: true,
        onFinish: () => {
          event.target.value = "";
        }
      });
    };
    const apagarMedia = (media) => {
      router.delete(route("eventos.media.destroy", media.id), { preserveScroll: true });
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), {
        title: `Editar ${__props.evento.titulo}`
      }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$1, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="mb-6 flex flex-wrap items-end justify-between gap-3"${_scopeId}><div${_scopeId}><p class="text-sm font-black uppercase tracking-[0.14em] text-slate-400"${_scopeId}>Editar evento</p><h1 class="text-2xl font-black"${_scopeId}>${ssrInterpolate(__props.evento.titulo)}</h1></div><div class="flex flex-wrap gap-2"${_scopeId}>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("eventos.show", __props.evento.id),
              class: "rounded-md border border-slate-300 px-4 py-2 text-sm font-bold hover:bg-white"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`Abrir evento`);
                } else {
                  return [
                    createTextVNode("Abrir evento")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("eventos.index"),
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
            _push2(`</div></div><div class="grid gap-6 xl:grid-cols-[320px_1fr]"${_scopeId}><aside class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}>`);
            if (__props.evento.cartaz) {
              _push2(`<img${ssrRenderAttr("src", __props.evento.cartaz)}${ssrRenderAttr("alt", __props.evento.titulo)} class="aspect-[4/5] w-full rounded-md bg-slate-100 object-cover"${_scopeId}>`);
            } else {
              _push2(`<div class="grid aspect-[4/5] place-items-center rounded-md bg-slate-100 text-sm font-bold text-slate-400"${_scopeId}>Sem cartaz</div>`);
            }
            _push2(`<label class="mt-4 block cursor-pointer rounded-md border border-slate-300 px-4 py-3 text-center text-sm font-black hover:bg-slate-50"${_scopeId}> Trocar cartaz <input type="file" accept="image/*" class="hidden"${_scopeId}></label></aside><form class="rounded-lg bg-white p-5 shadow-sm"${_scopeId}><div class="grid gap-3 md:grid-cols-4"${_scopeId}><input${ssrRenderAttr("value", unref(form).titulo)} required placeholder="Titulo" class="rounded-md border-slate-300 md:col-span-2"${_scopeId}><input${ssrRenderAttr("value", unref(form).subtitulo)} placeholder="Subtitulo" class="rounded-md border-slate-300"${_scopeId}><input${ssrRenderAttr("value", unref(form).localizacao)} placeholder="Local" class="rounded-md border-slate-300"${_scopeId}><input${ssrRenderAttr("value", unref(form).data_inicio)} type="date" class="rounded-md border-slate-300"${_scopeId}><input${ssrRenderAttr("value", unref(form).data_fim)} type="date" class="rounded-md border-slate-300"${_scopeId}><input${ssrRenderAttr("value", unref(form).periodo)} placeholder="Periodo" class="rounded-md border-slate-300"${_scopeId}><input${ssrRenderAttr("value", unref(form).badge)} placeholder="Etiqueta" class="rounded-md border-slate-300"${_scopeId}><input${ssrRenderAttr("value", unref(form).facebook_post_url)} type="url" placeholder="URL do post do Facebook" class="rounded-md border-slate-300 md:col-span-2"${_scopeId}><select class="rounded-md border-slate-300"${_scopeId}><option value="publicado"${ssrIncludeBooleanAttr(Array.isArray(unref(form).estado) ? ssrLooseContain(unref(form).estado, "publicado") : ssrLooseEqual(unref(form).estado, "publicado")) ? " selected" : ""}${_scopeId}>Publicado</option><option value="rascunho"${ssrIncludeBooleanAttr(Array.isArray(unref(form).estado) ? ssrLooseContain(unref(form).estado, "rascunho") : ssrLooseEqual(unref(form).estado, "rascunho")) ? " selected" : ""}${_scopeId}>Rascunho</option></select><label class="flex items-center gap-2 rounded-md border border-slate-300 px-3 py-2 text-sm font-bold"${_scopeId}><input${ssrIncludeBooleanAttr(Array.isArray(unref(form).destaque) ? ssrLooseContain(unref(form).destaque, null) : unref(form).destaque) ? " checked" : ""} type="checkbox" class="rounded border-slate-300"${_scopeId}> Destaque </label><input${ssrRenderAttr("value", unref(form).ordem)} type="number" min="0" class="rounded-md border-slate-300"${_scopeId}><textarea placeholder="Descricao" rows="5" class="rounded-md border-slate-300 md:col-span-4"${_scopeId}>${ssrInterpolate(unref(form).descricao)}</textarea><textarea placeholder="Programa: uma linha por item" rows="5" class="rounded-md border-slate-300 md:col-span-4"${_scopeId}>${ssrInterpolate(unref(form).programa_texto)}</textarea></div><div class="mt-5 flex flex-wrap gap-2"${_scopeId}><button class="rounded-md bg-emerald-700 px-5 py-3 font-black text-white disabled:opacity-60"${ssrIncludeBooleanAttr(unref(form).processing) ? " disabled" : ""}${_scopeId}>Guardar alteracoes</button>`);
            _push2(ssrRenderComponent(unref(Link), {
              href: _ctx.route("eventos.index"),
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
            _push2(`</div></form></div><section class="mt-6 rounded-lg bg-white p-5 shadow-sm"${_scopeId}><div class="mb-4 flex flex-wrap items-center justify-between gap-3"${_scopeId}><div${_scopeId}><h2 class="text-lg font-black"${_scopeId}>Fotos e videos do evento</h2><p class="text-sm text-slate-500"${_scopeId}>Carrega aqui os ficheiros para aparecerem na ficha do evento e na home.</p></div><label class="cursor-pointer rounded-md bg-slate-900 px-4 py-2 text-sm font-black text-white"${_scopeId}> Adicionar fotos/videos <input type="file" multiple accept="image/*,video/mp4,video/webm,video/quicktime" class="hidden"${_scopeId}></label></div>`);
            if (__props.evento.media?.length) {
              _push2(`<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"${_scopeId}><!--[-->`);
              ssrRenderList(__props.evento.media, (media) => {
                _push2(`<div class="rounded-md border border-slate-200 p-2"${_scopeId}>`);
                if (media.tipo === "foto") {
                  _push2(`<img${ssrRenderAttr("src", media.caminho)}${ssrRenderAttr("alt", media.titulo)} class="aspect-video w-full rounded object-cover"${_scopeId}>`);
                } else {
                  _push2(`<video${ssrRenderAttr("src", media.caminho)} controls class="aspect-video w-full rounded bg-black object-cover"${_scopeId}></video>`);
                }
                _push2(`<div class="mt-2 flex items-center justify-between gap-2 text-xs"${_scopeId}><span class="truncate font-bold"${_scopeId}>${ssrInterpolate(media.titulo)}</span><button type="button" class="text-rose-700 underline"${_scopeId}>Remover</button></div></div>`);
              });
              _push2(`<!--]--></div>`);
            } else {
              _push2(`<div class="rounded-md bg-slate-50 p-6 text-center text-sm font-bold text-slate-500"${_scopeId}> Ainda nao ha fotos ou videos neste evento. </div>`);
            }
            _push2(`</section>`);
          } else {
            return [
              createVNode("div", { class: "mb-6 flex flex-wrap items-end justify-between gap-3" }, [
                createVNode("div", null, [
                  createVNode("p", { class: "text-sm font-black uppercase tracking-[0.14em] text-slate-400" }, "Editar evento"),
                  createVNode("h1", { class: "text-2xl font-black" }, toDisplayString(__props.evento.titulo), 1)
                ]),
                createVNode("div", { class: "flex flex-wrap gap-2" }, [
                  createVNode(unref(Link), {
                    href: _ctx.route("eventos.show", __props.evento.id),
                    class: "rounded-md border border-slate-300 px-4 py-2 text-sm font-bold hover:bg-white"
                  }, {
                    default: withCtx(() => [
                      createTextVNode("Abrir evento")
                    ]),
                    _: 1
                  }, 8, ["href"]),
                  createVNode(unref(Link), {
                    href: _ctx.route("eventos.index"),
                    class: "rounded-md border border-slate-300 px-4 py-2 text-sm font-bold hover:bg-white"
                  }, {
                    default: withCtx(() => [
                      createTextVNode("Voltar")
                    ]),
                    _: 1
                  }, 8, ["href"])
                ])
              ]),
              createVNode("div", { class: "grid gap-6 xl:grid-cols-[320px_1fr]" }, [
                createVNode("aside", { class: "rounded-lg bg-white p-5 shadow-sm" }, [
                  __props.evento.cartaz ? (openBlock(), createBlock("img", {
                    key: 0,
                    src: __props.evento.cartaz,
                    alt: __props.evento.titulo,
                    class: "aspect-[4/5] w-full rounded-md bg-slate-100 object-cover"
                  }, null, 8, ["src", "alt"])) : (openBlock(), createBlock("div", {
                    key: 1,
                    class: "grid aspect-[4/5] place-items-center rounded-md bg-slate-100 text-sm font-bold text-slate-400"
                  }, "Sem cartaz")),
                  createVNode("label", { class: "mt-4 block cursor-pointer rounded-md border border-slate-300 px-4 py-3 text-center text-sm font-black hover:bg-slate-50" }, [
                    createTextVNode(" Trocar cartaz "),
                    createVNode("input", {
                      type: "file",
                      accept: "image/*",
                      class: "hidden",
                      onChange: ($event) => unref(form).cartaz = $event.target.files[0]
                    }, null, 40, ["onChange"])
                  ])
                ]),
                createVNode("form", {
                  class: "rounded-lg bg-white p-5 shadow-sm",
                  onSubmit: withModifiers(guardar, ["prevent"])
                }, [
                  createVNode("div", { class: "grid gap-3 md:grid-cols-4" }, [
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(form).titulo = $event,
                      required: "",
                      placeholder: "Titulo",
                      class: "rounded-md border-slate-300 md:col-span-2"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(form).titulo]
                    ]),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(form).subtitulo = $event,
                      placeholder: "Subtitulo",
                      class: "rounded-md border-slate-300"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(form).subtitulo]
                    ]),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(form).localizacao = $event,
                      placeholder: "Local",
                      class: "rounded-md border-slate-300"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(form).localizacao]
                    ]),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(form).data_inicio = $event,
                      type: "date",
                      class: "rounded-md border-slate-300"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(form).data_inicio]
                    ]),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(form).data_fim = $event,
                      type: "date",
                      class: "rounded-md border-slate-300"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(form).data_fim]
                    ]),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(form).periodo = $event,
                      placeholder: "Periodo",
                      class: "rounded-md border-slate-300"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(form).periodo]
                    ]),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(form).badge = $event,
                      placeholder: "Etiqueta",
                      class: "rounded-md border-slate-300"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(form).badge]
                    ]),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(form).facebook_post_url = $event,
                      type: "url",
                      placeholder: "URL do post do Facebook",
                      class: "rounded-md border-slate-300 md:col-span-2"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(form).facebook_post_url]
                    ]),
                    withDirectives(createVNode("select", {
                      "onUpdate:modelValue": ($event) => unref(form).estado = $event,
                      class: "rounded-md border-slate-300"
                    }, [
                      createVNode("option", { value: "publicado" }, "Publicado"),
                      createVNode("option", { value: "rascunho" }, "Rascunho")
                    ], 8, ["onUpdate:modelValue"]), [
                      [vModelSelect, unref(form).estado]
                    ]),
                    createVNode("label", { class: "flex items-center gap-2 rounded-md border border-slate-300 px-3 py-2 text-sm font-bold" }, [
                      withDirectives(createVNode("input", {
                        "onUpdate:modelValue": ($event) => unref(form).destaque = $event,
                        type: "checkbox",
                        class: "rounded border-slate-300"
                      }, null, 8, ["onUpdate:modelValue"]), [
                        [vModelCheckbox, unref(form).destaque]
                      ]),
                      createTextVNode(" Destaque ")
                    ]),
                    withDirectives(createVNode("input", {
                      "onUpdate:modelValue": ($event) => unref(form).ordem = $event,
                      type: "number",
                      min: "0",
                      class: "rounded-md border-slate-300"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [
                        vModelText,
                        unref(form).ordem,
                        void 0,
                        { number: true }
                      ]
                    ]),
                    withDirectives(createVNode("textarea", {
                      "onUpdate:modelValue": ($event) => unref(form).descricao = $event,
                      placeholder: "Descricao",
                      rows: "5",
                      class: "rounded-md border-slate-300 md:col-span-4"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(form).descricao]
                    ]),
                    withDirectives(createVNode("textarea", {
                      "onUpdate:modelValue": ($event) => unref(form).programa_texto = $event,
                      placeholder: "Programa: uma linha por item",
                      rows: "5",
                      class: "rounded-md border-slate-300 md:col-span-4"
                    }, null, 8, ["onUpdate:modelValue"]), [
                      [vModelText, unref(form).programa_texto]
                    ])
                  ]),
                  createVNode("div", { class: "mt-5 flex flex-wrap gap-2" }, [
                    createVNode("button", {
                      class: "rounded-md bg-emerald-700 px-5 py-3 font-black text-white disabled:opacity-60",
                      disabled: unref(form).processing
                    }, "Guardar alteracoes", 8, ["disabled"]),
                    createVNode(unref(Link), {
                      href: _ctx.route("eventos.index"),
                      class: "rounded-md border border-slate-300 px-5 py-3 font-black hover:bg-slate-50"
                    }, {
                      default: withCtx(() => [
                        createTextVNode("Cancelar")
                      ]),
                      _: 1
                    }, 8, ["href"])
                  ])
                ], 32)
              ]),
              createVNode("section", { class: "mt-6 rounded-lg bg-white p-5 shadow-sm" }, [
                createVNode("div", { class: "mb-4 flex flex-wrap items-center justify-between gap-3" }, [
                  createVNode("div", null, [
                    createVNode("h2", { class: "text-lg font-black" }, "Fotos e videos do evento"),
                    createVNode("p", { class: "text-sm text-slate-500" }, "Carrega aqui os ficheiros para aparecerem na ficha do evento e na home.")
                  ]),
                  createVNode("label", { class: "cursor-pointer rounded-md bg-slate-900 px-4 py-2 text-sm font-black text-white" }, [
                    createTextVNode(" Adicionar fotos/videos "),
                    createVNode("input", {
                      type: "file",
                      multiple: "",
                      accept: "image/*,video/mp4,video/webm,video/quicktime",
                      class: "hidden",
                      onChange: uploadMedia
                    }, null, 32)
                  ])
                ]),
                __props.evento.media?.length ? (openBlock(), createBlock("div", {
                  key: 0,
                  class: "grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
                }, [
                  (openBlock(true), createBlock(Fragment, null, renderList(__props.evento.media, (media) => {
                    return openBlock(), createBlock("div", {
                      key: media.id,
                      class: "rounded-md border border-slate-200 p-2"
                    }, [
                      media.tipo === "foto" ? (openBlock(), createBlock("img", {
                        key: 0,
                        src: media.caminho,
                        alt: media.titulo,
                        class: "aspect-video w-full rounded object-cover"
                      }, null, 8, ["src", "alt"])) : (openBlock(), createBlock("video", {
                        key: 1,
                        src: media.caminho,
                        controls: "",
                        class: "aspect-video w-full rounded bg-black object-cover"
                      }, null, 8, ["src"])),
                      createVNode("div", { class: "mt-2 flex items-center justify-between gap-2 text-xs" }, [
                        createVNode("span", { class: "truncate font-bold" }, toDisplayString(media.titulo), 1),
                        createVNode("button", {
                          type: "button",
                          class: "text-rose-700 underline",
                          onClick: ($event) => apagarMedia(media)
                        }, "Remover", 8, ["onClick"])
                      ])
                    ]);
                  }), 128))
                ])) : (openBlock(), createBlock("div", {
                  key: 1,
                  class: "rounded-md bg-slate-50 p-6 text-center text-sm font-bold text-slate-500"
                }, " Ainda nao ha fotos ou videos neste evento. "))
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Eventos/Edit.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
export {
  _sfc_main as default
};
