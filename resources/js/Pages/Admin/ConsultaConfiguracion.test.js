import {shallowMount} from "@vue/test-utils";
import {reactive} from "vue";
import {expect,it,vi} from "vitest";
import Empresa from "./ConfiguracionEmpresa/Index.vue";
import Menu from "./MenubarItems/Form.vue";
const state=vi.hoisted(()=>({post:vi.fn(),put:vi.fn()}));
vi.mock("primevue/usetoast",()=>({useToast:()=>({add:vi.fn()})}));
vi.mock("@inertiajs/vue3",()=>({
    Link:{template:"<a><slot/></a>"},usePage:()=>({props:{auth:{is_super_admin:true}}}),
    useForm:data=>{let defaults=data;const f=reactive({...data,errors:{},processing:false});Object.assign(f,{cancel(){},post:state.post,put:state.put,defaults:v=>{defaults=v;},reset:()=>Object.assign(f,JSON.parse(JSON.stringify(defaults))),clearErrors:()=>{f.errors={};}});return f;}
}));
const global={mocks:{route:()=>"/"},renderStubDefaultSlot:true,stubs:{AppLayout:{template:"<div><slot name='card-content'/></div>"},Dialog:{template:"<section><slot/></section>"}}};
it("empresa consulta datos sin normalizar RFC ni completar parámetros y bloquea submit",async()=>{
    state.put.mockClear();
    const w=shallowMount(Empresa,{props:{configurada:true,puedeEditar:true,modoEmpresa:"consulta",configuracion:{id:1,rfc:"rfc guardado",regimen_fiscal_id:99,parametros_operativos:{},integraciones:{}}},global});
    expect(w.vm.form.rfc).toBe("rfc guardado");expect(w.vm.form.regimen_fiscal_id).toBe(99);expect(w.vm.form.moneda).toBeNull();
    expect(w.vm.form.parametros_operativos.hora_corte_operativo).toBeNull();
    expect(w.find('button-stub[label="Guardar configuración"]').exists()).toBe(false);
    await w.get("form").trigger("submit");expect(state.put).not.toHaveBeenCalled();
    await w.setProps({configuracion:{id:1,rfc:"Actualizado",domicilio_fiscal:{calle:"Nueva",colonia:"Colonia nueva",codigo_postal_id:7,division_admin_tres_id:8}}});
    expect(w.vm.form.rfc).toBe("Actualizado");expect(w.vm.form.domicilio_fiscal.calle).toBe("Nueva");expect(w.vm.form.domicilio_fiscal.colonia).toBe("Colonia nueva");
    expect(w.find('select-stub[id="colonia"]').exists()).toBe(false);
});
it("menú conserva valor condicional parámetros y rutas guardadas sin reconstruirlos",async()=>{
    state.put.mockClear();state.post.mockClear();
    const item={id:2,type:"route:dynamic",label:"Histórico",value:'[{"condition_type":"legacy","route_name":"clientes.show"}]',params:{cliente:"{cliente}",extra:"valor"},parent_id:3,parent:{id:3,label:"Fuera de página"},modules:[{id:1,menubar_item_module:{routes:["clientes.legacy"]}}]};
    const original=JSON.stringify(item);
    const w=shallowMount(Menu,{props:{readonly:true,item,modules:[{id:1,name:"clientes",route_name:"clientes"}],menubarItems:[]},global});
    expect(w.vm.form.value).toBe(item.value);expect(JSON.parse(w.vm.form.params)).toEqual(item.params);
    expect(w.vm.form.modules[1].routes).toEqual(["clientes.legacy"]);
    await w.get("form").trigger("submit");expect(state.put).not.toHaveBeenCalled();expect(state.post).not.toHaveBeenCalled();
    expect(w.find('button-stub[label="Guardar"]').exists()).toBe(false);expect(JSON.stringify(item)).toBe(original);
});
