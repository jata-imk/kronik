import {shallowMount} from "@vue/test-utils";
import {reactive,nextTick} from "vue";
import {expect,it,vi} from "vitest";
import Index from "./Index.vue";
const state=vi.hoisted(()=>({page:null,submit:vi.fn()}));
vi.mock("@inertiajs/vue3",()=>({router:{get:vi.fn()},usePage:()=>state.page,useForm:data=>{let defaults=data;const f=reactive({...data,errors:{},processing:false});Object.assign(f,{cancel(){},defaults:v=>{defaults=v;},reset:()=>Object.assign(f,JSON.parse(JSON.stringify(defaults))),clearErrors:()=>{f.errors={};},put:state.submit,post:state.submit});return f;}}));
vi.mock("@/Composables/useListadoServidor",()=>({useListadoServidor:()=>({visit:vi.fn()})}));
vi.mock("primevue/useconfirm",()=>({useConfirm:()=>({})}));
vi.mock("primevue/usetoast",()=>({useToast:()=>({add:vi.fn()})}));
it("plantillas vuelve a consulta ante recarga parcial de permisos",async()=>{
 state.page=reactive({props:{auth:{permissions:{"update-plantillas-documentos":true}}}});
 const version={id:1,numero:1,estado:"borrador",puede_editar:true,contenido_html:"<p>Guardado</p>",presentacion:{marca_agua:"Original"}};
 const plantilla={id:10,clave:"qa",nombre:"QA",versionSeleccionada:version,versiones:[version]};
 const w=shallowMount(Index,{props:{plantillaSeleccionada:plantilla,modoVersion:"edicion"},global:{renderStubDefaultSlot:true,mocks:{route:()=>"/"},stubs:{AppLayout:{template:"<main><slot name='card-content'/></main>"},Dialog:{props:["visible"],template:"<section v-if='visible'><slot/></section>"}}}});
 expect(w.vm.editorReadonly).toBe(false);state.page.props.auth.permissions["update-plantillas-documentos"]=false;await nextTick();
 expect(w.vm.editorReadonly).toBe(true);expect(w.vm.form.contenido_html).toBe("<p>Guardado</p>");
 state.submit.mockClear();await w.get("form").trigger("submit");expect(state.submit).not.toHaveBeenCalled();
 await w.setProps({modoVersion:null});expect(w.vm.editorVisible).toBe(false);
});
