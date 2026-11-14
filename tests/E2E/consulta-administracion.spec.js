import {expect,test} from "@playwright/test";
import {login} from "./support/auth.js";

test("usuarios y sucursales alternan consulta y edición sin envío accidental",async({page},testInfo)=>{
    await login(page);
    await page.goto("/admin/users?buscar=Test&por_pagina=25");
    await page.getByRole("link",{name:/^Ver /}).first().click();
    let dialog=page.getByRole("dialog");
    await expect(dialog).toContainText("Consulta · solo lectura");
    await expect(dialog.getByLabel("Nombre",{exact:true})).toBeDisabled();
    await expect(dialog.getByRole("button",{name:"Guardar",exact:true})).toHaveCount(0);
    expect(page.url()).toContain("buscar=Test");
    const consultation=page.url();
    await page.goto(consultation.replace("modo=consulta","modo=edicion"));
    await expect(page.getByRole("dialog").getByLabel("Nombre",{exact:true})).toBeEnabled();
    await page.goto(consultation);
    await expect(page.getByRole("dialog")).toContainText("Consulta · solo lectura");
    await page.setViewportSize({width:390,height:844});
    await page.screenshot({path:testInfo.outputPath("usuario-consulta-movil.png"),fullPage:true});
    await page.setViewportSize({width:1440,height:1000});
    await page.goto("/admin/sucursales");
    await page.getByRole("button",{name:/^Ver /}).first().click();
    dialog=page.getByRole("dialog");
    await expect(dialog.locator("#sucursal-nombre")).toHaveAttribute("readonly","");
    await expect(dialog.getByRole("button",{name:"Guardar",exact:true})).toHaveCount(0);
    const writes=[];page.on("request",req=>{if(["POST","PUT","PATCH","DELETE"].includes(req.method()))writes.push(req.url());});
    await dialog.locator("#sucursal-nombre").press("Enter");
    await dialog.getByRole("button",{name:"Cerrar consulta",exact:true}).click();
    await expect(dialog).toHaveCount(0);
    await page.waitForURL(url => url.searchParams.get("modo") !== "consulta");
    expect(writes).toEqual([]);
    await page.getByRole("button",{name:/^Ver /}).first().click();
    await expect(page.getByRole("dialog")).toContainText("Consulta · solo lectura");
    await page.keyboard.press("Escape");
    await expect(page.getByRole("dialog")).toHaveCount(0);
    await page.waitForURL(url => url.searchParams.get("modo") !== "consulta");
    await page.reload();
    await expect(page.getByRole("dialog")).toHaveCount(0);
});


test("empresa y menú abren consulta sin controles de mutación",async({page},testInfo)=>{
    await login(page);await page.goto("/admin/configuracion-empresa?modo=consulta");
    await expect(page.getByText("Consulta · solo lectura.",{exact:true})).toBeVisible();
    await expect(page.getByRole("button",{name:"Guardar configuración"})).toHaveCount(0);
    await page.goto("/admin/menubar-items");
    await page.getByRole("button",{name:/^Ver /}).first().click();
    const dialog=page.getByRole("dialog");await expect(dialog).toContainText("Consulta · solo lectura");
    await expect(dialog.getByRole("button",{name:"Guardar",exact:true})).toHaveCount(0);
    const writes=[];page.on("request",req=>{if(["POST","PUT","PATCH","DELETE"].includes(req.method()))writes.push(req.url());});
    await dialog.locator("textarea").first().press("Enter");
    expect(writes).toEqual([]);
    await page.setViewportSize({width:390,height:844});
    await page.screenshot({path:testInfo.outputPath("menu-consulta-movil.png"),fullPage:true});
    await dialog.getByRole("button",{name:"Cerrar consulta",exact:true}).click();
    await expect(dialog).toHaveCount(0);
});


test("crear menú conserva el orden predeterminado del servidor",async({page})=>{
    await login(page);await page.goto("/admin/menubar-items");
    await page.getByRole("button",{name:"Crear nuevo",exact:true}).click();
    const dialog=page.getByRole("dialog",{name:"Configurar item de menú",exact:true});
    await dialog.getByPlaceholder("Ej: Ver clientes, Nueva consulta...").fill("Menú consulta QA");
    await dialog.getByPlaceholder("clientes.index, clientes.show...").fill("clientes.index");
    await dialog.locator(".p-multiselect").first().click();
    await page.getByRole("option",{name:"clientes",exact:true}).getByRole("checkbox").check();
    await dialog.getByPlaceholder("Ej: Ver clientes, Nueva consulta...").click();
    await expect(dialog.getByText("Vistas de", {exact:false})).toBeVisible();
    await dialog.locator(".p-multiselect").nth(1).click();
    await page.getByRole("option",{name:"Listado / Vista principal",exact:true}).getByRole("checkbox").check();
    await dialog.getByPlaceholder("Ej: Ver clientes, Nueva consulta...").click();
    await dialog.getByRole("button",{name:"Guardar",exact:true}).click();
    await expect(dialog).toHaveCount(0);
    await expect(page.getByText("Item creado",{exact:true})).toBeVisible();
});
