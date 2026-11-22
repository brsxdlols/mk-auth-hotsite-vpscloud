const fs=require('fs'),vm=require('vm'),assert=require('assert');
const source=fs.readFileSync(__dirname+'/../theme/midias_vpscloud/js/site-pointing.js','utf8');
function setup(active,success=true){
 const handlers={},buttons=[{hidden:false}],images={hidden:false},notice={textContent:''};
 const field={focus(){this.focused=true;}};
 const pointingForm={action:'https://mk-auth.example.org/admin/hotsite_layout.hhvm'};
 const form={appendChild(){},querySelectorAll(){return buttons;},addEventListener(type,fn){handlers[type]=fn;},requestSubmit(){this.submitted=true;}};
 const select={value:active?'__vpscloud_pointing__':'layout-vpscloud-sistema',form,addEventListener(type,fn){handlers[type]=fn;}};
 const panel={dataset:{activeTarget:active?'1':'0'},querySelector(selector){return selector==='form'?pointingForm:field;}};
 let posted;
 const document={querySelector(){return select;},getElementById(id){return id==='vpscloud-site-settings'?panel:images;},createElement(){return Object.assign(notice,{setAttribute(){}});}};
 class FormData{set(key,value){this[key]=value;}}
 class DOMParser{parseFromString(){return {getElementById(){return success?{dataset:{activeTarget:'0'}}:null;}};}}
 vm.runInNewContext(source,{document,FormData,DOMParser,fetch:async(url,options)=>{posted={url,options};return {ok:true,text:async()=>''};}});
 return {select,panel,buttons,images,field,form,handlers,notice,posted:()=>posted};
}
(async()=>{
 let state=setup(false);
 assert(state.panel.hidden && !state.buttons[0].hidden);
 state.select.value='__vpscloud_pointing__';state.handlers.change();
 assert(!state.panel.hidden && state.buttons[0].hidden && state.images.hidden);
 let prevented=false;await state.handlers.submit({preventDefault(){prevented=true;}});
 assert(prevented && state.field.focused && !state.posted());
 state.select.value='padrao';state.handlers.change();
 assert(state.panel.hidden && !state.buttons[0].hidden && state.images.hidden);
 for(const theme of ['layout-vpscloud-sistema','layout-vpscloud-whatsapp']){
  state.select.value=theme;state.handlers.change();assert(!state.images.hidden && state.panel.hidden);
 }
 state.select.value='novo_hotsite';state.handlers.change();assert(state.images.hidden);
 state=setup(true);state.select.value='padrao';
 await state.handlers.submit({preventDefault(){}});
 assert(state.posted().options.body.site_mode==='local' && state.form.submitted);
 state=setup(true,false);state.select.value='padrao';
 await state.handlers.submit({preventDefault(){}});
 assert(!state.form.submitted && state.notice.textContent.includes('sessão'));
 console.log('PASS dropdown, conditional field, native save and expired session');
})().catch(error=>{console.error(error);process.exitCode=1;});
