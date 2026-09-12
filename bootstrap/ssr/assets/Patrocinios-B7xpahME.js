import { computed, unref, withCtx, createVNode, toDisplayString, openBlock, createBlock, Fragment, renderList, withModifiers, createCommentVNode, createTextVNode, withDirectives, vModelText, vModelCheckbox, useSSRContext } from "vue";
import { ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderAttr, ssrIncludeBooleanAttr, ssrLooseContain } from "vue/server-renderer";
import { usePage, useForm, Head } from "@inertiajs/vue3";
import { _ as _sfc_main$1 } from "./PublicShell-w1n6S5Xb.js";
import { _ as _sfc_main$2 } from "./SponsorsSlider-CUXZJoI8.js";
import { _ as _export_sfc } from "./_plugin-vue_export-helper-1tPrXgE0.js";
import "./CookieBanner-Cf0YSWpg.js";
const _sfc_main = {
  __name: "Patrocinios",
  __ssrInlineRender: true,
  props: {
    page: Object,
    patrocinadores: { type: Array, default: () => [] }
  },
  setup(__props) {
    const props = __props;
    const inertiaPage = usePage();
    const content = computed(() => props.page?.conteudo || {});
    const form = useForm({
      nome: "",
      empresa: "",
      email: "",
      telefone: "",
      mensagem: "",
      aceita_contacto: false
    });
    const benefits = computed(() => (content.value.extra || "").split("\n").map((line) => line.split("|").map((part) => part.trim())).filter((parts) => parts[0] && parts[1]));
    const submit = () => {
      form.post(route("patrocinios.store"), {
        preserveScroll: true,
        onSuccess: () => form.reset()
      });
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(unref(Head), {
        title: `${props.page?.titulo || "Patrocínios"} | ARDC Santana`
      }, null, _parent));
      _push(ssrRenderComponent(_sfc_main$1, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<main data-v-5abafea4${_scopeId}><section class="relative isolate overflow-hidden bg-stone-800 px-5 py-24 text-white lg:px-8" data-v-5abafea4${_scopeId}><div class="absolute inset-0 -z-10 bg-gradient-to-r from-stone-900/90 to-stone-800/60" data-v-5abafea4${_scopeId}></div><div class="mx-auto max-w-6xl" data-v-5abafea4${_scopeId}><p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-400" data-v-5abafea4${_scopeId}>Festa anual</p><h1 class="mt-4 max-w-3xl text-5xl font-bold leading-tight text-white sm:text-6xl" data-v-5abafea4${_scopeId}>${ssrInterpolate(content.value.hero_titulo || "Apoia a Festa de Santa Ana")}</h1><p class="mt-6 max-w-2xl text-lg leading-relaxed text-stone-200" data-v-5abafea4${_scopeId}>${ssrInterpolate(content.value.hero_subtitulo || "Ajude-nos a manter viva uma festa feita pela comunidade. Cada contributo conta e a visibilidade é combinada consigo.")}</p></div></section><div class="h-0.5 bg-gradient-to-r from-transparent via-amber-400 to-transparent" data-v-5abafea4${_scopeId}></div><section class="py-20 bg-amber-50" data-v-5abafea4${_scopeId}><div class="mx-auto grid max-w-7xl gap-12 px-5 lg:grid-cols-[0.95fr_1.05fr] lg:items-start lg:px-8" data-v-5abafea4${_scopeId}><div data-v-5abafea4${_scopeId}><p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-700" data-v-5abafea4${_scopeId}>Como funciona</p><h2 class="mt-3 text-4xl font-bold text-stone-800 leading-tight" data-v-5abafea4${_scopeId}>${ssrInterpolate(content.value.introducao || "Patrocínio simples, direto e adaptado.")}</h2><p class="mt-5 text-lg leading-relaxed text-stone-600" data-v-5abafea4${_scopeId}>${ssrInterpolate(content.value.corpo || "Cada patrocinador contribui com o que lhe for possível. Em troca, trabalhamos consigo para dar a máxima visibilidade à vossa marca: no recinto com lonas, nas nossas redes sociais e aqui no nosso site.")}</p><div class="mt-8 grid gap-4" data-v-5abafea4${_scopeId}><!--[-->`);
            ssrRenderList(benefits.value, (benefit) => {
              _push2(`<article class="rounded-xl border border-amber-200 bg-white p-5 shadow-sm" data-v-5abafea4${_scopeId}><h3 class="text-lg font-bold text-stone-800" data-v-5abafea4${_scopeId}>${ssrInterpolate(benefit[0])}</h3><p class="mt-2 text-stone-600" data-v-5abafea4${_scopeId}>${ssrInterpolate(benefit[1])}</p></article>`);
            });
            _push2(`<!--]--></div></div><form class="rounded-xl border border-amber-200 bg-white p-8 shadow-md" data-v-5abafea4${_scopeId}><h2 class="text-2xl font-bold text-stone-800" data-v-5abafea4${_scopeId}>Proposta de patrocínio</h2><p class="mt-2 text-sm text-stone-500" data-v-5abafea4${_scopeId}>Diga-nos como gostaria de apoiar. Entraremos em contacto para combinar os detalhes.</p>`);
            if (unref(inertiaPage).props.flash?.success) {
              _push2(`<p class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm font-semibold text-emerald-800" data-v-5abafea4${_scopeId}>${ssrInterpolate(unref(inertiaPage).props.flash.success)}</p>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="mt-6 grid gap-4 sm:grid-cols-2" data-v-5abafea4${_scopeId}><label class="field" data-v-5abafea4${_scopeId}> Nome <input${ssrRenderAttr("value", unref(form).nome)} type="text" class="pub-input" data-v-5abafea4${_scopeId}>`);
            if (unref(form).errors.nome) {
              _push2(`<span class="field-error" data-v-5abafea4${_scopeId}>${ssrInterpolate(unref(form).errors.nome)}</span>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</label><label class="field" data-v-5abafea4${_scopeId}> Empresa <input${ssrRenderAttr("value", unref(form).empresa)} type="text" class="pub-input" data-v-5abafea4${_scopeId}>`);
            if (unref(form).errors.empresa) {
              _push2(`<span class="field-error" data-v-5abafea4${_scopeId}>${ssrInterpolate(unref(form).errors.empresa)}</span>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</label><label class="field" data-v-5abafea4${_scopeId}> Email <input${ssrRenderAttr("value", unref(form).email)} type="email" class="pub-input" data-v-5abafea4${_scopeId}>`);
            if (unref(form).errors.email) {
              _push2(`<span class="field-error" data-v-5abafea4${_scopeId}>${ssrInterpolate(unref(form).errors.email)}</span>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</label><label class="field" data-v-5abafea4${_scopeId}> Telefone <input${ssrRenderAttr("value", unref(form).telefone)} type="tel" class="pub-input" data-v-5abafea4${_scopeId}>`);
            if (unref(form).errors.telefone) {
              _push2(`<span class="field-error" data-v-5abafea4${_scopeId}>${ssrInterpolate(unref(form).errors.telefone)}</span>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</label><label class="field sm:col-span-2" data-v-5abafea4${_scopeId}> Mensagem / proposta livre <textarea rows="5" class="pub-input" data-v-5abafea4${_scopeId}>${ssrInterpolate(unref(form).mensagem)}</textarea>`);
            if (unref(form).errors.mensagem) {
              _push2(`<span class="field-error" data-v-5abafea4${_scopeId}>${ssrInterpolate(unref(form).errors.mensagem)}</span>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</label></div><label class="mt-5 flex gap-3 text-sm text-stone-600" data-v-5abafea4${_scopeId}><input${ssrIncludeBooleanAttr(Array.isArray(unref(form).aceita_contacto) ? ssrLooseContain(unref(form).aceita_contacto, null) : unref(form).aceita_contacto) ? " checked" : ""} type="checkbox" class="mt-1 rounded border-amber-300 text-amber-600 focus:ring-amber-500" data-v-5abafea4${_scopeId}><span data-v-5abafea4${_scopeId}>Aceito ser contactado pela ARDC Santana para dar seguimento a esta proposta.</span></label>`);
            if (unref(form).errors.aceita_contacto) {
              _push2(`<span class="mt-1 block text-xs text-red-600" data-v-5abafea4${_scopeId}>${ssrInterpolate(unref(form).errors.aceita_contacto)}</span>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`<button type="submit" class="mt-6 w-full rounded-md bg-amber-600 px-5 py-3 font-semibold text-white shadow-sm transition hover:bg-amber-700 disabled:opacity-60"${ssrIncludeBooleanAttr(unref(form).processing) ? " disabled" : ""} data-v-5abafea4${_scopeId}>${ssrInterpolate(unref(form).processing ? "A enviar..." : "Enviar proposta")}</button></form></div></section>`);
            _push2(ssrRenderComponent(_sfc_main$2, { patrocinadores: __props.patrocinadores }, null, _parent2, _scopeId));
            _push2(`</main>`);
          } else {
            return [
              createVNode("main", null, [
                createVNode("section", { class: "relative isolate overflow-hidden bg-stone-800 px-5 py-24 text-white lg:px-8" }, [
                  createVNode("div", { class: "absolute inset-0 -z-10 bg-gradient-to-r from-stone-900/90 to-stone-800/60" }),
                  createVNode("div", { class: "mx-auto max-w-6xl" }, [
                    createVNode("p", { class: "text-xs font-bold uppercase tracking-[0.2em] text-amber-400" }, "Festa anual"),
                    createVNode("h1", { class: "mt-4 max-w-3xl text-5xl font-bold leading-tight text-white sm:text-6xl" }, toDisplayString(content.value.hero_titulo || "Apoia a Festa de Santa Ana"), 1),
                    createVNode("p", { class: "mt-6 max-w-2xl text-lg leading-relaxed text-stone-200" }, toDisplayString(content.value.hero_subtitulo || "Ajude-nos a manter viva uma festa feita pela comunidade. Cada contributo conta e a visibilidade é combinada consigo."), 1)
                  ])
                ]),
                createVNode("div", { class: "h-0.5 bg-gradient-to-r from-transparent via-amber-400 to-transparent" }),
                createVNode("section", { class: "py-20 bg-amber-50" }, [
                  createVNode("div", { class: "mx-auto grid max-w-7xl gap-12 px-5 lg:grid-cols-[0.95fr_1.05fr] lg:items-start lg:px-8" }, [
                    createVNode("div", null, [
                      createVNode("p", { class: "text-xs font-bold uppercase tracking-[0.2em] text-amber-700" }, "Como funciona"),
                      createVNode("h2", { class: "mt-3 text-4xl font-bold text-stone-800 leading-tight" }, toDisplayString(content.value.introducao || "Patrocínio simples, direto e adaptado."), 1),
                      createVNode("p", { class: "mt-5 text-lg leading-relaxed text-stone-600" }, toDisplayString(content.value.corpo || "Cada patrocinador contribui com o que lhe for possível. Em troca, trabalhamos consigo para dar a máxima visibilidade à vossa marca: no recinto com lonas, nas nossas redes sociais e aqui no nosso site."), 1),
                      createVNode("div", { class: "mt-8 grid gap-4" }, [
                        (openBlock(true), createBlock(Fragment, null, renderList(benefits.value, (benefit) => {
                          return openBlock(), createBlock("article", {
                            key: benefit[0],
                            class: "rounded-xl border border-amber-200 bg-white p-5 shadow-sm"
                          }, [
                            createVNode("h3", { class: "text-lg font-bold text-stone-800" }, toDisplayString(benefit[0]), 1),
                            createVNode("p", { class: "mt-2 text-stone-600" }, toDisplayString(benefit[1]), 1)
                          ]);
                        }), 128))
                      ])
                    ]),
                    createVNode("form", {
                      class: "rounded-xl border border-amber-200 bg-white p-8 shadow-md",
                      onSubmit: withModifiers(submit, ["prevent"])
                    }, [
                      createVNode("h2", { class: "text-2xl font-bold text-stone-800" }, "Proposta de patrocínio"),
                      createVNode("p", { class: "mt-2 text-sm text-stone-500" }, "Diga-nos como gostaria de apoiar. Entraremos em contacto para combinar os detalhes."),
                      unref(inertiaPage).props.flash?.success ? (openBlock(), createBlock("p", {
                        key: 0,
                        class: "mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm font-semibold text-emerald-800"
                      }, toDisplayString(unref(inertiaPage).props.flash.success), 1)) : createCommentVNode("", true),
                      createVNode("div", { class: "mt-6 grid gap-4 sm:grid-cols-2" }, [
                        createVNode("label", { class: "field" }, [
                          createTextVNode(" Nome "),
                          withDirectives(createVNode("input", {
                            "onUpdate:modelValue": ($event) => unref(form).nome = $event,
                            type: "text",
                            class: "pub-input"
                          }, null, 8, ["onUpdate:modelValue"]), [
                            [vModelText, unref(form).nome]
                          ]),
                          unref(form).errors.nome ? (openBlock(), createBlock("span", {
                            key: 0,
                            class: "field-error"
                          }, toDisplayString(unref(form).errors.nome), 1)) : createCommentVNode("", true)
                        ]),
                        createVNode("label", { class: "field" }, [
                          createTextVNode(" Empresa "),
                          withDirectives(createVNode("input", {
                            "onUpdate:modelValue": ($event) => unref(form).empresa = $event,
                            type: "text",
                            class: "pub-input"
                          }, null, 8, ["onUpdate:modelValue"]), [
                            [vModelText, unref(form).empresa]
                          ]),
                          unref(form).errors.empresa ? (openBlock(), createBlock("span", {
                            key: 0,
                            class: "field-error"
                          }, toDisplayString(unref(form).errors.empresa), 1)) : createCommentVNode("", true)
                        ]),
                        createVNode("label", { class: "field" }, [
                          createTextVNode(" Email "),
                          withDirectives(createVNode("input", {
                            "onUpdate:modelValue": ($event) => unref(form).email = $event,
                            type: "email",
                            class: "pub-input"
                          }, null, 8, ["onUpdate:modelValue"]), [
                            [vModelText, unref(form).email]
                          ]),
                          unref(form).errors.email ? (openBlock(), createBlock("span", {
                            key: 0,
                            class: "field-error"
                          }, toDisplayString(unref(form).errors.email), 1)) : createCommentVNode("", true)
                        ]),
                        createVNode("label", { class: "field" }, [
                          createTextVNode(" Telefone "),
                          withDirectives(createVNode("input", {
                            "onUpdate:modelValue": ($event) => unref(form).telefone = $event,
                            type: "tel",
                            class: "pub-input"
                          }, null, 8, ["onUpdate:modelValue"]), [
                            [vModelText, unref(form).telefone]
                          ]),
                          unref(form).errors.telefone ? (openBlock(), createBlock("span", {
                            key: 0,
                            class: "field-error"
                          }, toDisplayString(unref(form).errors.telefone), 1)) : createCommentVNode("", true)
                        ]),
                        createVNode("label", { class: "field sm:col-span-2" }, [
                          createTextVNode(" Mensagem / proposta livre "),
                          withDirectives(createVNode("textarea", {
                            "onUpdate:modelValue": ($event) => unref(form).mensagem = $event,
                            rows: "5",
                            class: "pub-input"
                          }, null, 8, ["onUpdate:modelValue"]), [
                            [vModelText, unref(form).mensagem]
                          ]),
                          unref(form).errors.mensagem ? (openBlock(), createBlock("span", {
                            key: 0,
                            class: "field-error"
                          }, toDisplayString(unref(form).errors.mensagem), 1)) : createCommentVNode("", true)
                        ])
                      ]),
                      createVNode("label", { class: "mt-5 flex gap-3 text-sm text-stone-600" }, [
                        withDirectives(createVNode("input", {
                          "onUpdate:modelValue": ($event) => unref(form).aceita_contacto = $event,
                          type: "checkbox",
                          class: "mt-1 rounded border-amber-300 text-amber-600 focus:ring-amber-500"
                        }, null, 8, ["onUpdate:modelValue"]), [
                          [vModelCheckbox, unref(form).aceita_contacto]
                        ]),
                        createVNode("span", null, "Aceito ser contactado pela ARDC Santana para dar seguimento a esta proposta.")
                      ]),
                      unref(form).errors.aceita_contacto ? (openBlock(), createBlock("span", {
                        key: 1,
                        class: "mt-1 block text-xs text-red-600"
                      }, toDisplayString(unref(form).errors.aceita_contacto), 1)) : createCommentVNode("", true),
                      createVNode("button", {
                        type: "submit",
                        class: "mt-6 w-full rounded-md bg-amber-600 px-5 py-3 font-semibold text-white shadow-sm transition hover:bg-amber-700 disabled:opacity-60",
                        disabled: unref(form).processing
                      }, toDisplayString(unref(form).processing ? "A enviar..." : "Enviar proposta"), 9, ["disabled"])
                    ], 32)
                  ])
                ]),
                createVNode(_sfc_main$2, { patrocinadores: __props.patrocinadores }, null, 8, ["patrocinadores"])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/Public/Patrocinios.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const Patrocinios = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-5abafea4"]]);
export {
  Patrocinios as default
};
