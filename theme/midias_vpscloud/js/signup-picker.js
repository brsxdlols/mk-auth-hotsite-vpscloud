
(function(){
'use strict';
const triggers=[...document.querySelectorAll('[data-signup-picker]')];
if(!triggers.length)return;
const dialog=document.createElement('dialog');dialog.className='signup-picker';dialog.setAttribute('aria-labelledby','signup-picker-title');
dialog.innerHTML='<div class="signup-picker-head"><div><h2 id="signup-picker-title">Escolha seu plano</h2><p>Selecione uma opção para continuar o cadastro.</p></div><button type="button" aria-label="Fechar seleção de planos">×</button></div><div class="signup-picker-list" aria-live="polite"></div>';
document.body.append(dialog);
const list=dialog.querySelector('.signup-picker-list');let opener=null,overflow='',generation=0;
function text(tag,value){const el=document.createElement(tag);el.textContent=value;return el;}
async function load(){
 const token=++generation;list.replaceChildren(text('p','Carregando planos…'));
 try{
  const response=await fetch('/abgs-data.php',{cache:'no-store'});if(!response.ok)throw Error();
  const data=await response.json();if(data.ok===false||!Array.isArray(data.planos))throw Error();
  if(token!==generation||!dialog.open)return;
  list.replaceChildren();
  if(!data.planos.length){list.append(text('p','Nenhum plano disponível no momento. Entre em contato com o provedor.'));return;}
  const route=data.config?.cadastro_modo==='whatsapp'?'/cadastro-whatsapp.hhvm':'/cadastro-sistema.php';
  data.planos.forEach(plan=>{
   const card=document.createElement('article');
   if(window.vpscloudPlanVisual){const visual=document.createElement('div');visual.innerHTML=window.vpscloudPlanVisual(plan.nome,data.config?.plan_visual_mode||'dynamic');card.append(visual.firstElementChild);}
   const details=document.createElement('div');details.className='signup-plan-details';card.append(details);details.append(text('h3',plan.nome));
   const price=Number(String(plan.valor).replace(',','.'));
   details.append(text('p',Number.isFinite(price)?price.toLocaleString('pt-BR',{style:'currency',currency:'BRL'})+' / mês':'Consulte o valor'));
   const link=text('a','Selecionar plano');link.className='btn primary';link.href=route+'?plano='+encodeURIComponent(plan.nome);card.append(link);list.append(card);
  });
 }catch(e){if(token!==generation||!dialog.open)return;list.replaceChildren(text('p','Não foi possível carregar os planos.'));
 const retry=text('button','Tentar novamente');retry.type='button';retry.addEventListener('click',load);list.append(retry);}
}
triggers.forEach(el=>el.addEventListener('click',e=>{
 e.preventDefault();opener=el;document.querySelector('.nav nav')?.classList.remove('open');document.querySelector('.menu-btn')?.setAttribute('aria-expanded','false');
 overflow=document.body.style.overflow;dialog.showModal();document.body.style.overflow='hidden';load();
}));
dialog.querySelector('button').addEventListener('click',()=>dialog.close());
dialog.addEventListener('click',e=>{if(e.target===dialog){const b=dialog.getBoundingClientRect();if(e.clientX<b.left||e.clientX>b.right||e.clientY<b.top||e.clientY>b.bottom)dialog.close();}});
dialog.addEventListener('close',()=>{generation++;document.body.style.overflow=overflow;opener?.focus();});
}());
